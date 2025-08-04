<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    ProductController,
    CartController,
    CheckoutController,
    OrderController,
    GdprController,
    WishlistController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Language switcher (without locale prefix)
Route::get('locale/{locale}', function ($locale) {
    if (in_array($locale, config('locales.available', ['de', 'en', 'ar']))) {
        session(['locale' => $locale]);
        
        // If user is authenticated, update their preference
        if (auth()->check()) {
            auth()->user()->update(['preferred_language' => $locale]);
        }
    }
    
    return redirect()->back();
})->name('locale.switch');

// Redirect root to default locale
Route::get('/', function () {
    $locale = session('locale', config('locales.default', 'de'));
    return redirect("/{$locale}");
});

// Routes with locale prefix
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => '[a-z]{2}'],
    'middleware' => ['web', 'setlocale']
], function () {
    
    // Homepage
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{slug}', [ProductController::class, 'show'])->name('show');
        Route::get('/quick-view/{id}', [ProductController::class, 'quickView'])->name('quick-view');
    });
    
    // Categories
    Route::get('/category/{slug}', [ProductController::class, 'index'])->name('category.show');
    Route::get('/subcategory/{slug}', [ProductController::class, 'index'])->name('subcategory.show');
    
    // Cart (available for guests and users)
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add', [CartController::class, 'add'])->name('add');
        Route::patch('/update/{item}', [CartController::class, 'update'])->name('update');
        Route::delete('/remove/{item}', [CartController::class, 'remove'])->name('remove');
        Route::post('/clear', [CartController::class, 'clear'])->name('clear');
    });
    
    // Checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'process'])->name('process');
        Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
        Route::post('/payment/confirm', [CheckoutController::class, 'confirmPayment'])->name('payment.confirm');
    });
    
    // Authentication routes (Laravel Breeze will handle these)
    Route::middleware('guest')->group(function () {
        Route::get('/login', function () {
            return view('auth.login');
        })->name('login');
        
        Route::get('/register', function () {
            return view('auth.register');
        })->name('register');
    });
    
    // Authenticated user routes
    Route::middleware(['auth', 'verified'])->group(function () {
        // Account dashboard
        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/', [OrderController::class, 'dashboard'])->name('dashboard');
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::get('/addresses', [UserController::class, 'addresses'])->name('addresses');
            Route::get('/profile', [UserController::class, 'profile'])->name('profile');
            Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
        });
        
        // Wishlist
        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishlistController::class, 'index'])->name('index');
            Route::post('/toggle/{product}', [WishlistController::class, 'toggle'])->name('toggle');
            Route::delete('/remove/{product}', [WishlistController::class, 'remove'])->name('remove');
        });
        
        // GDPR
        Route::prefix('privacy')->name('gdpr.')->group(function () {
            Route::get('/settings', [GdprController::class, 'privacySettings'])->name('settings');
            Route::post('/consent', [GdprController::class, 'updateConsent'])->name('consent.update');
            Route::post('/export', [GdprController::class, 'requestDataExport'])->name('export');
            Route::get('/export/download/{token}', [GdprController::class, 'downloadExport'])->name('export.download');
            Route::post('/delete', [GdprController::class, 'requestDataDeletion'])->name('delete');
        });
    });
    
    // Legal pages (available to all)
    Route::get('/privacy-policy', function ($locale) {
        return view('legal.privacy-policy');
    })->name('privacy-policy');
    
    Route::get('/terms-of-service', function ($locale) {
        return view('legal.terms-of-service');
    })->name('terms-of-service');
    
    Route::get('/imprint', function ($locale) {
        return view('legal.imprint');
    })->name('imprint');
    
    Route::get('/shipping-returns', function ($locale) {
        return view('legal.shipping-returns');
    })->name('shipping-returns');
});

// Webhook routes (no locale prefix)
Route::prefix('webhooks')->group(function () {
    Route::post('/stripe', [PaymentController::class, 'handleStripeWebhook'])
        ->name('webhooks.stripe');
});

// Admin routes (separate file)
require __DIR__.'/admin.php';