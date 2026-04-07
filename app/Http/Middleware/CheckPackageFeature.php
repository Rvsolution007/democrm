<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPackageFeature
{
    /**
     * Check if the user's subscription package includes the required feature.
     * Usage: Route::middleware('feature:whatsapp_connect')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin bypasses
        if ($user->user_type === 'super_admin') {
            return $next($request);
        }

        $company = $user->company;
        if (!$company || !$company->hasFeature($feature)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'This feature is not available in your current package. Please upgrade.',
                    'upgrade_required' => true,
                    'feature' => $feature,
                ], 403);
            }

            abort(403, 'This feature is not available in your current package. Please upgrade to access ' . str_replace('_', ' ', $feature) . '.');
        }

        return $next($request);
    }
}
