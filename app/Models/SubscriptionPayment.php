<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'company_id',
        'amount',
        'payment_method',
        'transaction_id',
        'razorpay_order_id',
        'razorpay_signature',
        'status',
        'payment_meta',
        'admin_notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_meta' => 'array',
        'verified_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Status Checks ──────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isRazorpay(): bool
    {
        return $this->payment_method === 'razorpay';
    }

    public function isManual(): bool
    {
        return in_array($this->payment_method, ['manual', 'bank_transfer', 'upi', 'cash']);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    public function getAmountFormatted(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getMethodLabel(): string
    {
        return match ($this->payment_method) {
            'razorpay' => 'Razorpay',
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'cash' => 'Cash',
            default => 'Manual',
        };
    }
}
