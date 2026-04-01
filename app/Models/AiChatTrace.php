<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatTrace extends Model
{
    protected $fillable = [
        'session_id',
        'message_id',
        'node_name',
        'node_group',
        'status',
        'input_data',
        'output_data',
        'error_message',
        'execution_time_ms',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'execution_time_ms' => 'integer',
    ];

    // ─── Relationships ───

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'message_id');
    }

    // ─── Helpers ───

    /**
     * Get status badge color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'success' => '#22c55e',
            'error'   => '#ef4444',
            'warning' => '#f59e0b',
            'skipped' => '#94a3b8',
            default   => '#6b7280',
        };
    }

    /**
     * Get node group icon name for UI.
     */
    public function getGroupIcon(): string
    {
        return match ($this->node_group) {
            'routing'     => 'git-branch',
            'ai_call'     => 'brain',
            'database'    => 'database',
            'data_update' => 'database',
            'delivery'    => 'send',
            'media'       => 'image',
            'followup'    => 'bell',
            default       => 'circle',
        };
    }

    /**
     * Delete traces older than N days.
     */
    public static function purgeOlderThan(int $days = 7): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
