<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAutoReplyBlacklist extends Model
{
    protected $table = 'whatsapp_auto_reply_blacklist';

    protected $fillable = [
        'company_id',
        'user_id',
        'phone_number',
        'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
