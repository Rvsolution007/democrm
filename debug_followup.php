<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Lead;

echo "=== ALL LEADS WITH ANY FOLLOW-UP DATA ===\n";
$leads = Lead::whereNotNull('next_follow_up_at')
    ->orderBy('next_follow_up_at', 'desc')
    ->limit(10)
    ->get(['id', 'name', 'next_follow_up_at', 'assigned_to_user_id', 'stage']);

foreach ($leads as $lead) {
    echo "Lead #{$lead->id}: {$lead->name} | follow_up: {$lead->next_follow_up_at} | user: {$lead->assigned_to_user_id} | stage: {$lead->stage}\n";
}

if ($leads->isEmpty()) {
    echo "NO leads with next_follow_up_at found at all!\n";
}

echo "\n=== CHECK lead_followups TABLE ===\n";
$followups = \DB::table('lead_followups')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($followups as $f) {
    echo "Followup #{$f->id}: lead_id={$f->lead_id} | ";
    echo  (property_exists($f, 'follow_up_at') ? "follow_up_at={$f->follow_up_at}" : '') . " | ";
    echo  (property_exists($f, 'scheduled_at') ? "scheduled_at={$f->scheduled_at}" : '') . " | ";
    echo "created: {$f->created_at}\n";
}

if ($followups->isEmpty()) {
    echo "NO followups found in lead_followups table!\n";
}

echo "\n=== CHECK Fb Ad Lead 1 directly ===\n";
$lead = Lead::where('name', 'like', '%Fb Ad Lead 1%')->first();
if ($lead) {
    echo "Found: #{$lead->id}: {$lead->name}\n";
    echo "ALL columns: " . json_encode($lead->toArray(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Not found!\n";
}
