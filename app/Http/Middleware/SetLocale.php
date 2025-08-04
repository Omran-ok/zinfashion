<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get available locales from config
        $availableLocales = config('locales.available', ['de', 'en', 'ar']);
        $defaultLocale = config('locales.default', 'de');
        
        // Try to get locale from URL segment
        $urlLocale = $request->segment(1);
        
        // Check if URL has valid locale
        if (in_array($urlLocale, $availableLocales)) {
            $locale = $urlLocale;
        } else {
            // Try to get from session
            $locale = Session::get('locale');
            
            // If not in session, try user preference (if authenticated)
            if (!$locale && auth()->check()) {
                $locale = auth()->user()->preferred_language;
            }
            
            // If still no locale, try browser preference
            if (!$locale) {
                $locale = $request->getPreferredLanguage($availableLocales);
            }
            
            // Default to German
            $locale = $locale ?: $defaultLocale;
        }
        
        // Set the application locale
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        // Set text direction
        $direction = $locale === 'ar' ? 'rtl' : 'ltr';
        Session::put('text_direction', $direction);
        
        // Set locale for URLs
        URL::defaults(['locale' => $locale]);
        
        // Share locale data with all views
        view()->share([
            'currentLocale' => $locale,
            'availableLocales' => $availableLocales,
            'textDirection' => $direction
        ]);
        
        return $next($request);
    }
}