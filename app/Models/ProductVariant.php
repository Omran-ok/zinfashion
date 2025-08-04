<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $primaryKey = 'variant_id';
    
    protected $fillable = [
        'product_id',
        'color_id',
        'size_id',
        'variant_sku',
        'stock_quantity',
        'low_stock_threshold',
        'is_available'
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_available' => 'boolean'
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($variant) {
            // Generate variant SKU if not provided
            if (empty($variant->variant_sku)) {
                $variant->variant_sku = $variant->generateSku();
            }
        });

        static::updating(function ($variant) {
            // Auto-update availability based on stock
            if ($variant->isDirty('stock_quantity')) {
                $variant->is_available = $variant->stock_quantity > 0;
            }
        });
    }

    /**
     * Generate unique SKU for variant
     */
    public function generateSku()
    {
        $product = $this->product;
        $color = $this->color;
        $size = $this->size;
        
        $baseSku = $product->sku;
        $colorCode = $color ? strtoupper(substr($color->color_name, 0, 2)) : 'NC';
        $sizeCode = $size->size_name;
        
        return "{$baseSku}-{$colorCode}-{$sizeCode}";
    }

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= low_stock_threshold')
                     ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', 0)
                     ->orWhere('is_available', false);
    }

    /**
     * Accessors
     */
    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getIsOutOfStockAttribute()
    {
        return $this->stock_quantity <= 0 || !$this->is_available;
    }

    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getFormattedNameAttribute()
    {
        $parts = [];
        
        if ($this->product) {
            $parts[] = $this->product->product_name;
        }
        
        if ($this->color) {
            $parts[] = $this->color->color_name;
        }
        
        if ($this->size) {
            $parts[] = 'Size ' . $this->size->size_name;
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Get localized variant name
     */
    public function getLocalizedName($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $parts = [];
        
        if ($this->product) {
            $parts[] = $this->product->getTranslation('product_name', $locale);
        }
        
        if ($this->color) {
            $parts[] = $this->color->getTranslation('color_name', $locale);
        }
        
        if ($this->size) {
            $parts[] = __('product.size') . ' ' . $this->size->size_name;
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Check if variant can be purchased
     */
    public function canBePurchased($quantity = 1)
    {
        return $this->is_available && 
               $this->stock_quantity >= $quantity &&
               $this->product->is_active;
    }

    /**
     * Get available quantity (considering reserved stock)
     */
    public function getAvailableQuantityAttribute()
    {
        // This would need to consider reserved quantities from pending orders
        $reserved = $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->where('order_status', 'pending')
                      ->where('payment_status', '!=', 'paid');
            })
            ->sum('quantity');
        
        return max(0, $this->stock_quantity - $reserved);
    }

    /**
     * Methods
     */
    public function adjustStock($quantity, $type = 'adjustment', $reference = null)
    {
        $oldQuantity = $this->stock_quantity;
        $newQuantity = $oldQuantity + $quantity;
        
        // Ensure stock doesn't go negative
        if ($newQuantity < 0) {
            $newQuantity = 0;
        }
        
        $this->update(['stock_quantity' => $newQuantity]);
        
        // Create stock movement record
        StockMovement::create([
            'variant_id' => $this->variant_id,
            'movement_type' => $quantity > 0 ? 'in' : 'out',
            'quantity' => abs($quantity),
            'reference_type' => $type,
            'reference_id' => $reference,
            'notes' => "Stock adjusted from {$oldQuantity} to {$newQuantity}"
        ]);
        
        return $this;
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock($quantity)
    {
        if ($this->available_quantity < $quantity) {
            throw new \Exception('Insufficient stock available');
        }
        
        // In a more complex system, you might track reservations separately
        return true;
    }

    /**
     * Get price from parent product
     */
    public function getPriceAttribute()
    {
        return $this->product->current_price ?? 0;
    }

    /**
     * Get display information
     */
    public function getDisplayInfo($locale = null)
    {
        return [
            'id' => $this->variant_id,
            'sku' => $this->variant_sku,
            'color' => $this->color ? [
                'id' => $this->color->color_id,
                'name' => $this->color->getTranslation('color_name', $locale),
                'code' => $this->color->color_code
            ] : null,
            'size' => [
                'id' => $this->size->size_id,
                'name' => $this->size->size_name,
                'measurements' => $this->size->measurements
            ],
            'stock' => [
                'quantity' => $this->stock_quantity,
                'available' => $this->available_quantity,
                'status' => $this->stock_status,
                'is_available' => $this->is_available
            ],
            'price' => $this->price
        ];
    }
}