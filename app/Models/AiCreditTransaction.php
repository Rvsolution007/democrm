<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'wallet_id',
        'type',
        'credits',
        'balance_after',
        'amount_paid',
        'ai_tokens_used',
        'description',
        'reference_type',
        'reference_id',
        'payment_method',
        'razorpay_payment_id',
    ];

    protected $casts = [
        'credits' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AiCreditWallet::class, 'wallet_id');
    }

    // ─── Type Checks ────────────────────────────────────────────────────

    public function isRecharge(): bool
    {
        return $this->type === 'recharge';
    }

    public function isConsumption(): bool
    {
        return $this->type === 'consumption';
    }

    public function isBonus(): bool
    {
        return $this->type === 'bonus';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isAdjustment(): bool
    {
        return $this->type === 'adjustment';
    }

    // ─── Display Helpers ────────────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'recharge' => 'Recharge',
            'consumption' => 'AI Usage',
            'refund' => 'Refund',
            'adjustment' => 'Adjustment',
            'bonus' => 'Bonus',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'recharge', 'bonus' => 'badge-success',
            'consumption' => 'badge-warning',
            'refund' => 'badge-info',
            'adjustment' => 'badge-secondary',
            default => 'badge-default',
        };
    }

    public function getCreditsFormatted(): string
    {
        $prefix = $this->credits >= 0 ? '+' : '';
        return $prefix . number_format($this->credits, 2);
    }

    public function getAmountFormatted(): string
    {
        if (!$this->amount_paid) return '—';
        return '₹' . number_format($this->amount_paid, 2);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeRecharges($query)
    {
        return $query->whereIn('type', ['recharge', 'bonus']);
    }

    public function scopeConsumptions($query)
    {
        return $query->where('type', 'consumption');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
