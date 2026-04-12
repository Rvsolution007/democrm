<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaWhatsappTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'meta_template_id',
        'name',
        'category',
        'language',
        'status',
        'rejected_reason',
        'header_type',
        'header_text',
        'body_text',
        'footer_text',
        'buttons',
        'example_values',
        'last_synced_at',
    ];

    protected $casts = [
        'buttons' => 'array',
        'example_values' => 'array',
        'last_synced_at' => 'datetime',
    ];

    // ── Scopes ──

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    // ── Status Helpers ──

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'APPROVED' => '#22c55e',
            'PENDING' => '#f59e0b',
            'REJECTED' => '#ef4444',
            'DRAFT' => '#94a3b8',
            default => '#64748b',
        };
    }

    /**
     * Get status badge background
     */
    public function getStatusBgAttribute(): string
    {
        return match ($this->status) {
            'APPROVED' => '#f0fdf4',
            'PENDING' => '#fffbeb',
            'REJECTED' => '#fef2f2',
            'DRAFT' => '#f8fafc',
            default => '#f8fafc',
        };
    }

    /**
     * Get category badge color
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'MARKETING' => '#8b5cf6',
            'UTILITY' => '#3b82f6',
            'AUTHENTICATION' => '#0ea5e9',
            default => '#64748b',
        };
    }

    /**
     * Count variables in body text ({{1}}, {{2}}, etc.)
     */
    public function getVariableCountAttribute(): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->body_text, $matches);
        return count(array_unique($matches[1] ?? []));
    }

    // ── Relationships ──

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
