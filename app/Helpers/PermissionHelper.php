<?php

if (!function_exists('can')) {
    /**
     * Check if the current user has the given permission.
     * Hierarchy: super_admin > admin (package scoped) > staff (role + package scoped)
     *
     * @param string $permission  e.g., 'leads.read', 'whatsapp-connect.write'
     * @return bool
     */
    function can(string $permission): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super Admin bypasses everything
        if ($user->user_type === 'super_admin') {
            return true;
        }

        // Extract module from permission: "leads.read" → "leads"
        $module = explode('.', $permission)[0];

        // Check if the module is in the company's subscription package
        $company = $user->company;
        if ($company && !$company->hasModuleAccess($module)) {
            return false; // Feature not in package — deny regardless of role
        }

        // Admin (Business Owner) — has all permissions within their package
        if ($user->user_type === 'admin') {
            return true;
        }

        // Staff — check role-based permissions
        $role = $user->role;
        if (!$role) {
            // Fallback: try loading role directly from DB
            try {
                $role = \App\Models\Role::find($user->role_id);
            } catch (\Exception $e) {
                return false;
            }
        }

        if (!$role) {
            return false;
        }

        // Admin role name bypass (backward compat for roles named "admin")
        if (stripos($role->name ?? '', 'admin') !== false) {
            return true;
        }

        // Check "all" permission
        $permissions = $role->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }
        if (in_array('all', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Check if the current user is an admin (super_admin or admin type)
     *
     * @return bool
     */
    function isAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Check user_type first
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return true;
        }

        // Backward compat — check role name
        $role = $user->role;
        if (!$role) {
            try {
                $role = \App\Models\Role::find($user->role_id);
            } catch (\Exception $e) {
                return false;
            }
        }

        if (!$role) {
            return false;
        }

        return stripos($role->name ?? '', 'admin') !== false;
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if the current user is a Super Admin.
     *
     * @return bool
     */
    function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->user_type === 'super_admin';
    }
}

if (!function_exists('hasFeature')) {
    /**
     * Check if the current user's company has a specific feature in their package.
     * Super Admin always returns true.
     *
     * @param string $feature  e.g., 'whatsapp_connect', 'chatflow', 'ai_bot'
     * @return bool
     */
    function hasFeature(string $feature): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->user_type === 'super_admin') return true;

        $company = $user->company;
        if (!$company) return false;

        return $company->hasFeature($feature);
    }
}

