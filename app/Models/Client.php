<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Traits\BelongsToCompany;

class Client extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const BUSINESS_CATEGORIES = [
        'Agriculture',
        'Solar',
        'E-commerce',
        'Cosmetic',
        'Construction Chemical',
        'Real Estate',
        'Manufacturing',
        'IT / Software',
        'Healthcare',
        'Retail',
        'Education',
        'Hospitality',
        'Automotive',
        'Textile',
        'Finance',
        'Other'
    ];

    protected $fillable = [
        'company_id',
        'business_category',
        'lead_id',
        'created_by_user_id',
        'type',
        'business_name',
        'contact_name',
        'phone',
        'email',
        'gstin',
        'pan',
        'billing_address',
        'shipping_address',
        'credit_limit',
        'outstanding_amount',
        'payment_terms_days',
        'status',
        'notes',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'credit_limit' => 'integer',
        'outstanding_amount' => 'integer',
        'payment_terms_days' => 'integer',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Changed from assignedTo (belongsTo) to assignedUsers (belongsToMany)
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'entity_id')
            ->where('entity_type', 'client');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'entity_id')
            ->where('entity_type', 'client');
    }

    // Helpers
    public function getDisplayNameAttribute(): string
    {
        return $this->business_name ?: $this->contact_name;
    }

    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getCreditLimitInRupeesAttribute(): float
    {
        return $this->credit_limit / 100;
    }

    public function getOutstandingAmountInRupeesAttribute(): float
    {
        return $this->outstanding_amount / 100;
    }

    // Validate GSTIN format (basic)
    public static function isValidGstin(?string $gstin): bool
    {
        if (empty($gstin))
            return true;
        return preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin);
    }

    // Validate PAN format
    public static function isValidPan(?string $pan): bool
    {
        if (empty($pan))
            return true;
        return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
    }
}
