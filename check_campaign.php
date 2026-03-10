<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = \App\Models\WhatsappCampaign::latest()->first();

if ($campaign) {
    echo "Campaign ID: " . $campaign->id . "\n";
    echo "Status: " . $campaign->status . "\n";
    echo "Error Message: " . $campaign->error_message . "\n";

    // Total recipients logs
    echo "\n=== Message Logs ===\n";
    $logs = \App\Models\WhatsappCampaignRecipient::where('campaign_id', $campaign->id)->get();
    foreach ($logs as $log) {
        echo "Log ID: {$log->id} | Phone: {$log->phone_number} | Status: {$log->status} | Error: {$log->error_message}\n";
    }
} else {
    echo "No campaigns found.\n";
}
