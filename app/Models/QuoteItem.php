<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'product_name',
        'description',
        'hsn_code',
        'qty',
        'rate',
        'discount',
        'unit',
        'unit_price',
        'gst_percent',
        'gst_amount',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'integer',
        'rate' => 'integer',
        'discount' => 'integer',
        'unit_price' => 'integer',
        'gst_percent' => 'integer',
        'gst_amount' => 'integer',
        'line_total' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate GST and line total on save
        static::saving(function ($item) {
            $item->calculateTotals();
        });
    }

    // Relationships
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Calculate GST and line total
    public function calculateTotals(): void
    {
        $baseAmount = ($this->rate ?: $this->unit_price) * $this->qty;
        $netAmount = $baseAmount - $this->discount;
        $this->gst_amount = (int) round($netAmount * ($this->gst_percent / 100));
        $this->line_total = $netAmount + $this->gst_amount;
    }

    // Price helpers
    public function getUnitPriceInRupeesAttribute(): float
    {
        return $this->unit_price / 100;
    }

    public function getGstAmountInRupeesAttribute(): float
    {
        return $this->gst_amount / 100;
    }

    public function getLineTotalInRupeesAttribute(): float
    {
        return $this->line_total / 100;
    }
}
