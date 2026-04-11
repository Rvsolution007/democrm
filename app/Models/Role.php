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
        // Sales
        'leads.read', 'leads.write', 'leads.delete', 'leads.global',
        'clients.read', 'clients.write', 'clients.delete', 'clients.global',
        'quotes.read', 'quotes.write', 'quotes.delete', 'quotes.global', 'quotes.approve',
        'invoices.read', 'invoices.write', 'invoices.delete', 'invoices.global',
        'payments.read', 'payments.write', 'payments.delete', 'payments.global',
        'followups.read', 'followups.write', 'followups.delete', 'followups.global',

        // WhatsApp Bulk
        'whatsapp-connect.read', 'whatsapp-connect.write',
        'whatsapp-extension.read',
        'whatsapp-campaigns.read', 'whatsapp-campaigns.write', 'whatsapp-campaigns.delete',
        'whatsapp-templates.read', 'whatsapp-templates.write', 'whatsapp-templates.delete', 'whatsapp-templates.global',
        'whatsapp-auto-reply.read', 'whatsapp-auto-reply.write', 'whatsapp-auto-reply.delete',
        'whatsapp-analytics.read',

        // Catalog
        'products.read', 'products.write', 'products.delete', 'products.global',
        'categories.read', 'categories.write', 'categories.delete', 'categories.global',
        'vendors.read', 'vendors.write', 'vendors.delete', 'vendors.global',
        'purchases.read', 'purchases.write', 'purchases.delete', 'purchases.global',
        'purchase-payments.read', 'purchase-payments.write', 'purchase-payments.delete', 'purchase-payments.global',



        // Team
        'users.read', 'users.write', 'users.delete', 'users.global',
        'roles.read', 'roles.write', 'roles.delete', 'roles.global',
        'activities.read', 'activities.write', 'activities.global',

        // Analytics
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
