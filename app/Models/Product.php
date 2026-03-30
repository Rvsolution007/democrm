<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToCompany;

class Product extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $appends = ['display_name'];

    public static $uniqueColumnCache = [];

    public function getDisplayNameAttribute()
    {
        if (!$this->company_id) return $this->name;

        if (!array_key_exists($this->company_id, self::$uniqueColumnCache)) {
            self::$uniqueColumnCache[$this->company_id] = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
                ->where('is_unique', true)->first();
        }

        $uc = self::$uniqueColumnCache[$this->company_id];
        if ($uc) {
            if ($uc->is_system) {
                return $this->{$uc->slug} ?: $this->name;
            } else {
                if ($this->relationLoaded('customValues')) {
                    $val = $this->customValues->where('column_id', $uc->id)->first();
                } else {
                    $val = \App\Models\CatalogueCustomValue::where('product_id', $this->id)->where('column_id', $uc->id)->first();
                }

                if ($val) {
                    $decoded = json_decode($val->value, true);
                    return is_array($decoded) ? implode(', ', $decoded) : $val->value;
                }
            }
        }
        
        return $this->name;
    }

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'category_id',
        'sku',
        'name',
        'description',
        'unit',
        'mrp',
        'sale_price',
        'gst_percent',
        'hsn_code',
        'stock_qty',
        'min_stock_qty',
        'image',
        'specifications',
        'status',
        'is_purchase_enabled',
    ];

    protected $casts = [
        'mrp' => 'integer',
        'sale_price' => 'integer',
        'gst_percent' => 'integer',
        'stock_qty' => 'integer',
        'min_stock_qty' => 'integer',
        'specifications' => 'array',
        'is_purchase_enabled' => 'boolean',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customValues(): HasMany
    {
        return $this->hasMany(CatalogueCustomValue::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(ProductCombo::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function activeVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->where('status', 'active');
    }

    // Price helpers (convert paise to rupees)
    public function getMrpInRupeesAttribute(): float
    {
        return $this->mrp / 100;
    }

    public function getSalePriceInRupeesAttribute(): float
    {
        return $this->sale_price / 100;
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock_qty;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
