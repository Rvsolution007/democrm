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
        'follow_up_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($microTask) {
            $task = $microTask->task;
            if ($task) {
                $microTasks = $task->microTasks()->get();
                if ($microTasks->isNotEmpty()) {
                    $allDone = true;
                    foreach ($microTasks as $mt) {
                        if ($mt->status !== 'done') {
                            $allDone = false;
                            break;
                        }
                    }
                    if ($allDone && $task->status !== 'done') {
                        $task->status = 'done';
                        $task->completed_at = now();
                        $task->save();
                    }
                }

                if ($task->project_id) {
                    if ($project = \App\Models\Project::find($task->project_id)) {
                        $project->checkAndUpdateStatus();
                    }
                }
            }
        });

        static::deleted(function ($microTask) {
            $task = $microTask->task;
            if ($task && $task->project_id) {
                if ($project = \App\Models\Project::find($task->project_id)) {
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
