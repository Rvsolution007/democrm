<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotePayment extends Model
{
    protected $fillable = [
        'quote_id',
        'user_id',
        'amount',
        'payment_type',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'datetime',
    ];

    public const PAYMENT_TYPES = ['cash', 'online', 'cheque', 'upi', 'bank_transfer'];

    // Relationships
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getAmountInRupeesAttribute(): float
    {
        return $this->amount / 100;
    }
}
