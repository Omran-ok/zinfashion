<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    use HasTranslations;

    protected $primaryKey = 'color_id';
    
    protected $fillable = [
        'color_name',
        'translations',
        'color_code',
        'is_active'
    ];

    protected $casts = [
        'translations' => 'json',
        'is_active' => 'boolean'
    ];

    /**
     * Define which fields are translatable
     */
    public $translatable = [
        'color_name' => 'translations'
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($color) {
            // Initialize translations
            if (empty($color->translations)) {
                $color->translations = ['de' => $color->color_name];
            }
        });
    }

    /**
     * Relationships
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'color_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get display info
     */
    public function getDisplayInfo($locale = null)
    {
        return [
            'id' => $this->color_id,
            'name' => $this->getTranslation('color_name', $locale),
            'code' => $this->color_code,
            'is_active' => $this->is_active
        ];
    }

    /**
     * Get CSS style
     */
    public function getStyleAttribute()
    {
        if ($this->color_code) {
            return "background-color: {$this->color_code};";
        }
        return '';
    }

    /**
     * Check if color is light (for text contrast)
     */
    public function getIsLightAttribute()
    {
        if (!$this->color_code) {
            return false;
        }

        // Convert hex to RGB
        $hex = str_replace('#', '', $this->color_code);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5;
    }
}