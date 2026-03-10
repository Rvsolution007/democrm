<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicroTask extends Model
{
    protected $fillable = [
        'task_id',
        'role_id',
        'title',
        'status',
        'follow_up_date',
        'sort_order',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    protected static function booted()
    {
        static::saved(function ($microTask) {
            if ($microTask->task && $microTask->task->project_id) {
                if ($project = \App\Models\Project::find($microTask->task->project_id)) {
                    $project->checkAndUpdateStatus();
                }
            }
        });

        static::deleted(function ($microTask) {
            if ($microTask->task && $microTask->task->project_id) {
                if ($project = \App\Models\Project::find($microTask->task->project_id)) {
                    $project->checkAndUpdateStatus();
                }
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
