<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Traits\BelongsToCompany;

class AiProductSession extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'chat_session_id',
        'company_id',
        'session_uuid',
        'product_id',
        'product_name',
        'collected_answers',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'collected_answers' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->session_uuid)) {
                $model->session_uuid = (string) Str::uuid();
            }
        });
    }

    // ═══ Relationships ═══

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'chat_session_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ═══ Scopes ═══

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ═══ Helpers ═══

    public function getAnswer(string $key, $default = null)
    {
        return $this->collected_answers[$key] ?? $default;
    }

    public function setAnswer(string $key, $value): void
    {
        $answers = $this->collected_answers ?? [];
        $answers[$key] = $value;
        $this->collected_answers = $answers;
    }

    public function markActive(): void
    {
        $this->update(['status' => 'active']);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the next pending product session for a chat session.
     */
    public static function getNextPending(int $chatSessionId): ?self
    {
        return static::where('chat_session_id', $chatSessionId)
            ->where('status', 'pending')
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Create product sessions from QUEUE_MATCHES.
     * First product = active, rest = pending.
     */
    public static function createFromQueue(int $chatSessionId, int $companyId, array $productIds): ?self
    {
        $firstSession = null;

        foreach ($productIds as $index => $productId) {
            $product = Product::find($productId);
            $session = static::create([
                'chat_session_id' => $chatSessionId,
                'company_id' => $companyId,
                'product_id' => $productId,
                'product_name' => $product?->name ?? "Product #{$productId}",
                'status' => $index === 0 ? 'active' : 'pending',
                'sort_order' => $index,
            ]);

            if ($index === 0) {
                $firstSession = $session;
            }
        }

        return $firstSession;
    }
}
