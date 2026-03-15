<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAutoReplyLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'rule_id',
        'instance_name',
        'phone_number',
        'incoming_message',
        'reply_template_id',
        'status',
        'skip_reason',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rule()
    {
        return $this->belongsTo(WhatsappAutoReplyRule::class, 'rule_id');
    }

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'reply_template_id');
    }
}
