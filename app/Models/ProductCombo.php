<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCombo extends Model
{
    protected $fillable = [
        'product_id',
        'column_id',
        'selected_values',
        'sort_order',
    ];

    protected $casts = [
        'selected_values' => 'array',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(CatalogueCustomColumn::class, 'column_id');
    }
}
