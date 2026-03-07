<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorCustomField extends Model
{
    protected $fillable = [
        'vendor_id',
        'field_name',
        'field_type',
        'field_options',
        'sort_order',
    ];

    protected $casts = [
        'field_options' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function values()
    {
        return $this->hasMany(PurchaseCustomFieldValue::class);
    }
}
