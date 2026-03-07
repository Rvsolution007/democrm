<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class LeadSource extends Model
{
    use BelongsToCompany;

    public const SOURCE_TYPES = ['indiamart', 'facebook', 'website', 'whatsapp', 'other'];

    protected $fillable = [
        'company_id',
        'source_type',
        'name',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Config helpers
    public function getFieldMapping(): array
    {
        return $this->config['field_mapping'] ?? [];
    }

    public function setFieldMapping(array $mapping): void
    {
        $config = $this->config ?? [];
        $config['field_mapping'] = $mapping;
        $this->config = $config;
        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    // Default field mappings for providers
    public static function getDefaultFieldMapping(string $sourceType): array
    {
        return match ($sourceType) {
            'indiamart' => [
                'SENDER_NAME' => 'name',
                'SENDERMOBILE' => 'phone',
                'SENDEREMAIL' => 'email',
                'SENDER_CITY' => 'city',
                'SENDER_STATE' => 'state',
                'QUERY_TYPE' => 'query_type',
                'QUERY_MESSAGE' => 'query_message',
                'QUERY_PRODUCT_NAME' => 'product_name',
            ],
            'facebook' => [
                'full_name' => 'name',
                'phone_number' => 'phone',
                'email' => 'email',
                'city' => 'city',
                'state' => 'state',
            ],
            default => [],
        };
    }
}
