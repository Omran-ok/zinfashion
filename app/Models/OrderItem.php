<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $primaryKey = 'order_item_id';
    
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_sku',
        'color_name',
        'size_name',
        'quantity',
        'unit_price',
        'total_price',
        'tax_amount',
        'discount_amount'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2'
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($orderItem) {
            // Calculate total price if not set
            if (empty($orderItem->total_price)) {
                $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
            }
            
            // Calculate tax if applicable
            if (empty($orderItem->tax_amount) && config('app.tax_included')) {
                $taxRate = 0.19; // 19% German VAT
                $orderItem->tax_amount = $orderItem->total_price * ($taxRate / (1 + $taxRate));
            }
        });
    }

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Accessors
     */
    public function getNetPriceAttribute()
    {
        return $this->total_price - $this->tax_amount;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->discount_amount <= 0) {
            return 0;
        }
        
        $originalPrice = $this->total_price + $this->discount_amount;
        return round(($this->discount_amount / $originalPrice) * 100, 2);
    }

    /**
     * Get formatted item details
     */
    public function getFormattedItem($locale = null)
    {
        $locale = $locale ?? $this->order->order_language ?? app()->getLocale();
        
        return [
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'variant' => $this->getVariantDescription(),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'product_url' => $this->product ? $this->product->getUrl($locale) : null,
            'image_url' => $this->product ? $this->product->primary_image?->image_url : null
        ];
    }

    /**
     * Get variant description
     */
    public function getVariantDescription()
    {
        $parts = [];
        
        if ($this->color_name) {
            $parts[] = $this->color_name;
        }
        
        if ($this->size_name) {
            $parts[] = __('product.size') . ' ' . $this->size_name;
        }
        
        return implode(', ', $parts);
    }

    /**
     * Check if item can be refunded
     */
    public function canBeRefunded()
    {
        return $this->order->can_be_refunded;
    }

    /**
     * Check if item can be reviewed
     */
    public function canBeReviewed()
    {
        // Can review if order is completed/delivered and user hasn't reviewed yet
        if (!in_array($this->order->order_status, ['completed', 'delivered'])) {
            return false;
        }
        
        if (!$this->order->user_id) {
            return false; // Guest orders can't leave reviews
        }
        
        // Check if already reviewed
        $existingReview = ProductReview::where('product_id', $this->product_id)
            ->where('user_id', $this->order->user_id)
            ->where('order_item_id', $this->order_item_id)
            ->exists();
        
        return !$existingReview;
    }

    /**
     * Create review for this item
     */
    public function createReview($rating, $reviewText = null, $title = null)
    {
        if (!$this->canBeReviewed()) {
            throw new \Exception('This item cannot be reviewed');
        }
        
        return ProductReview::create([
            'product_id' => $this->product_id,
            'user_id' => $this->order->user_id,
            'order_item_id' => $this->order_item_id,
            'rating' => $rating,
            'review_title' => $title,
            'review_text' => $reviewText,
            'review_language' => $this->order->order_language ?? app()->getLocale(),
            'is_verified_purchase' => true,
            'is_approved' => false // Requires admin approval
        ]);
    }

    /**
     * Get item for invoice/email
     */
    public function getInvoiceData()
    {
        return [
            'position' => $this->order->items->search(function ($item) {
                return $item->order_item_id === $this->order_item_id;
            }) + 1,
            'sku' => $this->product_sku,
            'description' => $this->product_name . ($this->getVariantDescription() ? ' - ' . $this->getVariantDescription() : ''),
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unit_price, 2, ',', '.') . ' €',
            'total' => number_format($this->total_price, 2, ',', '.') . ' €',
            'tax' => number_format($this->tax_amount, 2, ',', '.') . ' €',
        ];
    }
}