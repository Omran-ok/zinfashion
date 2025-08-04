<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $primaryKey = 'order_id';
    
    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'order_language',
        'order_status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'customer_notes',
        'admin_notes',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancelled_reason'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            
            if (empty($order->order_language)) {
                $order->order_language = app()->getLocale();
            }
        });

        static::updating(function ($order) {
            // Log status changes
            if ($order->isDirty('order_status')) {
                activity()
                    ->performedOn($order)
                    ->withProperties([
                        'old_status' => $order->getOriginal('order_status'),
                        'new_status' => $order->order_status
                    ])
                    ->log('Order status changed');
                    
                // Update timestamps based on status
                if ($order->order_status === 'completed' && !$order->shipped_at) {
                    $order->shipped_at = now();
                }
                
                if ($order->order_status === 'delivered' && !$order->delivered_at) {
                    $order->delivered_at = now();
                }
                
                if ($order->order_status === 'cancelled' && !$order->cancelled_at) {
                    $order->cancelled_at = now();
                }
            }
        });
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber()
    {
        $prefix = config('app.order_prefix', 'ZF-');
        $date = now()->format('ymd');
        
        $lastOrder = self::whereDate('created_at', today())
            ->where('order_number', 'like', $prefix . $date . '%')
            ->orderBy('order_number', 'desc')
            ->first();
        
        if ($lastOrder) {
            $lastSequence = intval(substr($lastOrder->order_number, -3));
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class, 'order_id');
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id')
            ->where('address_type', 'billing');
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id')
            ->where('address_type', 'shipping');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('order_status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('order_status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Accessors
     */
    public function getCustomerEmailAttribute()
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    public function getCustomerNameAttribute()
    {
        if ($this->user) {
            return $this->user->full_name;
        }
        
        $billingAddress = $this->billingAddress;
        return $billingAddress ? $billingAddress->full_name : 'Guest';
    }

    public function getIsGuestAttribute()
    {
        return is_null($this->user_id);
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->order_status, ['pending', 'processing']);
    }

    public function getCanBeRefundedAttribute()
    {
        return $this->payment_status === 'paid' && 
               !in_array($this->order_status, ['cancelled', 'refunded']);
    }

    public function getItemCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Methods
     */
    public function markAsPaid($transactionId = null)
    {
        $this->update([
            'payment_status' => 'paid',
            'order_status' => 'processing'
        ]);
        
        // Process inventory
        foreach ($this->items as $item) {
            $item->variant->decrement('stock_quantity', $item->quantity);
            
            // Create stock movement
            StockMovement::create([
                'variant_id' => $item->variant_id,
                'movement_type' => 'out',
                'quantity' => $item->quantity,
                'reference_type' => 'order',
                'reference_id' => $this->order_id,
                'notes' => "Order {$this->order_number}"
            ]);
        }
    }

    public function markAsShipped($trackingNumber = null)
    {
        $this->update([
            'order_status' => 'completed',
            'tracking_number' => $trackingNumber,
            'shipped_at' => now()
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'order_status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function cancel($reason = null)
    {
        if (!$this->can_be_cancelled) {
            return false;
        }
        
        $this->update([
            'order_status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => $reason
        ]);
        
        // Restore inventory if already paid
        if ($this->payment_status === 'paid') {
            foreach ($this->items as $item) {
                $item->variant->increment('stock_quantity', $item->quantity);
                
                StockMovement::create([
                    'variant_id' => $item->variant_id,
                    'movement_type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'order_cancelled',
                    'reference_id' => $this->order_id,
                    'notes' => "Order {$this->order_number} cancelled"
                ]);
            }
        }
        
        return true;
    }

    /**
     * Get localized status
     */
    public function getLocalizedStatus($locale = null)
    {
        $locale = $locale ?? $this->order_language ?? app()->getLocale();
        return __('order_status.' . $this->order_status, [], $locale);
    }

    /**
     * Get formatted order for email/invoice
     */
    public function getFormattedOrder($locale = null)
    {
        $locale = $locale ?? $this->order_language ?? app()->getLocale();
        app()->setLocale($locale);
        
        return [
            'order_number' => $this->order_number,
            'date' => $this->created_at->format(__('date.format')),
            'status' => $this->getLocalizedStatus($locale),
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email
            ],
            'billing_address' => $this->billingAddress?->toArray(),
            'shipping_address' => $this->shippingAddress?->toArray(),
            'items' => $this->items->map(function ($item) use ($locale) {
                return [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'color' => $item->color_name,
                    'size' => $item->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price
                ];
            }),
            'subtotal' => $this->subtotal,
            'tax' => $this->tax_amount,
            'shipping' => $this->shipping_cost,
            'total' => $this->total_amount,
            'currency' => config('app.currency', 'EUR'),
            'notes' => $this->customer_notes
        ];
    }
}