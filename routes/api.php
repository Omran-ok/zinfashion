<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    ProductController,
    CartController,
    OrderController,
    UserController,
    CategoryController,
    WishlistController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    });
    
    // Products (public)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/search', [ProductController::class, 'search']);
    });
    
    // Categories (public)
    Route::get('/categories', [ProductController::class, 'categories']);
    
    // Cart (session-based for guests)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::patch('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'remove']);
        Route::post('/clear', [CartController::class, 'clear']);
    });
    
    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });
        
        // User profile
        Route::prefix('user')->group(function () {
            Route::get('/profile', [UserController::class, 'profile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::put('/password', [UserController::class, 'updatePassword']);
            Route::put('/language', [UserController::class, 'updateLanguage']);
            
            // Addresses
            Route::get('/addresses', [UserController::class, 'addresses']);
            Route::post('/addresses', [UserController::class, 'createAddress']);
            Route::put('/addresses/{id}', [UserController::class, 'updateAddress']);
            Route::delete('/addresses/{id}', [UserController::class, 'deleteAddress']);
            Route::post('/addresses/{id}/default', [UserController::class, 'setDefaultAddress']);
        });
        
        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'create']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
            Route::get('/{id}/invoice', [OrderController::class, 'invoice']);
        });
        
        // Wishlist
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/{productId}', [WishlistController::class, 'add']);
            Route::delete('/{productId}', [WishlistController::class, 'remove']);
            Route::post('/clear', [WishlistController::class, 'clear']);
        });
        
        // GDPR
        Route::prefix('gdpr')->group(function () {
            Route::get('/consents', [UserController::class, 'getConsents']);
            Route::post('/consents', [UserController::class, 'updateConsents']);
            Route::post('/export', [UserController::class, 'requestDataExport']);
            Route::post('/delete', [UserController::class, 'requestAccountDeletion']);
        });
        
        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [UserController::class, 'notifications']);
            Route::post('/{id}/read', [UserController::class, 'markNotificationRead']);
            Route::post('/read-all', [UserController::class, 'markAllNotificationsRead']);
        });
    });
    
    // Checkout (can be used by guests with session)
    Route::prefix('checkout')->group(function () {
        Route::post('/calculate', [CheckoutController::class, 'calculate']);
        Route::post('/process', [CheckoutController::class, 'process']);
        Route::post('/payment/confirm', [CheckoutController::class, 'confirmPayment']);
    });
    
    // Localization
    Route::get('/translations/{locale}', function ($locale) {
        if (!in_array($locale, ['de', 'en', 'ar'])) {
            return response()->json(['error' => 'Invalid locale'], 400);
        }
        
        $translations = trans('*', [], $locale);
        
        return response()->json([
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
            'translations' => $translations
        ]);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0')
    ]);
});