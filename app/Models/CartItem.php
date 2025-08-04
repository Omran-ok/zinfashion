<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $primaryKey = 'cart_item_id';
    
    protected $fillable = [
        'user_id',
        'session_id',
        'variant_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Clean up old guest cart items periodically
        static::created(function ($cartItem) {
            if ($cartItem->session_id) {
                // Delete old guest cart items (older than 7 days)
                self::where('session_id', '!=', null)
                    ->where('created_at', '<', now()->subDays(7))
                    ->delete();
            }
        });
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            ProductVariant::class,
            'variant_id',
            'product_id',
            'variant_id',
            'product_id'
        );
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeOldGuestItems($query, $days = 7)
    {
        return $query->whereNotNull('session_id')
                     ->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getSubtotalAttribute()
    {
        if (!$this->variant || !$this->variant->product) {
            return 0;
        }
        
        return $this->quantity * $this->variant->product->current_price;
    }

    public function getIsAvailableAttribute()
    {
        return $this->variant && $this->variant->canBePurchased($this->quantity);
    }

    public function getMaxQuantityAttribute()
    {
        if (!$this->variant) {
            return 0;
        }
        
        // Limit to 10 per item or available stock, whichever is lower
        return min(10, $this->variant->available_quantity);
    }

    /**
     * Get formatted cart item for display
     */
    public function getFormattedItem($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!$this->variant || !$this->variant->product) {
            return null;
        }

        $product = $this->variant->product;
        
        return [
            'cart_item_id' => $this->cart_item_id,
            'product' => [
                'id' => $product->product_id,
                'name' => $product->getTranslation('product_name', $locale),
                'slug' => $product->getTranslation('product_slug', $locale),
                'url' => $product->getUrl($locale),
                'image' => $product->primary_image?->image_url,
            ],
            'variant' => [
                'id' => $this->variant->variant_id,
                'sku' => $this->variant->variant_sku,
                'color' => $this->variant->color ? [
                    'name' => $this->variant->color->getTranslation('color_name', $locale),
                    'code' => $this->variant->color->color_code
                ] : null,
                'size' => $this->variant->size->size_name,
            ],
            'quantity' => $this->quantity,
            'max_quantity' => $this->max_quantity,
            'price' => $product->current_price,
            'regular_price' => $product->regular_price,
            'subtotal' => $this->subtotal,
            'is_on_sale' => $product->is_on_sale,
            'is_available' => $this->is_available,
            'stock_status' => $this->variant->stock_status,
            'added_at' => $this->created_at
        ];
    }

    /**
     * Update quantity
     */
    public function updateQuantity($newQuantity)
    {
        if ($newQuantity <= 0) {
            $this->delete();
            return null;
        }
        
        if ($newQuantity > $this->max_quantity) {
            throw new \Exception(__('messages.quantity_exceeds_stock'));
        }
        
        $this->update(['quantity' => $newQuantity]);
        
        return $this;
    }

    /**
     * Check if item can be checked out
     */
    public function canCheckout()
    {
        return $this->is_available && 
               $this->variant->product->is_active &&
               $this->quantity > 0;
    }

    /**
     * Merge with another cart item (same variant)
     */
    public function mergeWith(CartItem $otherItem)
    {
        if ($this->variant_id !== $otherItem->variant_id) {
            throw new \Exception('Cannot merge different variants');
        }
        
        $newQuantity = $this->quantity + $otherItem->quantity;
        
        // Check max quantity
        if ($newQuantity > $this->max_quantity) {
            $newQuantity = $this->max_quantity;
        }
        
        $this->update(['quantity' => $newQuantity]);
        $otherItem->delete();
        
        return $this;
    }
}