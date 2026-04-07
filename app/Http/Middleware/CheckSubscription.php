<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Verify the user's company has an active subscription.
     * Super Admin bypasses this check.
     * Grace period: 3 days after expiry.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin always passes
        if ($user->user_type === 'super_admin') {
            return $next($request);
        }

        $company = $user->company;
        if (!$company) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not linked to any company. Contact support.',
            ]);
        }

        $subscription = $company->activeSubscription();

        // No active subscription at all
        if (!$subscription) {
            $latest = $company->latestSubscription();

            // Check grace period (3 days after expiry)
            if ($latest && $latest->isInGracePeriod()) {
                // Allow access but flash warning
                session()->flash('subscription_warning', 'Your subscription expired on ' . $latest->expires_at->format('d M Y') . '. You have ' . max(0, 3 - (int) $latest->expires_at->diffInDays(now())) . ' days remaining in grace period. Please renew to continue using the system.');
                return $next($request);
            }

            // Fully expired — redirect to expired page
            return redirect()->route('subscription.expired');
        }

        // Subscription is suspended
        if ($subscription->isSuspended()) {
            return redirect()->route('subscription.expired')
                ->with('reason', 'suspended');
        }

        // Active subscription — share data with views
        view()->share('currentSubscription', $subscription);
        view()->share('currentPackage', $subscription->package);

        return $next($request);
    }
}
