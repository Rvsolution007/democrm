<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToCompany;

class Quote extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    protected $fillable = [
        'company_id',
        'client_id',
        'lead_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'quote_no',
        'date',
        'valid_till',
        'subtotal',
        'discount',
        'gst_total',
        'grand_total',
        'status',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'notes',
        'terms_and_conditions',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_till' => 'date',
        'subtotal' => 'integer',
        'discount' => 'integer',
        'gst_total' => 'integer',
        'grand_total' => 'integer',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'entity_id')
            ->where('entity_type', 'quote');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(QuotePayment::class);
    }

    // Payment helpers
    public function getPaidAmountAttribute(): int
    {
        return $this->payments()->sum('amount');
    }

    public function getPaidAmountInRupeesAttribute(): float
    {
        return $this->paid_amount / 100;
    }

    public function getDueAmountInRupeesAttribute(): float
    {
        return $this->grand_total_in_rupees - $this->paid_amount_in_rupees;
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->valid_till && $this->valid_till->isPast() && !$this->isAccepted());
    }

    // Price helpers (paise to rupees)
    public function getSubtotalInRupeesAttribute(): float
    {
        return $this->subtotal / 100;
    }

    public function getDiscountInRupeesAttribute(): float
    {
        return $this->discount / 100;
    }

    public function getGstTotalInRupeesAttribute(): float
    {
        return ($this->gst_total ?: $this->tax_amount ?: 0) / 100;
    }

    public function getGrandTotalInRupeesAttribute(): float
    {
        $tax = $this->gst_total ?: $this->tax_amount ?: 0;
        $total = $this->subtotal - $this->discount + $tax;
        return $total / 100;
    }

    // Recalculate totals from items
    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('line_total');

        $this->subtotal = $subtotal;
        $this->grand_total = $subtotal - $this->discount + $this->gst_total;
        $this->save();
    }

    // Generate quote number with FY pattern
    public static function generateQuoteNumber(Company $company): string
    {
        $now = now();

        // Determine financial year (April to March)
        if ($now->month >= 4) {
            $fyStart = $now->year;
            $fyEnd = $now->year + 1;
        } else {
            $fyStart = $now->year - 1;
            $fyEnd = $now->year;
        }

        // Format FY based on company settings
        switch ($company->quote_fy_format) {
            case 'YYYY-YY':
                $fy = $fyStart . '-' . substr($fyEnd, -2);
                break;
            case 'YYYY':
                $fy = (string) $fyStart;
                break;
            default: // YY-YY
                $fy = substr($fyStart, -2) . '-' . substr($fyEnd, -2);
        }

        // Get next sequence number
        $lastQuote = static::withTrashed()
            ->where('company_id', $company->id)
            ->where('quote_no', 'like', $company->quote_prefix . '-' . $fy . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastQuote) {
            $parts = explode('-', $lastQuote->quote_no);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%06d', $company->quote_prefix, $fy, $sequence);
    }
}
