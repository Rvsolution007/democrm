<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'lead_id',
        'client_id',
        'phone_number',
        'status',
        'error_message',
        'sent_at',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsappCampaign::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
