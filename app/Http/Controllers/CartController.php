<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display cart
     */
    public function index()
    {
        $locale = app()->getLocale();
        $cartItems = $this->getCartItems();
        
        // Calculate totals
        $subtotal = 0;
        $itemCount = 0;
        
        foreach ($cartItems as $item) {
            $price = $item->variant->product->current_price;
            $item->total = $price * $item->quantity;
            $subtotal += $item->total;
            $itemCount += $item->quantity;
            
            // Add localized product info
            $item->localized = [
                'product_name' => $item->variant->product->getTranslation('product_name', $locale),
                'color_name' => $item->variant->color ? 
                    $item->variant->color->getTranslation('color_name', $locale) : null,
                'size_name' => $item->variant->size->size_name
            ];
        }
        
        // Tax calculation (19% German VAT)
        $taxRate = 0.19;
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal; // Tax included in price
        
        $cartData = [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'item_count' => $itemCount,
            'is_empty' => $cartItems->isEmpty()
        ];
        
        if (request()->wantsJson()) {
            return response()->json($cartData);
        }
        
        return view('cart.index', $cartData);
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,variant_id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);
        
        $locale = app()->getLocale();
        $variant = ProductVariant::with('product', 'color', 'size')->findOrFail($validated['variant_id']);
        
        // Check if product is active
        if (!$variant->product->is_active) {
            return $this->errorResponse(__('messages.product_not_available'));
        }
        
        // Check stock availability
        if ($variant->stock_quantity < $validated['quantity']) {
            return $this->errorResponse(__('messages.insufficient_stock'));
        }
        
        DB::beginTransaction();
        
        try {
            if (auth()->check()) {
                // For logged-in users
                $cartItem = CartItem::where('user_id', auth()->id())
                    ->where('variant_id', $variant->variant_id)
                    ->first();
                
                if ($cartItem) {
                    // Update quantity if item exists
                    $newQuantity = $cartItem->quantity + $validated['quantity'];
                    
                    // Check stock for combined quantity
                    if ($variant->stock_quantity < $newQuantity) {
                        DB::rollBack();
                        return $this->errorResponse(__('messages.insufficient_stock'));
                    }
                    
                    $cartItem->update(['quantity' => $newQuantity]);
                } else {
                    // Create new cart item
                    CartItem::create([
                        'user_id' => auth()->id(),
                        'variant_id' => $variant->variant_id,
                        'quantity' => $validated['quantity']
                    ]);
                }
            } else {
                // For guest users - use session
                $cart = session()->get('cart', []);
                $cartKey = 'variant_' . $variant->variant_id;
                
                if (isset($cart[$cartKey])) {
                    $newQuantity = $cart[$cartKey]['quantity'] + $validated['quantity'];
                    
                    if ($variant->stock_quantity < $newQuantity) {
                        DB::rollBack();
                        return $this->errorResponse(__('messages.insufficient_stock'));
                    }
                    
                    $cart[$cartKey]['quantity'] = $newQuantity;
                } else {
                    $cart[$cartKey] = [
                        'variant_id' => $variant->variant_id,
                        'quantity' => $validated['quantity'],
                        'added_at' => now()
                    ];
                }
                
                session()->put('cart', $cart);
            }
            
            DB::commit();
            
            // Prepare success response
            $productName = $variant->product->getTranslation('product_name', $locale);
            $message = __('messages.product_added', ['product' => $productName]);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'cart_count' => $this->getCartCount(),
                'product' => [
                    'name' => $productName,
                    'image' => $variant->product->primary_image?->image_url,
                    'price' => $variant->product->current_price,
                    'color' => $variant->color?->getTranslation('color_name', $locale),
                    'size' => $variant->size->size_name
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_adding_to_cart'));
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:10'
        ]);
        
        if (auth()->check()) {
            $cartItem = CartItem::where('user_id', auth()->id())
                ->where('cart_item_id', $itemId)
                ->firstOrFail();
            
            if ($validated['quantity'] == 0) {
                $cartItem->delete();
            } else {
                // Check stock
                if ($cartItem->variant->stock_quantity < $validated['quantity']) {
                    return $this->errorResponse(__('messages.insufficient_stock'));
                }
                
                $cartItem->update(['quantity' => $validated['quantity']]);
            }
        } else {
            // Guest cart
            $cart = session()->get('cart', []);
            $cartKey = 'variant_' . $itemId;
            
            if (isset($cart[$cartKey])) {
                if ($validated['quantity'] == 0) {
                    unset($cart[$cartKey]);
                } else {
                    $variant = ProductVariant::find($cart[$cartKey]['variant_id']);
                    if ($variant->stock_quantity < $validated['quantity']) {
                        return $this->errorResponse(__('messages.insufficient_stock'));
                    }
                    
                    $cart[$cartKey]['quantity'] = $validated['quantity'];
                }
                
                session()->put('cart', $cart);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => __('messages.cart_updated'),
            'cart_count' => $this->getCartCount()
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove($itemId)
    {
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())
                ->where('cart_item_id', $itemId)
                ->delete();
        } else {
            $cart = session()->get('cart', []);
            $cartKey = 'variant_' . $itemId;
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
        }
        
        return response()->json([
            'success' => true,
            'message' => __('messages.item_removed'),
            'cart_count' => $this->getCartCount()
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())->delete();
        } else {
            session()->forget('cart');
        }
        
        return response()->json([
            'success' => true,
            'message' => __('messages.cart_cleared')
        ]);
    }

    /**
     * Get cart items
     */
    private function getCartItems()
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id())
                ->with([
                    'variant.product.images' => function ($query) {
                        $query->where('is_primary', true);
                    },
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
            'product.images' => function ($query) {
                $query->where('is_primary', true);
            },
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
                'variant' => $variant,
                'added_at' => $item['added_at'] ?? now()
            ];
        })->filter();
    }

    /**
     * Get cart count
     */
    private function getCartCount()
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id())->sum('quantity');
        }
        
        $cart = session()->get('cart', []);
        return collect($cart)->sum('quantity');
    }

    /**
     * Merge guest cart with user cart after login
     */
    public function mergeGuestCart()
    {
        if (!auth()->check()) {
            return;
        }
        
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
     * Error response helper
     */
    private function errorResponse($message)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 400);
    }
}