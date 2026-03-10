<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Company;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'template_code',
        'type',
        'message_text',
        'media_path',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->template_code)) {
                $template->template_code = strtoupper(Str::random(8));
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
