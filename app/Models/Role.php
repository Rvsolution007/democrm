<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    // Available permissions
    public const PERMISSIONS = [
        'leads.read',
        'leads.write',
        'leads.delete',
        'clients.read',
        'clients.write',
        'clients.delete',
        'quotes.read',
        'quotes.write',
        'quotes.delete',
        'quotes.approve',
        'products.read',
        'products.write',
        'products.delete',
        'categories.read',
        'categories.write',
        'categories.delete',
        'users.read',
        'users.write',
        'users.delete',
        'roles.read',
        'roles.write',
        'roles.delete',
        'activities.read',
        'activities.write',
        'tasks.read',
        'tasks.write',
        'tasks.delete',
        'reports.read',
        'settings.manage',
        'integrations.manage',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Permission checks
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        if (in_array('all', $permissions)) {
            return true;
        }
        return in_array($permission, $permissions);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        $myPermissions = $this->permissions ?? [];
        if (in_array('all', $myPermissions)) {
            return true;
        }
        return count(array_intersect($permissions, $myPermissions)) > 0;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        $myPermissions = $this->permissions ?? [];
        if (in_array('all', $myPermissions)) {
            return true;
        }
        return count(array_intersect($permissions, $myPermissions)) === count($permissions);
    }
}
