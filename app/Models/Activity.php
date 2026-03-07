<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class Activity extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const TYPES = ['call', 'whatsapp', 'email', 'note', 'meeting', 'task'];
    public const ENTITY_TYPES = ['lead', 'client', 'quote'];

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'entity_type',
        'entity_id',
        'type',
        'subject',
        'summary',
        'next_action_at',
        'next_action_type',
    ];

    protected $casts = [
        'next_action_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Polymorphic entity relationship
    public function entity()
    {
        return match ($this->entity_type) {
            'lead' => $this->belongsTo(Lead::class, 'entity_id'),
            'client' => $this->belongsTo(Client::class, 'entity_id'),
            'quote' => $this->belongsTo(Quote::class, 'entity_id'),
            default => null,
        };
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'entity_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'entity_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'entity_id');
    }

    // Scope for entity
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }
}
