<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;

class Vendor extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'address',
        'status',
        'has_purchase_section',
    ];

    protected $casts = [
        'has_purchase_section' => 'boolean',
    ];

    public function customFields()
    {
        return $this->hasMany(VendorCustomField::class)->orderBy('sort_order');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
