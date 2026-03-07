<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class ExternalLead extends Model
{
    use BelongsToCompany;

    public const PROVIDERS = ['indiamart', 'facebook'];

    protected $fillable = [
        'company_id',
        'provider',
        'external_id',
        'lead_id',
        'payload',
        'received_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
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

    // Check if lead already exists by external ID
    public static function exists(int $companyId, string $provider, string $externalId): bool
    {
        return static::where('company_id', $companyId)
            ->where('provider', $provider)
            ->where('external_id', $externalId)
            ->exists();
    }

    // Find or create external lead
    public static function findOrCreateForExternal(
        int $companyId,
        string $provider,
        string $externalId,
        array $payload
    ): static {
        return static::firstOrCreate(
            [
                'company_id' => $companyId,
                'provider' => $provider,
                'external_id' => $externalId,
            ],
            [
                'payload' => $payload,
                'received_at' => now(),
            ]
        );
    }

    // Link to lead
    public function linkToLead(Lead $lead): void
    {
        $this->lead_id = $lead->id;
        $this->save();
    }

    // Scopes
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('lead_id');
    }
}
