<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if (config('app.env') === 'production') {
        //     \Illuminate\Support\Facades\URL::forceScheme('https');
        // }

        // Configure rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Configure model factories for multi-tenant
        // Model::preventLazyLoading(!app()->isProduction());
    }
}
