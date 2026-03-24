<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class AiTokenLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'session_id',
        'phone_number',
        'tier',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'model_used',
    ];

    protected $casts = [
        'tier' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}
