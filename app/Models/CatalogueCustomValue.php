<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogueCustomValue extends Model
{
    protected $fillable = [
        'product_id',
        'column_id',
        'value',
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
