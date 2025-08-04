<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Order;
use App\Events\LowStockAlert;
use App\Events\OutOfStockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Check if variant has enough stock
     */
    public function checkAvailability($variantId, $quantity)
    {
        $variant = ProductVariant::find($variantId);
        
        if (!$variant || !$variant->is_available) {
            return false;
        }

        // Consider reserved stock in availability check
        $availableStock = $variant->stock_quantity - $this->getReservedQuantity($variantId);
        
        return $availableStock >= $quantity;
    }

    /**
     * Get reserved quantity for a variant
     */
    public function getReservedQuantity($variantId)
    {
        // Get pending orders that haven't been processed yet
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->where('order_items.variant_id', $variantId)
            ->whereIn('orders.order_status', ['pending'])
            ->where('orders.payment_status', '!=', 'paid')
            ->sum('order_items.quantity');
    }

    /**
     * Reserve stock for pending order
     */
    public function reserveStock($variantId, $quantity, $orderId)
    {
        DB::transaction(function () use ($variantId, $quantity, $orderId) {
            $variant = ProductVariant::lockForUpdate()->find($variantId);
            
            if (!$variant) {
                throw new \Exception('Product variant not found');
            }

            $availableStock = $variant->stock_quantity - $this->getReservedQuantity($variantId);
            
            if ($availableStock < $quantity) {
                throw new \Exception('Insufficient stock available');
            }

            // Log the reservation
            StockMovement::create([
                'variant_id' => $variantId,
                'movement_type' => 'out',
                'quantity' => $quantity,
                'reference_type' => 'order_reserved',
                'reference_id' => $orderId,
                'notes' => "Reserved for order #{$orderId}"
            ]);

            Log::info("Stock reserved: Variant {$variantId}, Quantity {$quantity}, Order {$orderId}");
        });
    }

    /**
     * Process stock deduction after payment
     */
    public function processOrderStock(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id);
                
                if (!$variant) {
                    throw new \Exception("Product variant {$item->variant_id} not found");
                }

                // Deduct stock
                $variant->decrement('stock_quantity', $item->quantity);

                // Record movement
                StockMovement::create([
                    'variant_id' => $item->variant_id,
                    'movement_type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->order_id,
                    'notes' => "Sold in order {$order->order_number}"
                ]);

                // Check stock levels
                $this->checkStockLevels($variant);

                Log::info("Stock deducted: Variant {$item->variant_id}, Quantity {$item->quantity}, Order {$order->order_number}");
            }
        });
    }

    /**
     * Restore stock when order is cancelled
     */
    public function restoreOrderStock(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id);
                
                if (!$variant) {
                    continue;
                }

                // Restore stock
                $variant->increment('stock_quantity', $item->quantity);

                // Record movement
                StockMovement::create([
                    'variant_id' => $item->variant_id,
                    'movement_type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'order_cancelled',
                    'reference_id' => $order->order_id,
                    'notes' => "Restored from cancelled order {$order->order_number}"
                ]);

                Log::info("Stock restored: Variant {$item->variant_id}, Quantity {$item->quantity}, Order {$order->order_number}");
            }
        });
    }

    /**
     * Adjust stock manually
     */
    public function adjustStock($variantId, $newQuantity, $reason = null, $userId = null)
    {
        DB::transaction(function () use ($variantId, $newQuantity, $reason, $userId) {
            $variant = ProductVariant::lockForUpdate()->find($variantId);
            
            if (!$variant) {
                throw new \Exception('Product variant not found');
            }

            $oldQuantity = $variant->stock_quantity;
            $difference = $newQuantity - $oldQuantity;
            
            // Update stock
            $variant->update(['stock_quantity' => $newQuantity]);

            // Record movement
            StockMovement::create([
                'variant_id' => $variantId,
                'movement_type' => 'adjustment',
                'quantity' => $difference,
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => $reason,
                'created_by' => $userId
            ]);

            // Check stock levels
            $this->checkStockLevels($variant);

            Log::info("Stock adjusted: Variant {$variantId}, Old: {$oldQuantity}, New: {$newQuantity}, Reason: {$reason}");
        });
    }

    /**
     * Add stock (receiving inventory)
     */
    public function addStock($variantId, $quantity, $reference = null, $userId = null)
    {
        DB::transaction(function () use ($variantId, $quantity, $reference, $userId) {
            $variant = ProductVariant::lockForUpdate()->find($variantId);
            
            if (!$variant) {
                throw new \Exception('Product variant not found');
            }

            // Add stock
            $variant->increment('stock_quantity', $quantity);

            // Record movement
            StockMovement::create([
                'variant_id' => $variantId,
                'movement_type' => 'in',
                'quantity' => $quantity,
                'reference_type' => 'restock',
                'reference_id' => null,
                'notes' => $reference,
                'created_by' => $userId
            ]);

            Log::info("Stock added: Variant {$variantId}, Quantity {$quantity}");
        });
    }

    /**
     * Check stock levels and trigger alerts
     */
    private function checkStockLevels(ProductVariant $variant)
    {
        // Check if out of stock
        if ($variant->stock_quantity <= 0) {
            $variant->update(['is_available' => false]);
            event(new OutOfStockAlert($variant));
            
            // Notify wishlist users
            $this->notifyWishlistUsers($variant->product_id, 'out_of_stock');
        }
        // Check if low stock
        elseif ($variant->stock_quantity <= $variant->low_stock_threshold) {
            event(new LowStockAlert($variant));
        }
        // Re-enable if was out of stock
        elseif (!$variant->is_available && $variant->stock_quantity > 0) {
            $variant->update(['is_available' => true]);
            
            // Notify wishlist users that item is back
            $this->notifyWishlistUsers($variant->product_id, 'back_in_stock');
        }
    }

    /**
     * Notify wishlist users
     */
    private function notifyWishlistUsers($productId, $type)
    {
        $product = \App\Models\Product::find($productId);
        
        if (!$product) {
            return;
        }

        $users = $product->wishlists()
            ->where(function ($query) use ($type) {
                if ($type === 'back_in_stock') {
                    $query->where('notified_back_in_stock', false);
                } elseif ($type === 'on_sale' && $product->is_on_sale) {
                    $query->where('notified_on_sale', false);
                }
            })
            ->get();

        foreach ($users as $user) {
            try {
                // Send notification (implement your notification logic)
                // \Mail::to($user)->send(new WishlistNotification($product, $type));
                
                // Mark as notified
                $user->wishlist()->updateExistingPivot($productId, [
                    'notified_' . $type => true
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to notify user {$user->user_id} about {$type} for product {$productId}");
            }
        }
    }

    /**
     * Get stock movements history
     */
    public function getStockHistory($variantId, $limit = 50)
    {
        return StockMovement::where('variant_id', $variantId)
            ->with('creator:user_id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts()
    {
        return ProductVariant::with(['product', 'color', 'size'])
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->where('is_available', true)
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts()
    {
        return ProductVariant::with(['product', 'color', 'size'])
            ->where('stock_quantity', 0)
            ->orWhere('is_available', false)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Calculate inventory value
     */
    public function calculateInventoryValue($subcategoryId = null)
    {
        $query = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.product_id')
            ->where('product_variants.stock_quantity', '>', 0)
            ->where('products.is_active', true);

        if ($subcategoryId) {
            $query->where('products.subcategory_id', $subcategoryId);
        }

        return $query->sum(DB::raw('product_variants.stock_quantity * COALESCE(products.cost_price, products.regular_price * 0.5)'));
    }

    /**
     * Clean up old reservations
     */
    public function cleanupOldReservations($hours = 24)
    {
        $cutoffTime = now()->subHours($hours);
        
        // Find old pending orders
        $oldOrders = Order::where('order_status', 'pending')
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<', $cutoffTime)
            ->get();

        foreach ($oldOrders as $order) {
            // Cancel the order
            $order->cancel('Automatic cancellation - payment timeout');
            
            Log::info("Cancelled old pending order: {$order->order_number}");
        }
    }
}