<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiCreditWallet extends Model
{
    protected $fillable = [
        'company_id',
        'balance',
        'total_purchased',
        'total_consumed',
        'low_balance_threshold',
        'low_balance_alert_sent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_purchased' => 'decimal:2',
        'total_consumed' => 'decimal:2',
        'low_balance_alert_sent' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class, 'wallet_id');
    }

    // ─── Credit Operations ──────────────────────────────────────────────

    /**
     * Add credits to wallet (recharge, bonus).
     */
    public function addCredits(
        float $credits,
        string $type,
        ?string $description = null,
        ?float $amountPaid = null,
        ?string $paymentMethod = null,
        ?string $razorpayPaymentId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): AiCreditTransaction {
        $this->balance += $credits;
        if ($type === 'recharge' || $type === 'bonus') {
            $this->total_purchased += $credits;
        }

        // Reset low balance alert if recharged above threshold
        if ($this->balance >= $this->low_balance_threshold) {
            $this->low_balance_alert_sent = false;
        }

        $this->save();

        return $this->transactions()->create([
            'company_id' => $this->company_id,
            'type' => $type,
            'credits' => $credits,
            'balance_after' => $this->balance,
            'amount_paid' => $amountPaid,
            'ai_tokens_used' => null,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payment_method' => $paymentMethod,
            'razorpay_payment_id' => $razorpayPaymentId,
        ]);
    }

    /**
     * Deduct credits from wallet (AI consumption).
     * Returns the transaction record, or null if insufficient balance.
     */
    public function deductCredits(
        float $credits,
        string $referenceType = 'chat_message',
        ?int $referenceId = null,
        ?int $aiTokensUsed = null,
        ?string $description = null
    ): ?AiCreditTransaction {
        if ($this->balance < $credits) {
            return null; // Insufficient balance
        }

        $this->balance -= $credits;
        $this->total_consumed += $credits;
        $this->save();

        return $this->transactions()->create([
            'company_id' => $this->company_id,
            'type' => 'consumption',
            'credits' => -$credits, // negative for consumption
            'balance_after' => $this->balance,
            'amount_paid' => null,
            'ai_tokens_used' => $aiTokensUsed,
            'description' => $description ?? "AI usage: {$aiTokensUsed} tokens",
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payment_method' => null,
            'razorpay_payment_id' => null,
        ]);
    }

    // ─── Balance Checks ─────────────────────────────────────────────────

    /**
     * Check if wallet has enough credits to operate.
     */
    public function canOperate(): bool
    {
        $minCredits = Setting::getGlobalValue('ai_credits', 'min_credits_to_operate', 10);
        return $this->balance >= $minCredits;
    }

    /**
     * Check if balance is below low threshold.
     */
    public function isLowBalance(): bool
    {
        return $this->balance < $this->low_balance_threshold;
    }

    /**
     * Check if balance needs an alert (low + not yet alerted).
     */
    public function needsLowBalanceAlert(): bool
    {
        return $this->isLowBalance() && !$this->low_balance_alert_sent;
    }

    /**
     * Mark low balance alert as sent.
     */
    public function markAlertSent(): void
    {
        $this->update(['low_balance_alert_sent' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    public function getBalanceFormatted(): string
    {
        return number_format($this->balance, 0);
    }

    /**
     * Calculate credits to deduct based on AI tokens used.
     */
    public static function calculateCredits(int $aiTokensUsed): float
    {
        $rate = Setting::getGlobalValue('ai_credits', 'credits_per_1k_tokens', 1.2);
        return round(($aiTokensUsed / 1000) * $rate, 2);
    }
}
