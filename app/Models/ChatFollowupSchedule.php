<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class ChatFollowupSchedule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'delay_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delay_minutes' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
