<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'combination',
        'combination_key',
        'price',
        'description',
        'status',
    ];

    protected $casts = [
        'combination' => 'array',
        'price' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Helpers
    public function getPriceInRupeesAttribute(): float
    {
        return $this->price / 100;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Generate a combination_key from a combination array
     * e.g., {"finish":"Black","size":"Large"} → "black|large"
     */
    public static function generateKey(array $combination): string
    {
        $values = array_map(function ($v) {
            return strtolower(trim($v));
        }, array_values($combination));

        sort($values); // Consistent ordering
        return implode('|', $values);
    }
}
