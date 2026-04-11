<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;

class Purchase extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'client_id',
        'quote_id',
        'project_id',
        'product_id',
        'purchase_no',
        'date',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }




    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function getDueAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function customFieldValues()
    {
        return $this->hasMany(PurchaseCustomFieldValue::class);
    }

    /**
     * Generate a unique purchase number
     */
    public static function generatePurchaseNumber(Company $company): string
    {
        $prefix = $company->settings['purchase_prefix'] ?? 'PUR-';

        $currentYear = date('Y');
        $nextYear = date('y', strtotime('+1 year'));
        $dateFormat = "{$currentYear}-{$nextYear}-";
        $prefix = $prefix . $dateFormat;

        $lastPurchase = self::withTrashed()
            ->where('company_id', $company->id)
            ->where('purchase_no', 'like', $prefix . '%')
            ->orderBy('purchase_no', 'desc')
            ->first();

        if (!$lastPurchase) {
            $number = 1;
        } else {
            $lastNumber = str_replace($prefix, '', $lastPurchase->purchase_no);
            $number = (int) $lastNumber + 1;
        }

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
