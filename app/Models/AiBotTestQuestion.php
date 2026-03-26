<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class AiBotTestQuestion extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'question',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
