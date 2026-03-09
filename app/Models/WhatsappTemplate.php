<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'message_text',
        'media_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
