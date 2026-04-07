<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_type',
        'company_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'max_sessions',
        'last_login_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Permission helpers
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->role?->hasAnyPermission($permissions) ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ─── User Type Helpers ───────────────────────────────────────────

    /**
     * Check if user is the platform Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    /**
     * Check if user is a Business Admin (company owner).
     */
    public function isBusinessAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user is a Staff member under an Admin.
     */
    public function isStaff(): bool
    {
        return $this->user_type === 'staff';
    }

    /**
     * Legacy isAdmin() — returns true for super_admin and admin types,
     * and also for roles with 'all' permission (backward compat).
     */
    public function isAdmin(): bool
    {
        if (in_array($this->user_type, ['super_admin', 'admin'])) {
            return true;
        }
        $permissions = $this->role?->permissions ?? [];
        return in_array('all', $permissions) || ($this->role?->is_system ?? false);
    }

    /**
     * Get the admin user who owns this staff user's company.
     */
    public function getCompanyOwner(): ?User
    {
        return $this->company?->owner;
    }

    /**
     * Scope to filter users who have at least one permission for a given module.
     * E.g., scopeWithModulePermission($query, 'projects') checks for projects.read, projects.write, etc.
     * Also includes admin users (role has 'all' permission or role name contains 'admin').
     */
    public function scopeWithModulePermission($query, string $module)
    {
        return $query->whereHas('role', function ($q) use ($module) {
            $q->where(function ($roleQuery) use ($module) {
                // Include roles with 'all' permission (admin)
                $roleQuery->where('permissions', 'LIKE', '%"all"%')
                    // Include roles whose name contains 'admin'
                    ->orWhere('name', 'LIKE', '%admin%')
                    // Include roles with any permission for this module
                    ->orWhere('permissions', 'LIKE', '%"' . $module . '.%');
            });
        });
    }

    // Scope queries by company
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
