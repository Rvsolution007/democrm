<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\BelongsToCompany;

class Lead extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    public const SOURCES = ['walk-in', 'reference', 'indiamart', 'facebook', 'website', 'whatsapp', 'call', 'other'];

    protected $fillable = [
        'company_id',
        'assigned_to_user_id',
        'created_by_user_id',
        'source',
        'source_provider',
        'source_external_id',
        'raw_source_payload',
        'name',
        'phone',
        'email',
        'city',
        'state',
        'stage',
        'expected_value',
        'next_follow_up_at',
        'notes',
        'query_type',
        'query_message',
        'product_name',
    ];

    protected $casts = [
        'expected_value' => 'integer',
        'next_follow_up_at' => 'datetime',
        'raw_source_payload' => 'array',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'entity_id')
            ->where('entity_type', 'lead');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'entity_id')
            ->where('entity_type', 'lead');
    }

    public function externalLead(): HasOne
    {
        return $this->hasOne(ExternalLead::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lead_product')
            ->withPivot('quantity', 'price', 'discount', 'description')
            ->withTimestamps();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class)->latest();
    }

    // Stage helpers
    public static function getDynamicStages(): array
    {
        return Setting::getValue('leads', 'stages', self::STAGES);
    }

    public function isOpen(): bool
    {
        return !in_array($this->stage, ['won', 'lost']);
    }

    public function isWon(): bool
    {
        return $this->stage === 'won';
    }

    public function isLost(): bool
    {
        return $this->stage === 'lost';
    }

    public function hasOverdueFollowUp(): bool
    {
        return $this->next_follow_up_at && $this->next_follow_up_at->isPast();
    }

    // Price helper
    public function getExpectedValueInRupeesAttribute(): float
    {
        return $this->expected_value / 100;
    }

    public function getTotalAmountAttribute(): float
    {
        if (!$this->relationLoaded('products')) {
            $this->load('products');
        }

        $total = 0;
        foreach ($this->products as $product) {
            $price = $product->pivot->price ?? 0;
            $discount = $product->pivot->discount ?? 0;
            $quantity = $product->pivot->quantity ?? 1;
            $total += (($price - $discount) * $quantity) / 100;
        }

        return $total;
    }
}
