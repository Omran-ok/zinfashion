<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslations;

    protected $primaryKey = 'category_id';
    
    protected $fillable = [
        'category_name',
        'category_slug',
        'translations',
        'slug_translations',
        'category_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'translations' => 'json',
        'slug_translations' => 'json'
    ];

    /**
     * Define which fields are translatable
     */
    public $translatable = [
        'category_name' => 'translations',
        'category_slug' => 'slug_translations'
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($category) {
            // Initialize translation arrays
            if (empty($category->translations)) {
                $category->translations = ['de' => $category->category_name];
            }
            if (empty($category->slug_translations)) {
                $category->slug_translations = ['de' => $category->category_slug];
            }
        });
    }

    /**
     * Relationships
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Subcategory::class,
            'category_id',
            'subcategory_id',
            'category_id',
            'subcategory_id'
        );
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category_order')->orderBy('category_name');
    }

    /**
     * Get active subcategories
     */
    public function activeSubcategories()
    {
        return $this->subcategories()->where('is_active', true)->orderBy('subcategory_order');
    }

    /**
     * Get product count
     */
    public function getProductCountAttribute()
    {
        return $this->products()->active()->count();
    }

    /**
     * Get URL for category
     */
    public function getUrl($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $slug = $this->getTranslation('category_slug', $locale);
        
        return route('products.index', ['locale' => $locale, 'category' => $slug]);
    }
}