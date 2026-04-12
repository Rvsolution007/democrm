<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAutoReplyRule extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'instance_name',
        'name',
        'match_type',
        'keywords',
        'template_id',
        'meta_template_id',
        'template_source',
        'reply_delay_seconds',
        'is_one_time',
        'cooldown_hours',
        'business_hours_only',
        'business_hours_start',
        'business_hours_end',
        'max_replies_per_day',
        'priority',
        'is_active',
        'create_lead',
        'total_triggered',
        'total_sent',
        'total_skipped',
        'last_error',
        'last_error_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_one_time' => 'boolean',
        'business_hours_only' => 'boolean',
        'is_active' => 'boolean',
        'create_lead' => 'boolean',
        'last_error_at' => 'datetime',
    ];

    /**
     * Get satisfaction percentage (sent / triggered * 100)
     */
    public function getSatisfactionAttribute(): float
    {
        if ($this->total_triggered <= 0) {
            return 100.0; // No triggers yet = healthy
        }
        return round(($this->total_sent / $this->total_triggered) * 100, 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function metaTemplate()
    {
        return $this->belongsTo(MetaWhatsappTemplate::class, 'meta_template_id');
    }

    public function logs()
    {
        return $this->hasMany(WhatsappAutoReplyLog::class, 'rule_id');
    }

    /**
     * Get today's trigger count for this rule
     */
    public function getTodayTriggersAttribute()
    {
        return $this->logs()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get today's sent count for this rule
     */
    public function getTodaySentAttribute()
    {
        return $this->logs()
            ->whereDate('created_at', today())
            ->where('status', 'sent')
            ->count();
    }
}
