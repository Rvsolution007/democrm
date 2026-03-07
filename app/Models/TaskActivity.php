<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    public const TYPES = ['status_change', 'note', 'client_reply', 'revision', 'file_upload'];

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'message',
        'old_value',
        'new_value',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'status_change' => 'Status Changed',
            'note' => 'Note Added',
            'client_reply' => 'Client Reply',
            'revision' => 'Revision',
            'file_upload' => 'File Uploaded',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeColor(): string
    {
        return match ($this->type) {
            'status_change' => '#3b82f6',
            'note' => '#8b5cf6',
            'client_reply' => '#10b981',
            'revision' => '#f59e0b',
            'file_upload' => '#6366f1',
            default => '#64748b',
        };
    }
}
