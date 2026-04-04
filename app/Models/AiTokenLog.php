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

    /**
     * Get human-readable tier label.
     */
    public static function tierLabel(int $tier): string
    {
        return match ($tier) {
            0 => 'Tier 0 (Greeting)',
            1 => 'Tier 1 (Product Match)',
            2 => 'Tier 2 (Conversational)',
            3 => 'Tier 3 (Column Analytics)',
            default => "Tier {$tier}",
        };
    }

    /**
     * Accessor for tier_label attribute.
     */
    public function getTierLabelAttribute(): string
    {
        return self::tierLabel($this->tier ?? 2);
    }
}
