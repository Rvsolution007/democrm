<?php

namespace App\Models\Traits;

trait BelongsToCompany
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToCompany(): void
    {
        // Automatically set company_id from authenticated user on create
        static::creating(function ($model) {
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    /**
     * Scope query to current company.
     */
    public function scopeForCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;

        if ($companyId) {
            return $query->where('company_id', $companyId);
        }

        return $query;
    }
}
