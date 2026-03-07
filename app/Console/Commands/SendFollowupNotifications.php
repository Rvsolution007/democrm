<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\FollowupTodayNotification;

class SendFollowupNotifications extends Command
{
    protected $signature = 'notifications:followups';
    protected $description = 'Send notifications for leads with followup date today';

    public function handle()
    {
        $today = now()->toDateString();

        // Leads with next_follow_up_at = today
        $leads = Lead::whereDate('next_follow_up_at', $today)
            ->whereNotNull('assigned_to_user_id')
            ->whereNotIn('stage', ['won', 'lost'])
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            $user = User::find($lead->assigned_to_user_id);
            if ($user) {
                // Avoid duplicate: check if same notification already sent today
                $exists = $user->notifications()
                    ->whereDate('created_at', $today)
                    ->where('type', FollowupTodayNotification::class)
                    ->whereJsonContains('data->entity_type', 'lead')
                    ->whereJsonContains('data->entity_id', $lead->id)
                    ->exists();

                if (!$exists) {
                    $user->notify(new FollowupTodayNotification('lead', $lead->id, $lead->name));
                    $count++;
                }
            }
        }

        $this->info("Sent {$count} follow-up notifications.");
        return Command::SUCCESS;
    }
}
