<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasTranslations;

    protected $primaryKey = 'product_id';
    
    protected $fillable = [
        'subcategory_id',
        'product_name',
        'product_slug',
        'sku',
        'description',
        'short_description',
        'regular_price',
        'sale_price',
        'material',
        'care_instructions',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        // Translation fields
        'name_translations',
        'description_translations',
        'short_description_translations',
        'slug_translations',
        'material_translations',
        'care_instructions_translations',
        'meta_title_translations',
        'meta_description_translations'
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'name_translations' => 'json',
        'description_translations' => 'json',
        'short_description_translations' => 'json',
        'slug_translations' => 'json',
        'material_translations' => 'json',
        'care_instructions_translations' => 'json',
        'meta_title_translations' => 'json',
        'meta_description_translations' => 'json'
    ];

    /**
     * Define which fields are translatable
     */
    public $translatable = [
        'product_name' => 'name_translations',
        'description' => 'description_translations',
        'short_description' => 'short_description_translations',
        'product_slug' => 'slug_translations',
        'material' => 'material_translations',
        'care_instructions' => 'care_instructions_translations',
        'meta_title' => 'meta_title_translations',
        'meta_description' => 'meta_description_translations'
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            // Generate slug if not provided
            if (empty($product->product_slug)) {
                $product->product_slug = Str::slug($product->product_name);
            }
            
            // Initialize translation arrays
            if (empty($product->name_translations)) {
                $product->name_translations = ['de' => $product->product_name];
            }
            if (empty($product->slug_translations)) {
                $product->slug_translations = ['de' => $product->product_slug];
            }
        });

        static::updating(function ($product) {
            // Update German translation when main field changes
            if ($product->isDirty('product_name')) {
                $translations = $product->name_translations ?? [];
                $translations['de'] = $product->product_name;
                $product->name_translations = $translations;
            }
        });
    }

    /**
     * Relationships
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            Subcategory::class,
            'subcategory_id',
            'category_id',
            'subcategory_id',
            'category_id'
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->orderBy('display_order')
            ->orderBy('is_primary', 'desc');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    public function wishlists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists', 'product_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Accessors
     */
    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first() 
            ?? $this->images()->first();
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->regular_price;
    }

    public function getIsOnSaleAttribute()
    {
        return $this->sale_price !== null && $this->sale_price < $this->regular_price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->is_on_sale) {
            return 0;
        }
        
        return round((($this->regular_price - $this->sale_price) / $this->regular_price) * 100);
    }

    public function getInStockAttribute()
    {
        return $this->variants()->where('stock_quantity', '>', 0)->exists();
    }

    public function getTotalStockAttribute()
    {
        return $this->variants()->sum('stock_quantity');
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->reviews()->where('is_approved', true)->count();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'regular_price');
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->where('stock_quantity', '>', 0);
        });
    }

    public function scopeWithMainImage($query)
    {
        return $query->with(['images' => function ($q) {
            $q->where('is_primary', true);
        }]);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('subcategory', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    public function scopeInSubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where(function ($q) use ($min) {
                $q->where('sale_price', '>=', $min)
                  ->orWhere(function ($q2) use ($min) {
                      $q2->whereNull('sale_price')
                         ->where('regular_price', '>=', $min);
                  });
            });
        }

        if ($max !== null) {
            $query->where(function ($q) use ($max) {
                $q->where('sale_price', '<=', $max)
                  ->orWhere(function ($q2) use ($max) {
                      $q2->whereNull('sale_price')
                         ->where('regular_price', '<=', $max);
                  });
            });
        }

        return $query;
    }

    /**
     * Get localized URL for the product
     */
    public function getUrl($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $slug = $this->getTranslation('product_slug', $locale);
        
        return route('products.show', ['locale' => $locale, 'slug' => $slug]);
    }

    /**
     * Get structured data for SEO
     */
    public function getStructuredData($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->getTranslation('product_name', $locale),
            'description' => $this->getTranslation('short_description', $locale),
            'sku' => $this->sku,
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->current_price,
                'priceCurrency' => 'EUR',
                'availability' => $this->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
            'aggregateRating' => $this->review_count > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->average_rating,
                'reviewCount' => $this->review_count
            ] : null
        ];
    }
}