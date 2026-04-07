<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'company_id',
        'package_id',
        'status',
        'billing_cycle',
        'amount_paid',
        'max_users',
        'starts_at',
        'expires_at',
        'trial_ends_at',
        'cancelled_at',
        'custom_overrides',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'trial_ends_at' => 'date',
        'cancelled_at' => 'datetime',
        'custom_overrides' => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Status Checks ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']) && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if within grace period (3 days after expiry).
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->expires_at) return false;
        return $this->expires_at->isPast()
            && $this->expires_at->addDays(3)->isFuture();
    }

    /**
     * Days remaining in subscription.
     */
    public function daysRemaining(): int
    {
        if (!$this->expires_at) return 0;
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if expiring soon (within 7 days).
     */
    public function isExpiringSoon(): bool
    {
        return $this->daysRemaining() <= 7 && $this->daysRemaining() > 0;
    }

    // ─── Feature & Limit Checks ─────────────────────────────────────────

    /**
     * Check if a feature is available (package feature + custom override).
     */
    public function hasFeature(string $feature): bool
    {
        // Check custom overrides first (SA can enable/disable per admin)
        $overrides = $this->custom_overrides ?? [];
        $normalized = str_replace('-', '_', $feature);
        if (isset($overrides[$normalized])) {
            return (bool) $overrides[$normalized];
        }

        // Fall back to package features
        return $this->package?->hasFeature($normalized) ?? false;
    }

    /**
     * Get effective max users (custom override or package default).
     */
    public function getMaxUsers(): int
    {
        return $this->max_users ?? $this->package?->default_max_users ?? 3;
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial'])
            ->where('expires_at', '>=', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now()->toDateString());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereIn('status', ['active', 'trial'])
            ->where('expires_at', '>=', now()->toDateString())
            ->where('expires_at', '<=', now()->addDays($days)->toDateString());
    }
}
