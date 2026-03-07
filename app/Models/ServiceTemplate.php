<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class ServiceTemplate extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'name',
        'tasks_json',
        'is_active',
    ];

    protected $casts = [
        'tasks_json' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Get tasks as array
    public function getTaskSteps(): array
    {
        return $this->tasks_json ?? [];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Find template for a product
    public static function findForProduct(int $productId): ?self
    {
        return static::where('product_id', $productId)
            ->where('is_active', true)
            ->first();
    }
}
