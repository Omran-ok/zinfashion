<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Application Locales Configuration
    |--------------------------------------------------------------------------
    |
    | The application locales determines the possible locales that can be
    | used in the application. This includes the default locale and all
    | available locales with their configuration.
    |
    */

    'default' => env('APP_LOCALE', 'de'),
    
    'fallback' => env('APP_FALLBACK_LOCALE', 'de'),
    
    'available' => ['de', 'en', 'ar'],
    
    /*
    |--------------------------------------------------------------------------
    | Locale Details
    |--------------------------------------------------------------------------
    |
    | Detailed configuration for each available locale including native names,
    | direction, date formats, and other locale-specific settings.
    |
    */
    
    'supported' => [
        'de' => [
            'name' => 'German',
            'native' => 'Deutsch',
            'flag' => '🇩🇪',
            'dir' => 'ltr',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'thousands_separator' => '.',
            'decimal_separator' => ',',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
            'dir' => 'ltr',
            'date_format' => 'm/d/Y',
            'time_format' => 'h:i A',
            'currency_symbol' => '€',
            'currency_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
            'dir' => 'rtl',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Locale Route Settings
    |--------------------------------------------------------------------------
    |
    | Configure how locales are handled in routes, including whether to hide
    | the default locale from URLs and how to detect user preferences.
    |
    */
    
    'hide_default_in_url' => false,
    
    'detect_browser_language' => true,
    
    'store_in_session' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Locale Switcher Settings
    |--------------------------------------------------------------------------
    */
    
    'switcher' => [
        'enabled' => true,
        'position' => 'header', // header, footer, both
        'display' => 'native', // code, name, native, flag
    ],
    
];