<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\PaymentService;
use App\Services\InventoryService;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class CheckoutController extends Controller
{
    protected $paymentService;
    protected $inventoryService;

    public function __construct(PaymentService $paymentService, InventoryService $inventoryService)
    {
        $this->paymentService = $paymentService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display checkout page
     */
    public function index()
    {
        $locale = app()->getLocale();
        $cartItems = $this->getCartItems();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index', $locale)
                ->with('error', __('messages.cart_empty'));
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $item->total = $item->variant->product->current_price * $item->quantity;
            $subtotal += $item->total;
            
            // Add localized info
            $item->localized = [
                'product_name' => $item->variant->product->getTranslation('product_name', $locale),
                'color_name' => $item->variant->color ? 
                    $item->variant->color->getTranslation('color_name', $locale) : null,
                'size_name' => $item->variant->size->size_name
            ];
        }

        // Get user addresses if logged in
        $addresses = auth()->check() ? auth()->user()->addresses : collect();
        $defaultAddress = auth()->check() ? auth()->user()->defaultAddress : null;

        // Get shipping methods
        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        $shippingMethods = $shippingMethods->map(function ($method) use ($locale, $subtotal) {
            $method->localized_name = $method->getTranslation('method_name', $locale);
            $method->is_free = $method->free_shipping_threshold && 
                              $subtotal >= $method->free_shipping_threshold;
            $method->final_cost = $method->is_free ? 0 : $method->base_cost;
            return $method;
        });

        // Tax calculation (19% German VAT included in price)
        $taxRate = 0.19;
        $taxAmount = $subtotal * ($taxRate / (1 + $taxRate)); // Extract tax from price
        
        $checkoutData = [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'shippingMethods' => $shippingMethods,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'countries' => ['DE' => 'Deutschland'] // For now, only Germany
        ];

        if (request()->wantsJson()) {
            return response()->json($checkoutData);
        }

        return view('checkout.index', $checkoutData);
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        $locale = app()->getLocale();
        
        $validated = $request->validate([
            // Customer info
            'email' => 'required|email',
            
            // Billing address
            'billing_first_name' => 'required|string|max:100',
            'billing_last_name' => 'required|string|max:100',
            'billing_street_address' => 'required|string|max:255',
            'billing_postal_code' => 'required|string|max:20',
            'billing_city' => 'required|string|max:100',
            'billing_phone' => 'required|string|max:20',
            
            // Shipping address
            'shipping_same_as_billing' => 'boolean',
            'shipping_first_name' => 'required_if:shipping_same_as_billing,false|string|max:100',
            'shipping_last_name' => 'required_if:shipping_same_as_billing,false|string|max:100',
            'shipping_street_address' => 'required_if:shipping_same_as_billing,false|string|max:255',
            'shipping_postal_code' => 'required_if:shipping_same_as_billing,false|string|max:20',
            'shipping_city' => 'required_if:shipping_same_as_billing,false|string|max:100',
            'shipping_phone' => 'nullable|string|max:20',
            
            // Order details
            'shipping_method_id' => 'required|exists:shipping_methods,shipping_method_id',
            'payment_method' => 'required|in:stripe,paypal,invoice',
            'customer_notes' => 'nullable|string|max:500',
            
            // Legal
            'accept_terms' => 'required|accepted',
            'privacy_consent' => 'required|accepted',
            
            // Optional
            'newsletter_signup' => 'boolean',
            'create_account' => 'boolean',
            'password' => 'required_if:create_account,true|string|min:8|confirmed'
        ]);

        DB::beginTransaction();

        try {
            // Get cart items
            $cartItems = $this->getCartItems();
            
            if ($cartItems->isEmpty()) {
                throw new \Exception(__('messages.cart_empty'));
            }

            // Validate stock availability
            foreach ($cartItems as $item) {
                if (!$this->inventoryService->checkAvailability($item->variant_id, $item->quantity)) {
                    $productName = $item->variant->product->getTranslation('product_name', $locale);
                    throw new \Exception(__('messages.insufficient_stock_for', ['product' => $productName]));
                }
            }

            // Calculate order totals
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += $item->variant->product->current_price * $item->quantity;
            }

            $shippingMethod = ShippingMethod::findOrFail($validated['shipping_method_id']);
            $shippingCost = $this->calculateShipping($subtotal, $shippingMethod);
            
            // Tax calculation (VAT included in German prices)
            $taxRate = 0.19;
            $priceIncludesTax = true;
            
            if ($priceIncludesTax) {
                $taxAmount = $subtotal * ($taxRate / (1 + $taxRate));
                $netAmount = $subtotal - $taxAmount;
            } else {
                $taxAmount = $subtotal * $taxRate;
                $netAmount = $subtotal;
            }
            
            $total = $subtotal + $shippingCost;

            // Handle user creation or guest checkout
            $user = auth()->user();
            
            if (!$user && $request->boolean('create_account')) {
                // Create new user account
                $user = \App\Models\User::create([
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'first_name' => $validated['billing_first_name'],
                    'last_name' => $validated['billing_last_name'],
                    'phone' => $validated['billing_phone'],
                    'preferred_language' => $locale,
                ]);
                
                // Log them in
                auth()->login($user);
                
                // Merge guest cart
                $this->mergeGuestCart();
            }

            // Create order
            $order = Order::create([
                'user_id' => $user?->user_id,
                'guest_email' => $user ? null : $validated['email'],
                'order_language' => $locale,
                'order_status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $total,
                'customer_notes' => $validated['customer_notes']
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                $product = $item->variant->product;
                
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $product->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $product->getTranslation('product_name', $locale),
                    'product_sku' => $item->variant->variant_sku ?? $product->sku,
                    'color_name' => $item->variant->color ? 
                        $item->variant->color->getTranslation('color_name', $locale) : null,
                    'size_name' => $item->variant->size->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $product->current_price,
                    'total_price' => $product->current_price * $item->quantity
                ]);

                // Reserve stock
                $this->inventoryService->reserveStock($item->variant_id, $item->quantity, $order->order_id);
            }

            // Create addresses
            OrderAddress::create([
                'order_id' => $order->order_id,
                'address_type' => 'billing',
                'first_name' => $validated['billing_first_name'],
                'last_name' => $validated['billing_last_name'],
                'street_address' => $validated['billing_street_address'],
                'postal_code' => $validated['billing_postal_code'],
                'city' => $validated['billing_city'],
                'phone' => $validated['billing_phone'],
                'email' => $validated['email']
            ]);

            // Shipping address
            if ($request->boolean('shipping_same_as_billing')) {
                OrderAddress::create([
                    'order_id' => $order->order_id,
                    'address_type' => 'shipping',
                    'first_name' => $validated['billing_first_name'],
                    'last_name' => $validated['billing_last_name'],
                    'street_address' => $validated['billing_street_address'],
                    'postal_code' => $validated['billing_postal_code'],
                    'city' => $validated['billing_city'],
                    'phone' => $validated['billing_phone']
                ]);
            } else {
                OrderAddress::create([
                    'order_id' => $order->order_id,
                    'address_type' => 'shipping',
                    'first_name' => $validated['shipping_first_name'],
                    'last_name' => $validated['shipping_last_name'],
                    'street_address' => $validated['shipping_street_address'],
                    'postal_code' => $validated['shipping_postal_code'],
                    'city' => $validated['shipping_city'],
                    'phone' => $validated['shipping_phone'] ?? $validated['billing_phone']
                ]);
            }

            // Handle GDPR consents
            if ($user) {
                $user->giveConsent('terms_of_service', '1.0', $request->ip());
                $user->giveConsent('privacy_policy', '1.0', $request->ip());
                
                if ($request->boolean('newsletter_signup')) {
                    $user->giveConsent('newsletter', '1.0', $request->ip());
                    
                    // Subscribe to newsletter
                    \App\Models\NewsletterSubscriber::firstOrCreate(
                        ['email' => $user->email],
                        [
                            'preferred_language' => $locale,
                            'is_active' => true,
                            'confirmed_at' => now()
                        ]
                    );
                }
            }

            // Process payment
            if ($validated['payment_method'] === 'stripe') {
                $paymentIntent = $this->paymentService->createPaymentIntent($order);
                
                DB::commit();
                
                // Return payment intent for frontend to complete
                return response()->json([
                    'success' => true,
                    'order_id' => $order->order_id,
                    'payment_intent' => $paymentIntent->client_secret,
                    'redirect_url' => route('checkout.payment', ['locale' => $locale, 'order' => $order->order_id])
                ]);
                
            } elseif ($validated['payment_method'] === 'invoice') {
                // Invoice payment - mark as processing
                $order->update(['order_status' => 'processing']);
                
                // Clear cart
                $this->clearCart();
                
                // Send confirmation email
                $this->sendOrderConfirmation($order);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'order_id' => $order->order_id,
                    'redirect_url' => route('checkout.success', ['locale' => $locale, 'order' => $order->order_number])
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Show success page
     */
    public function success($locale, $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        
        // Verify order belongs to user or session
        if (auth()->check()) {
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }
        } else {
            $sessionOrderId = session('last_order_id');
            if ($order->order_id !== $sessionOrderId) {
                abort(403);
            }
        }

        return view('checkout.success', compact('order'));
    }

    /**
     * Confirm payment (webhook or frontend callback)
     */
    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent' => 'required|string',
            'order_id' => 'required|integer'
        ]);

        $order = Order::findOrFail($validated['order_id']);
        
        try {
            $result = $this->paymentService->confirmPayment($validated['payment_intent']);
            
            if ($result['status'] === 'succeeded') {
                $order->markAsPaid($result['id']);
                $this->clearCart();
                $this->sendOrderConfirmation($order);
                
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('checkout.success', [
                        'locale' => app()->getLocale(), 
                        'order' => $order->order_number
                    ])
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => __('messages.payment_failed')
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get cart items
     */
    private function getCartItems()
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id())
                ->with([
                    'variant.product.images',
                    'variant.color',
                    'variant.size'
                ])
                ->get();
        }

        // Guest cart from session
        $sessionCart = collect(session()->get('cart', []));
        
        if ($sessionCart->isEmpty()) {
            return collect();
        }

        $variantIds = $sessionCart->pluck('variant_id')->toArray();
        $variants = ProductVariant::with([
            'product.images',
            'color',
            'size'
        ])->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');

        return $sessionCart->map(function ($item) use ($variants) {
            $variant = $variants->get($item['variant_id']);
            if (!$variant) {
                return null;
            }

            return (object) [
                'cart_item_id' => $item['variant_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'variant' => $variant
            ];
        })->filter();
    }

    /**
     * Clear cart after successful order
     */
    private function clearCart()
    {
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())->delete();
        } else {
            session()->forget('cart');
        }
    }

    /**
     * Merge guest cart with user cart
     */
    private function mergeGuestCart()
    {
        $guestCart = session()->get('cart', []);
        
        if (empty($guestCart)) {
            return;
        }

        foreach ($guestCart as $item) {
            $existingItem = CartItem::where('user_id', auth()->id())
                ->where('variant_id', $item['variant_id'])
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $item['quantity']);
            } else {
                CartItem::create([
                    'user_id' => auth()->id(),
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        }

        session()->forget('cart');
    }

    /**
     * Calculate shipping cost
     */
    private function calculateShipping($subtotal, ShippingMethod $shippingMethod)
    {
        if ($shippingMethod->free_shipping_threshold && 
            $subtotal >= $shippingMethod->free_shipping_threshold) {
            return 0;
        }

        return $shippingMethod->base_cost;
    }

    /**
     * Send order confirmation email
     */
    private function sendOrderConfirmation(Order $order)
    {
        try {
            $locale = $order->order_language ?? app()->getLocale();
            app()->setLocale($locale);
            
            Mail::to($order->customer_email)
                ->locale($locale)
                ->send(new OrderConfirmation($order));
                
            // Store order ID in session for guest users
            if (!auth()->check()) {
                session(['last_order_id' => $order->order_id]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation: ' . $e->getMessage());
        }
    }
}