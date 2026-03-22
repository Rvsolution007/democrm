<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        // Force HTTPS in production (EasyPanel Traefik handles SSL)
        if (app()->environment('production')) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on'); // Bypass proxy issues causing 419 Page Expired with Secure Cookies
        }

        Storage::extend('google', function($app, $config) {
            if (empty($config['clientId']) || empty($config['clientSecret']) || empty($config['refreshToken'])) {
                throw new \Exception('Google Drive credentials not configured. Please set GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN in your .env file.');
            }
            $client = new \Google\Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $service = new \Google\Service\Drive($client);
            $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId']);
            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter),
                $adapter,
                $config
            );
        });

        \Illuminate\Pagination\Paginator::useBootstrapFive();

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
