<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseCustomFieldValue extends Model
{
    protected $fillable = [
        'purchase_id',
        'vendor_custom_field_id',
        'value',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function customField()
    {
        return $this->belongsTo(VendorCustomField::class, 'vendor_custom_field_id');
    }
}
