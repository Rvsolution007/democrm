<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use App\Models\Traits\BelongsToCompany;

class Integration extends Model
{
    use BelongsToCompany;

    public const PROVIDERS = ['indiamart', 'facebook', 'whatsapp', 'email'];
    public const STATUSES = ['active', 'inactive', 'error'];

    protected $fillable = [
        'company_id',
        'provider',
        'status',
        'settings',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'settings' => AsEncryptedArrayObject::class, // Encrypted JSON for credentials
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'settings', // Never expose settings in API responses
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Provider-specific setting getters
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    // Status helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    public function markActive(): void
    {
        $this->status = 'active';
        $this->last_error = null;
        $this->save();
    }

    public function markError(string $error): void
    {
        $this->status = 'error';
        $this->last_error = $error;
        $this->save();
    }

    public function updateLastSync(): void
    {
        $this->last_sync_at = now();
        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // IndiaMART specific helpers
    public function getIndiamartApiKey(): ?string
    {
        return $this->getSetting('api_key');
    }

    public function getIndiamartMobile(): ?string
    {
        return $this->getSetting('mobile');
    }

    public function getIndiamartLastFetchedAt(): ?\DateTime
    {
        $timestamp = $this->getSetting('last_fetched_at');
        return $timestamp ? new \DateTime($timestamp) : null;
    }

    public function setIndiamartLastFetchedAt(\DateTime $datetime): void
    {
        $this->setSetting('last_fetched_at', $datetime->format('c'));
    }

    // Facebook specific helpers
    public function getFacebookPageId(): ?string
    {
        return $this->getSetting('page_id');
    }

    public function getFacebookAccessToken(): ?string
    {
        return $this->getSetting('page_access_token');
    }

    public function getFacebookVerifyToken(): ?string
    {
        return $this->getSetting('verify_token');
    }
}
