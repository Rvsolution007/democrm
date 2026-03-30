<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class ChatflowStep extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'step_type',
        'linked_column_id',
        'question_text',
        'media_path',
        'field_key',
        'is_optional',
        'max_retries',
        'sort_order',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'max_retries' => 'integer',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function linkedColumn(): BelongsTo
    {
        return $this->belongsTo(CatalogueCustomColumn::class, 'linked_column_id');
    }

    // Helpers
    public function isComboStep(): bool
    {
        return $this->step_type === 'ask_combo';
    }

    public function isOptionalStep(): bool
    {
        return $this->is_optional || $this->step_type === 'ask_optional';
    }

    public function isProductStep(): bool
    {
        return in_array($this->step_type, ['ask_product', 'ask_unique_column']);
    }

    public function isBaseColumnStep(): bool
    {
        return $this->step_type === 'ask_base_column';
    }

    public function isUniqueColumnStep(): bool
    {
        return $this->step_type === 'ask_unique_column';
    }

    public function hasMedia(): bool
    {
        return !empty($this->media_path);
    }

    public function isCategoryStep(): bool
    {
        return $this->step_type === 'ask_category';
    }

    public function isSummaryStep(): bool
    {
        return $this->step_type === 'send_summary';
    }

    /**
     * Get the next step in the chatflow
     */
    public function getNextStep(): ?self
    {
        return static::where('company_id', $this->company_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Get the first step of a company's chatflow
     */
    public static function getFirstStep(int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->first();
    }
}
