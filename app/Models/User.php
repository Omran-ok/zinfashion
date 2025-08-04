<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'preferred_language',
        'is_active',
        'is_verified',
        'verification_token',
        'last_login'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'password_hash',
        'remember_token',
        'verification_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'last_login' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the password attribute name
     */
    public function getAuthPassword()
    {
        return $this->password_hash ?? $this->password;
    }

    /**
     * Set password attribute
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
    }

    /**
     * Relationships
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class, 'user_id')->where('is_default', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'user_id');
    }

    public function wishlist()
    {
        return $this->belongsToMany(Product::class, 'wishlists', 'user_id', 'product_id')
                    ->withTimestamps()
                    ->withPivot(['notified_on_sale', 'notified_back_in_stock']);
    }

    public function consents()
    {
        return $this->hasMany(UserConsent::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'user_id');
    }

    public function newsletterSubscription()
    {
        return $this->hasOne(NewsletterSubscriber::class, 'email', 'email');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Check if user has given consent
     */
    public function hasConsent($type)
    {
        return $this->consents()
            ->where('consent_type', $type)
            ->where('consent_given', true)
            ->whereNull('withdrawn_date')
            ->exists();
    }

    /**
     * Give consent
     */
    public function giveConsent($type, $version = '1.0', $ipAddress = null)
    {
        return $this->consents()->updateOrCreate(
            [
                'consent_type' => $type,
            ],
            [
                'consent_given' => true,
                'consent_version' => $version,
                'consent_language' => app()->getLocale(),
                'ip_address' => $ipAddress ?? request()->ip(),
                'consent_date' => now(),
                'withdrawn_date' => null,
            ]
        );
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent($type)
    {
        return $this->consents()
            ->where('consent_type', $type)
            ->update(['withdrawn_date' => now()]);
    }

    /**
     * Get cart count
     */
    public function getCartCountAttribute()
    {
        return $this->cartItems()->sum('quantity');
    }

    /**
     * Get wishlist count
     */
    public function getWishlistCountAttribute()
    {
        return $this->wishlist()->count();
    }

    /**
     * Check if product is in wishlist
     */
    public function hasInWishlist($productId)
    {
        return $this->wishlist()->where('product_id', $productId)->exists();
    }

    /**
     * GDPR: Export user data
     */
    public function exportPersonalData()
    {
        return [
            'personal_info' => $this->only([
                'email', 'first_name', 'last_name', 'phone', 
                'date_of_birth', 'preferred_language'
            ]),
            'addresses' => $this->addresses->toArray(),
            'orders' => $this->orders()->with('items')->get()->toArray(),
            'wishlist' => $this->wishlist->map(function ($product) {
                return [
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'added_at' => $product->pivot->created_at
                ];
            }),
            'reviews' => $this->reviews->toArray(),
            'consents' => $this->consents->toArray(),
            'account_created' => $this->created_at,
            'last_login' => $this->last_login,
        ];
    }

    /**
     * GDPR: Anonymize user data
     */
    public function anonymize()
    {
        $this->update([
            'email' => 'deleted_' . $this->user_id . '@deleted.com',
            'password_hash' => 'DELETED',
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'phone' => null,
            'date_of_birth' => null,
            'is_active' => false,
            'is_verified' => false,
        ]);

        // Delete related data
        $this->addresses()->delete();
        $this->consents()->delete();
        $this->cartItems()->delete();
        $this->wishlist()->detach();
        
        // Anonymize orders (keep for legal/tax reasons)
        $this->orders()->update([
            'user_id' => null,
            'guest_email' => 'deleted_order_' . $this->user_id . '@deleted.com'
        ]);
        
        // Anonymize reviews
        $this->reviews()->update(['user_id' => null]);
    }

    /**
     * Send email verification
     */
    public function sendEmailVerificationNotification()
    {
        // Use localized email
        app()->setLocale($this->preferred_language);
        parent::sendEmailVerificationNotification();
    }
}