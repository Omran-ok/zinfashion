<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create payment intent for Stripe
     */
    public function createPaymentIntent(Order $order)
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->formatAmount($order->total_amount),
                'currency' => strtolower(config('app.currency', 'EUR')),
                'metadata' => [
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                ],
                'description' => "Order {$order->order_number}",
                'receipt_email' => $order->customer_email,
                'shipping' => $this->formatShippingInfo($order),
            ]);

            // Store payment intent ID
            $order->update(['payment_intent_id' => $paymentIntent->id]);

            // Create transaction record
            PaymentTransaction::create([
                'order_id' => $order->order_id,
                'transaction_type' => 'payment',
                'payment_method' => 'stripe',
                'transaction_id' => $paymentIntent->id,
                'amount' => $order->total_amount,
                'currency' => config('app.currency', 'EUR'),
                'status' => 'pending',
                'gateway_response' => $paymentIntent->toArray(),
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            throw new \Exception(__('messages.payment_initialization_failed'));
        }
    }

    /**
     * Confirm payment status
     */
    public function confirmPayment($paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            $transaction = PaymentTransaction::where('transaction_id', $paymentIntentId)->first();
            
            if ($transaction) {
                $transaction->update([
                    'status' => $this->mapStripeStatus($paymentIntent->status),
                    'gateway_response' => $paymentIntent->toArray(),
                ]);
            }

            if ($paymentIntent->status === 'succeeded') {
                $order = Order::where('payment_intent_id', $paymentIntentId)->first();
                
                if ($order) {
                    $order->markAsPaid($paymentIntentId);
                    
                    return [
                        'status' => 'succeeded',
                        'order' => $order,
                        'id' => $paymentIntentId
                    ];
                }
            }

            return [
                'status' => $paymentIntent->status,
                'id' => $paymentIntentId
            ];
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            throw new \Exception(__('messages.payment_confirmation_failed'));
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook($payload, $signature)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        
        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed');
            return false;
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
                
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
                
            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;
        }

        return true;
    }

    /**
     * Process refund
     */
    public function processRefund(Order $order, $amount = null)
    {
        try {
            if (!$order->payment_intent_id) {
                throw new \Exception('No payment intent found for this order');
            }

            $paymentIntent = PaymentIntent::retrieve($order->payment_intent_id);
            
            if (!$paymentIntent->charges->data) {
                throw new \Exception('No charges found for this payment');
            }

            $charge = $paymentIntent->charges->data[0];
            
            $refundData = [
                'charge' => $charge->id,
                'metadata' => [
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                ]
            ];

            // If amount specified, partial refund
            if ($amount !== null) {
                $refundData['amount'] = $this->formatAmount($amount);
            }

            $refund = \Stripe\Refund::create($refundData);

            // Create refund transaction
            PaymentTransaction::create([
                'order_id' => $order->order_id,
                'transaction_type' => 'refund',
                'payment_method' => 'stripe',
                'transaction_id' => $refund->id,
                'amount' => $amount ?? $order->total_amount,
                'currency' => config('app.currency', 'EUR'),
                'status' => 'completed',
                'gateway_response' => $refund->toArray(),
            ]);

            // Update order status
            $order->update([
                'payment_status' => $amount === null ? 'refunded' : 'partially_refunded'
            ]);

            return $refund;
        } catch (\Exception $e) {
            Log::error('Refund processing failed: ' . $e->getMessage());
            throw new \Exception(__('messages.refund_failed'));
        }
    }

    /**
     * Format amount for Stripe (convert to cents)
     */
    private function formatAmount($amount)
    {
        return (int) ($amount * 100);
    }

    /**
     * Format shipping information for Stripe
     */
    private function formatShippingInfo(Order $order)
    {
        $shippingAddress = $order->shippingAddress;
        
        if (!$shippingAddress) {
            return null;
        }

        return [
            'name' => $shippingAddress->first_name . ' ' . $shippingAddress->last_name,
            'address' => [
                'line1' => $shippingAddress->street_address,
                'city' => $shippingAddress->city,
                'postal_code' => $shippingAddress->postal_code,
                'country' => 'DE',
            ],
            'phone' => $shippingAddress->phone,
        ];
    }

    /**
     * Map Stripe status to internal status
     */
    private function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'requires_payment_method' => 'pending',
            'requires_confirmation' => 'pending',
            'requires_action' => 'pending',
            'processing' => 'processing',
            'requires_capture' => 'processing',
            'canceled' => 'cancelled',
            'succeeded' => 'completed',
        ];

        return $statusMap[$stripeStatus] ?? 'pending';
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($order && $order->payment_status !== 'paid') {
            $order->markAsPaid($paymentIntent->id);
            
            // Send order confirmation
            try {
                \Mail::to($order->customer_email)
                    ->send(new \App\Mail\OrderConfirmation($order));
            } catch (\Exception $e) {
                Log::error('Failed to send order confirmation: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle failed payment intent
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($order) {
            $order->update(['payment_status' => 'failed']);
            
            $transaction = PaymentTransaction::where('transaction_id', $paymentIntent->id)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => 'failed',
                    'gateway_response' => $paymentIntent->toArray(),
                ]);
            }
        }
    }

    /**
     * Handle charge refunded
     */
    private function handleChargeRefunded($charge)
    {
        // Find order by charge metadata
        if (isset($charge->metadata->order_id)) {
            $order = Order::find($charge->metadata->order_id);
            
            if ($order) {
                $refundAmount = $charge->amount_refunded / 100;
                
                if ($refundAmount >= $order->total_amount) {
                    $order->update(['payment_status' => 'refunded']);
                } else {
                    $order->update(['payment_status' => 'partially_refunded']);
                }
            }
        }
    }

    /**
     * Get payment methods for checkout
     */
    public function getAvailablePaymentMethods()
    {
        return [
            [
                'id' => 'stripe',
                'name' => __('payment.credit_card'),
                'description' => __('payment.credit_card_description'),
                'icon' => 'credit-card',
                'active' => true,
                'requires_billing' => true,
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'description' => __('payment.paypal_description'),
                'icon' => 'paypal',
                'active' => false, // Not implemented yet
                'requires_billing' => false,
            ],
            [
                'id' => 'invoice',
                'name' => __('payment.invoice'),
                'description' => __('payment.invoice_description'),
                'icon' => 'document',
                'active' => true,
                'requires_billing' => true,
            ],
        ];
    }
}