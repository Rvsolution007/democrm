<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
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

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
