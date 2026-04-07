<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Ensure only super_admin users can access the route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || $user->user_type !== 'super_admin') {
            abort(403, 'Access denied. Super Admin privileges required.');
        }

        return $next($request);
    }
}
