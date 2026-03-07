<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToCompany;

class Task extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const STATUSES = ['todo', 'doing', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const ENTITY_TYPES = ['lead', 'client', 'quote', 'project'];

    protected $fillable = [
        'company_id',
        'assigned_to_user_id',
        'created_by_user_id',
        'project_id',
        'entity_type',
        'entity_id',
        'title',
        'description',
        'contact_number',
        'due_at',
        'priority',
        'status',
        'sort_order',
        'completed_at',
        'started_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    protected $appends = ['contact_phone'];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Project relationship (direct FK)
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Task activities
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    // Micro tasks
    public function microTasks(): HasMany
    {
        return $this->hasMany(MicroTask::class)->orderBy('sort_order', 'asc');
    }

    // Polymorphic entity relationship
    public function entity()
    {
        return match ($this->entity_type) {
            'lead' => $this->belongsTo(Lead::class, 'entity_id'),
            'client' => $this->belongsTo(Client::class, 'entity_id'),
            'quote' => $this->belongsTo(Quote::class, 'entity_id'),
            'project' => $this->belongsTo(Project::class, 'entity_id'),
            default => null,
        };
    }

    // Direct lead relationship for entity_type=lead
    public function leadEntity(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'entity_id');
    }

    // Direct client relationship for entity_type=client
    public function clientEntity(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'entity_id');
    }

    // Get contact phone from associated lead/client
    public function getContactPhoneAttribute(): ?string
    {
        if (!empty($this->contact_number)) {
            return $this->contact_number;
        }

        if ($this->entity_type === 'lead' && $this->relationLoaded('leadEntity')) {
            return $this->leadEntity?->phone;
        }
        if ($this->entity_type === 'client' && $this->relationLoaded('clientEntity')) {
            return $this->clientEntity?->phone;
        }
        if ($this->project_id && $this->relationLoaded('project')) {
            return $this->project?->lead?->phone ?? $this->project?->client?->phone;
        }
        // Lazy load fallback if really needed, but try to avoid N+1
        if ($this->project_id && !$this->relationLoaded('project')) {
            // It will trigger a query, but ensures the data is there
            return $this->project?->lead?->phone ?? $this->project?->client?->phone;
        }

        return null;
    }

    // Status helpers
    public static function getDynamicStatuses(): array
    {
        return Setting::getValue('tasks', 'statuses', self::STATUSES);
    }

    public function isPending(): bool
    {
        return $this->status === 'todo';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'doing';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !$this->isDone();
    }

    // Mark as done
    public function markAsDone(): void
    {
        $this->status = 'done';
        $this->completed_at = now();
        $this->save();
    }

    // Scopes
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'done')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', '!=', 'done');
    }
}
