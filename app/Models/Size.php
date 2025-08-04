<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Size extends Model
{
    protected $primaryKey = 'size_id';
    
    protected $fillable = [
        'size_category_id',
        'size_name',
        'size_name_translations',
        'size_order',
        'measurements',
        'is_active'
    ];

    protected $casts = [
        'size_name_translations' => 'json',
        'measurements' => 'json',
        'size_order' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function sizeCategory(): BelongsTo
    {
        return $this->belongsTo(SizeCategory::class, 'size_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'size_id');
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
        return $query->orderBy('size_order')->orderBy('size_name');
    }

    /**
     * Get formatted measurements
     */
    public function getFormattedMeasurements($unit = 'cm')
    {
        if (!$this->measurements || !is_array($this->measurements)) {
            return [];
        }

        $formatted = [];
        foreach ($this->measurements as $key => $value) {
            $formatted[$key] = $value . ' ' . $unit;
        }

        return $formatted;
    }

    /**
     * Get measurement by key
     */
    public function getMeasurement($key)
    {
        return $this->measurements[$key] ?? null;
    }

    /**
     * Get display info
     */
    public function getDisplayInfo($locale = null)
    {
        return [
            'id' => $this->size_id,
            'name' => $this->size_name,
            'measurements' => $this->measurements,
            'order' => $this->size_order
        ];
    }
}