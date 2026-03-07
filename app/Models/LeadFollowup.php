<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    use SoftDeletes;

    protected $table = 'lead_followups';

    protected $fillable = [
        'lead_id',
        'user_id',
        'message',
        'next_follow_up_date',
    ];

    protected $casts = [
        'next_follow_up_date' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
