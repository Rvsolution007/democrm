<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditPack extends Model
{
    protected $fillable = [
        'name',
        'credits',
        'price',
        'description',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Display Helpers ────────────────────────────────────────────────

    public function getPriceFormatted(): string
    {
        return '₹' . number_format($this->price, 0);
    }

    public function getCreditsFormatted(): string
    {
        return number_format($this->credits, 0);
    }

    /**
     * Per-credit cost for comparison display.
     */
    public function getPerCreditCost(): float
    {
        if ($this->credits <= 0) return 0;
        return round($this->price / $this->credits, 2);
    }

    public function getPerCreditLabel(): string
    {
        return '₹' . number_format($this->getPerCreditCost(), 2) . '/credit';
    }
}
