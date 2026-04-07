<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['company_id', 'scope', 'group', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a setting value by group and key.
     * First checks company-level setting, then falls back to global setting.
     */
    public static function getValue(string $group, string $key, $default = null, ?int $companyId = null)
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()->company_id : 1);

        // 1. Try company-specific setting first
        $setting = static::where('company_id', $companyId)
            ->where('scope', 'company')
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if ($setting) {
            return $setting->value;
        }

        // 2. Fall back to global setting (any company_id, scope=global)
        $globalSetting = static::where('scope', 'global')
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if ($globalSetting) {
            return $globalSetting->value;
        }

        return $default;
    }

    /**
     * Set a company-level setting value by group and key
     */
    public static function setValue(string $group, string $key, $value, ?int $companyId = null)
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()->company_id : 1);
        return static::updateOrCreate(
            ['company_id' => $companyId, 'scope' => 'company', 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }

    // ─── Global Settings (Super Admin Only) ──────────────────────────

    /**
     * Get a global setting value (shared across all companies).
     * These are configured by Super Admin only.
     */
    public static function getGlobalValue(string $group, string $key, $default = null)
    {
        $setting = static::where('scope', 'global')
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a global setting value (Super Admin only).
     * Uses company_id=1 as the storage anchor.
     */
    public static function setGlobalValue(string $group, string $key, $value)
    {
        $companyId = 1; // Global settings stored under company_id=1
        return static::updateOrCreate(
            ['company_id' => $companyId, 'scope' => 'global', 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if a setting exists as global scope.
     */
    public static function isGlobalSetting(string $group, string $key): bool
    {
        return static::where('scope', 'global')
            ->where('group', $group)
            ->where('key', $key)
            ->exists();
    }
}
