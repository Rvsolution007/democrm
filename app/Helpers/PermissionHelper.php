<?php

if (!function_exists('can')) {
    /**
     * Check if the current user has the given permission.
     * Admin users (ID=1 or role name contains 'admin' or role has 'all' permission) always return true.
     *
     * @param string $permission
     * @return bool
     */
    function can(string $permission): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Admin bypass — using loose == so "1" == 1 works
        if ($user->id == 1) {
            return true;
        }

        // Try to load role if not loaded
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

        // Admin role name bypass
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
     * Check if the current user is an admin
     *
     * @return bool
     */
    function isAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Loose comparison for ID
        if ($user->id == 1) {
            return true;
        }

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
