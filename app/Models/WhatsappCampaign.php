<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToCompany;

class WhatsappCampaign extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'template_id',
        'target_stage',
        'target_product_id',
        'total_recipients',
        'total_sent',
        'total_failed',
        'status',
        'error_message',
    ];

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    public function recipients()
    {
        return $this->hasMany(WhatsappCampaignRecipient::class, 'campaign_id');
    }
}
