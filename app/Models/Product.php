<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class Product extends Model
{
    use SoftDeletes, BelongsToCompany;

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
