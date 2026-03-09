<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['company_id', 'group', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a setting value by group and key
     */
    public static function getValue(string $group, string $key, $default = null, ?int $companyId = null)
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()->company_id : 1);
        $setting = static::where('company_id', $companyId)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by group and key
     */
    public static function setValue(string $group, string $key, $value, ?int $companyId = null)
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()->company_id : 1);
        return static::updateOrCreate(
            ['company_id' => $companyId, 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }
}
