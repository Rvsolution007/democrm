<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Traits\BelongsToCompany;

class Project extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const STATUSES = ['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'];

    protected $fillable = [
        'company_id',
        'client_id',
        'quote_id',
        'lead_id',
        'created_by_user_id',
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
        'budget',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'budget' => 'integer',
    ];

    protected static function booted()
    {
        static::updated(function ($project) {
            if ($project->isDirty('status') && $project->status === 'completed') {
                foreach ($project->tasks as $task) {
                    if ($task->status !== 'done') {
                        $task->status = 'done';
                        $task->completed_at = now();
                        $task->save();
                    }
                }
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Changed from assignedTo (belongsTo) to assignedUsers (belongsToMany)
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    // Generate Project ID like Project-26-001
    public function getProjectIdCodeAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : date('y');
        return 'Project-' . $year . '-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    // Budget helper (paise to rupees)
    public function getBudgetInRupeesAttribute(): float
    {
        return ($this->budget ?? 0) / 100;
    }

    // Task progress
    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', 'done')->count();
    }

    public function getTotalTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->total_tasks_count;
        if ($total === 0)
            return 0;
        return (int) round(($this->completed_tasks_count / $total) * 100);
    }

    /**
     * Auto-updates the project status based on tasks and microtasks.
     */
    public function checkAndUpdateStatus()
    {
        $tasks = $this->tasks()->with('microTasks')->get();

        if ($tasks->isEmpty()) {
            return;
        }

        $allTasksDone = true;
        $anyTaskStarted = false;

        foreach ($tasks as $task) {
            if ($task->status !== 'done') {
                $allTasksDone = false;
            }
            if (in_array($task->status, ['doing', 'done'])) {
                $anyTaskStarted = true;
            }

            foreach ($task->microTasks as $microTask) {
                if (in_array($microTask->status, ['doing', 'done'])) {
                    $anyTaskStarted = true;
                }
            }
        }

        if ($allTasksDone) {
            if ($this->status !== 'completed') {
                $this->update(['status' => 'completed']);
            }
        } elseif ($anyTaskStarted) {
            if ($this->status === 'pending') {
                $this->update(['status' => 'in_progress']);
            }
        }
    }
}
