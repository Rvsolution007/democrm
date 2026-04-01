<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Models\Traits\BelongsToCompany;

class AiChatSession extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'phone_number',
        'instance_name',
        'lead_id',
        'quote_id',
        'current_step_id',
        'collected_answers',
        'optional_asked',
        'current_step_retries',
        'status',
        'conversation_state',
        'catalogue_sent',
        'last_message_at',
        'detected_language',
        'media_sent_keys',
    ];

    protected $casts = [
        'collected_answers' => 'array',
        'optional_asked' => 'array',
        'current_step_retries' => 'integer',
        'catalogue_sent' => 'boolean',
        'last_message_at' => 'datetime',
        'media_sent_keys' => 'array',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ChatflowStep::class, 'current_step_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id')->orderBy('created_at');
    }

    public function traces(): HasMany
    {
        return $this->hasMany(AiChatTrace::class, 'session_id')->orderBy('created_at');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get collected answer for a specific key
     */
    public function getAnswer(string $key, $default = null)
    {
        $answers = $this->collected_answers ?? [];
        return $answers[$key] ?? $default;
    }

    /**
     * Set a collected answer
     */
    public function setAnswer(string $key, $value): void
    {
        $answers = $this->collected_answers ?? [];
        $answers[$key] = $value;
        $this->collected_answers = $answers;
    }

    /**
     * Check if an optional question has already been asked
     */
    public function wasOptionalAsked(string $fieldKey): bool
    {
        $asked = $this->optional_asked ?? [];
        return in_array($fieldKey, $asked);
    }

    /**
     * Mark an optional question as asked
     */
    public function markOptionalAsked(string $fieldKey): void
    {
        $asked = $this->optional_asked ?? [];
        if (!in_array($fieldKey, $asked)) {
            $asked[] = $fieldKey;
            $this->optional_asked = $asked;
        }
    }

    /**
     * Check if a media item has already been sent in this session
     */
    public function hasMediaBeenSent(string $key): bool
    {
        $keys = $this->media_sent_keys ?? [];
        return in_array($key, $keys);
    }

    /**
     * Mark a media item as sent in this session
     */
    public function markMediaSent(string $key): void
    {
        $keys = $this->media_sent_keys ?? [];
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $this->media_sent_keys = $keys;
        }
    }

    /**
     * Find or create an active session for a phone number on an instance.
     * Auto-expires sessions older than configured timeout (default 30 min).
     */
    public static function findOrCreateForPhone(int $companyId, string $phone, string $instanceName): self
    {
        // First try to find an existing active session
        $session = static::where('company_id', $companyId)
            ->where('phone_number', $phone)
            ->where('instance_name', $instanceName)
            ->where('status', 'active')
            ->first();

        if ($session) {
            return $session;
        }

        // Create new session
        return static::create([
            'company_id' => $companyId,
            'phone_number' => $phone,
            'instance_name' => $instanceName,
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    /**
     * Get recent messages for AI context (last N messages)
     */
    public function getRecentMessages(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->messages()
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
