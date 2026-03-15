<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\MicroTask;
use App\Models\User;
use App\Notifications\FollowupTodayNotification;

class SendFollowupNotifications extends Command
{
    protected $signature = 'notifications:followups';
    protected $description = 'Send notifications for leads/micro-tasks with follow-up due (past or current)';

    public function handle()
    {
        $count = 0;
        $count += $this->processLeadFollowups();
        $count += $this->processMicroTaskFollowups();

        $this->info("Sent {$count} follow-up notifications.");
        return Command::SUCCESS;
    }

    /**
     * Lead follow-ups: find all leads where next_follow_up_at is due (past or now)
     * and notification hasn't been sent yet today for that lead.
     * This ensures missed follow-ups are still caught.
     */
    private function processLeadFollowups(): int
    {
        $now = now();

        // Get all leads where follow-up time has passed (or is right now)
        // and stage is not won/lost
        $leads = Lead::where('next_follow_up_at', '<=', $now)
            ->whereNotNull('assigned_to_user_id')
            ->whereNotIn('stage', ['won', 'lost'])
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            $user = User::find($lead->assigned_to_user_id);
            if (!$user)
                continue;

            $followUpTime = $lead->next_follow_up_at->format('h:i A');

            // Avoid duplicate: check if same notification already sent today for this exact follow-up time
            $exists = $user->notifications()
                ->whereDate('created_at', now()->toDateString())
                ->where('type', FollowupTodayNotification::class)
                ->whereJsonContains('data->entity_type', 'lead')
                ->whereJsonContains('data->entity_id', $lead->id)
                ->whereJsonContains('data->time', $followUpTime)
                ->exists();

            if (!$exists) {
                $user->notify(new FollowupTodayNotification('lead', $lead->id, $lead->name, $followUpTime));
                $count++;
            }
        }

        return $count;
    }

    /**
     * MicroTask follow-ups: date-level match on follow_up_date (no time stored)
     * Runs once daily — duplicate check prevents re-sending.
     */
    private function processMicroTaskFollowups(): int
    {
        $today = now()->toDateString();

        $microTasks = MicroTask::whereDate('follow_up_date', '<=', $today)
            ->whereNot('status', 'done')
            ->with('task')
            ->get();

        $count = 0;
        foreach ($microTasks as $microTask) {
            $task = $microTask->task;
            if (!$task || !$task->assigned_to_user_id)
                continue;

            $user = User::find($task->assigned_to_user_id);
            if (!$user)
                continue;

            // Avoid duplicate
            $exists = $user->notifications()
                ->whereDate('created_at', $today)
                ->where('type', FollowupTodayNotification::class)
                ->whereJsonContains('data->entity_type', 'micro_task')
                ->whereJsonContains('data->entity_id', $microTask->id)
                ->exists();

            if (!$exists) {
                $user->notify(new FollowupTodayNotification('micro_task', $microTask->id, $microTask->title));
                $count++;
            }
        }

        return $count;
    }
}
