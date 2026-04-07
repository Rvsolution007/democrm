<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'name',
        'gstin',
        'pan',
        'phone',
        'email',
        'logo',
        'address',
        'default_gst_percent',
        'gst_inclusive',
        'quote_prefix',
        'quote_fy_format',
        'terms_and_conditions',
        'language',
        'timezone',
        'status',
    ];

    protected $casts = [
        'address' => 'array',
        'gst_inclusive' => 'boolean',
        'default_gst_percent' => 'integer',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // ─── Ownership & Subscription Relationships ────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the current active subscription.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where('expires_at', '>=', now()->toDateString())
            ->latest('id')
            ->first();
    }

    /**
     * Get the latest subscription (even if expired).
     */
    public function latestSubscription(): ?Subscription
    {
        return $this->subscriptions()->latest('id')->first();
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(AiCreditWallet::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    // ─── Feature & Module Access ───────────────────────────────────

    /**
     * Check if this company has access to a specific feature.
     * Used for feature gating in sidebar, routes, etc.
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) return false;
        return $subscription->hasFeature($feature);
    }

    /**
     * Check if this company has access to a specific module.
     * Normalizes: "whatsapp-connect" → "whatsapp_connect"
     */
    public function hasModuleAccess(string $module): bool
    {
        // Map permission module names to feature names
        $moduleToFeature = [
            'leads' => 'leads',
            'quotes' => 'quotes',
            'invoices' => 'invoices',
            'clients' => 'clients',
            'payments' => 'payments',
            'followups' => 'followups',
            'products' => 'products',
            'categories' => 'categories',
            'catalogue-columns' => 'catalogue_columns',
            'catalogue_columns' => 'catalogue_columns',
            'users' => 'users',
            'roles' => 'roles',
            'activities' => 'activities',
            'profile' => 'profile',
            'reports' => 'reports',
            'settings' => 'settings',
            'whatsapp-connect' => 'whatsapp_connect',
            'whatsapp_connect' => 'whatsapp_connect',
            'whatsapp-campaigns' => 'whatsapp_campaigns',
            'whatsapp_campaigns' => 'whatsapp_campaigns',
            'whatsapp-templates' => 'whatsapp_templates',
            'whatsapp_templates' => 'whatsapp_templates',
            'whatsapp-auto-reply' => 'whatsapp_auto_reply',
            'whatsapp_auto_reply' => 'whatsapp_auto_reply',
            'whatsapp-analytics' => 'whatsapp_analytics',
            'whatsapp_analytics' => 'whatsapp_analytics',
            'whatsapp-extension' => 'whatsapp_connect', // extension = part of connect feature
            'chatflow' => 'chatflow',
            'ai-bot' => 'ai_bot',
            'ai_bot' => 'ai_bot',
            'ai-analytics' => 'ai_bot',
            // Catch-all for production modules (always allowed in all packages)
            'projects' => 'leads',
            'tasks' => 'leads',
            'micro-tasks' => 'leads',
            'micro_tasks' => 'leads',
            'task-followups' => 'leads',
            'task_followups' => 'leads',
            'service-templates' => 'leads',
            'service_templates' => 'leads',
            'vendors' => 'products',
            'purchases' => 'products',
            'purchase-payments' => 'products',
            'purchase_payments' => 'products',
            'integrations' => 'settings',
        ];

        $feature = $moduleToFeature[$module] ?? $module;
        return $this->hasFeature($feature);
    }

    // ─── User Limit Enforcement ───────────────────────────────────

    /**
     * Get the max number of users allowed for this company.
     */
    public function getMaxUsers(): int
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) return 1; // No subscription = only owner
        return $subscription->getMaxUsers();
    }

    /**
     * Get current active user count.
     */
    public function getActiveUserCount(): int
    {
        return $this->users()->where('status', 'active')->count();
    }

    /**
     * Check if company can add more users.
     */
    public function canAddUser(): bool
    {
        return $this->getActiveUserCount() < $this->getMaxUsers();
    }

    /**
     * Get remaining user slots.
     */
    public function getRemainingUserSlots(): int
    {
        return max(0, $this->getMaxUsers() - $this->getActiveUserCount());
    }

    // ─── Subscription Status Helpers ───────────────────────────────

    /**
     * Check if company has any active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Get the package name for this company.
     */
    public function getPackageName(): string
    {
        return $this->activeSubscription()?->package?->name ?? 'No Plan';
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
