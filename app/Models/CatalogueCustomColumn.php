<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Traits\BelongsToCompany;

class CatalogueCustomColumn extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'type',
        'options',
        'is_required',
        'is_unique',
        'is_combo',
        'is_system',
        'is_active',
        'connected_modules',
        'show_on_list',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'connected_modules' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_combo' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'show_on_list' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($column) {
            if (empty($column->slug)) {
                $column->slug = Str::slug($column->name, '_');
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CatalogueCustomValue::class, 'column_id');
    }

    public function productCombos(): HasMany
    {
        return $this->hasMany(ProductCombo::class, 'column_id');
    }

    public function chatflowSteps(): HasMany
    {
        return $this->hasMany(ChatflowStep::class, 'linked_column_id');
    }

    // Helpers
    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multiselect']);
    }

    public function getOptionsArray(): array
    {
        return $this->options ?? [];
    }
}
