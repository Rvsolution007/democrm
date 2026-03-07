<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Integration;
use App\Jobs\PullIndiaMartLeadsJob;

// Schedule IndiaMART lead pulling every 5 minutes
Schedule::call(function () {
    $integrations = Integration::where('provider', 'indiamart')
        ->where('status', 'active')
        ->get();

    foreach ($integrations as $integration) {
        PullIndiaMartLeadsJob::dispatch($integration->id);
    }
})->everyFiveMinutes()->name('indiamart-pull-leads')->withoutOverlapping();

// Note: Facebook leads are received via webhook, no scheduled pulling needed
// However, you can schedule a daily backfill check if desired:
// Schedule::command('facebook:backfill-leads --since=yesterday')
//     ->dailyAt('06:00')
//     ->name('facebook-daily-check');

// Process WhatsApp Bulk Campaigns
Schedule::command('whatsapp:process-campaigns')
    ->everyMinute()
    ->name('whatsapp-bulk-sender')
    ->withoutOverlapping();

// Daily Notification Commands
Schedule::command('notifications:followups')
    ->dailyAt('08:00')
    ->name('notifications-followups')
    ->withoutOverlapping();

Schedule::command('notifications:overdue')
    ->dailyAt('08:00')
    ->name('notifications-overdue')
    ->withoutOverlapping();

// Daily CRM Data Backup at midnight
Schedule::command('backup:crm')
    ->dailyAt('00:00')
    ->name('crm-data-backup')
    ->withoutOverlapping();
