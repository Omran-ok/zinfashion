<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get translation for a specific field
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();
        
        // Check if field has translations
        if (isset($this->translatable[$field])) {
            $translationField = $this->translatable[$field];
            $translations = $this->{$translationField};
            
            // Handle JSON string or array
            if (is_string($translations)) {
                $translations = json_decode($translations, true);
            }
            
            if (is_array($translations) && isset($translations[$locale])) {
                return $translations[$locale];
            }
        }
        
        // Fallback to original field value
        return $this->{$field};
    }

    /**
     * Set translation for a specific field
     */
    public function setTranslation(string $field, string $locale, string $value): void
    {
        if (isset($this->translatable[$field])) {
            $translationField = $this->translatable[$field];
            $translations = $this->{$translationField} ?? [];
            
            if (is_string($translations)) {
                $translations = json_decode($translations, true) ?? [];
            }
            
            $translations[$locale] = $value;
            $this->{$translationField} = $translations;
        }
    }

    /**
     * Get all translations for a field
     */
    public function getTranslations(string $field): array
    {
        if (isset($this->translatable[$field])) {
            $translationField = $this->translatable[$field];
            $translations = $this->{$translationField};
            
            if (is_string($translations)) {
                return json_decode($translations, true) ?? [];
            }
            
            return $translations ?? [];
        }
        
        return [];
    }

    /**
     * Check if field has translation for a locale
     */
    public function hasTranslation(string $field, string $locale): bool
    {
        $translations = $this->getTranslations($field);
        return isset($translations[$locale]);
    }

    /**
     * Get the model with all fields translated to current locale
     */
    public function toLocalizedArray(?string $locale = null): array
    {
        $locale = $locale ?? App::getLocale();
        $data = $this->toArray();
        
        // Translate all translatable fields
        foreach ($this->translatable as $field => $translationField) {
            if (isset($data[$field])) {
                $data[$field] = $this->getTranslation($field, $locale);
            }
            
            // Remove translation JSON fields from output
            unset($data[$translationField]);
        }
        
        // Add locale and direction info
        $data['_locale'] = $locale;
        $data['_direction'] = $locale === 'ar' ? 'rtl' : 'ltr';
        
        return $data;
    }

    /**
     * Scope to search in translated fields
     */
    public function scopeWhereTranslation($query, string $field, string $value, ?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        
        if (isset($this->translatable[$field])) {
            $translationField = $this->translatable[$field];
            
            // Search in JSON column
            return $query->where(function ($q) use ($field, $translationField, $value, $locale) {
                // Search in translation
                $q->whereRaw("JSON_EXTRACT({$translationField}, '$.{$locale}') LIKE ?", ["%{$value}%"])
                  // Fallback to default field
                  ->orWhere($field, 'LIKE', "%{$value}%");
            });
        }
        
        return $query->where($field, 'LIKE', "%{$value}%");
    }

    /**
     * Get field value with fallback chain: requested locale -> default locale -> original field
     */
    public function getTranslationWithFallback(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();
        $defaultLocale = config('app.fallback_locale', 'de');
        
        // Try requested locale
        $translation = $this->getTranslation($field, $locale);
        if ($translation && $translation !== $this->{$field}) {
            return $translation;
        }
        
        // Try default locale if different
        if ($locale !== $defaultLocale) {
            $translation = $this->getTranslation($field, $defaultLocale);
            if ($translation && $translation !== $this->{$field}) {
                return $translation;
            }
        }
        
        // Fallback to original field
        return $this->{$field};
    }

    /**
     * Fill model with translations
     */
    public function fillWithTranslations(array $data, string $locale): void
    {
        foreach ($data as $key => $value) {
            if (isset($this->translatable[$key])) {
                $this->setTranslation($key, $locale, $value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Get attribute accessor for translated fields
     */
    public function getAttribute($key)
    {
        // Check if this is a translatable field with accessor
        if (isset($this->translatable[$key]) && !isset($this->attributes[$key])) {
            return $this->getTranslation($key);
        }
        
        return parent::getAttribute($key);
    }
}