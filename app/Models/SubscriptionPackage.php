<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'default_max_users',
        'max_leads_per_month',
        'features',
        'module_permissions',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'module_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'package_id');
    }

    // ─── Feature Checks ─────────────────────────────────────────────────

    /**
     * Check if this package includes a specific feature/module.
     */
    public function hasFeature(string $feature): bool
    {
        $modules = $this->module_permissions ?? [];
        return !empty($modules[$feature]);
    }

    /**
     * Check if this package includes a specific module for sidebar/route gating.
     * Normalizes module names: "whatsapp-connect" → "whatsapp_connect"
     */
    public function hasModule(string $module): bool
    {
        $normalized = str_replace('-', '_', $module);
        return $this->hasFeature($normalized);
    }

    /**
     * Get all enabled feature slugs.
     */
    public function getEnabledFeatures(): array
    {
        $modules = $this->module_permissions ?? [];
        return array_keys(array_filter($modules));
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    /**
     * Get the effective price label for display.
     */
    public function getPriceLabel(string $cycle = 'monthly'): string
    {
        $price = $cycle === 'yearly' ? $this->yearly_price : $this->monthly_price;
        if ($price <= 0) return 'Free';
        return '₹' . number_format((float) $price, 0);
    }

    /**
     * Calculate monthly effective price (for yearly comparison display).
     */
    public function getMonthlyEffectivePrice(): float
    {
        if ($this->yearly_price > 0) {
            return round($this->yearly_price / 12, 2);
        }
        return (float) $this->monthly_price;
    }
}
