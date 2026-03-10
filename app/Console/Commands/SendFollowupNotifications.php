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
    protected $description = 'Send notifications for leads/micro-tasks with follow-up matching current time';

    public function handle()
    {
        $count = 0;
        $count += $this->processLeadFollowups();
        $count += $this->processMicroTaskFollowups();

        $this->info("Sent {$count} follow-up notifications.");
        return Command::SUCCESS;
    }

    /**
     * Lead follow-ups: exact minute match on next_follow_up_at
     */
    private function processLeadFollowups(): int
    {
        $now = now();
        $minuteStart = $now->copy()->startOfMinute();
        $minuteEnd = $now->copy()->endOfMinute();

        $leads = Lead::whereBetween('next_follow_up_at', [$minuteStart, $minuteEnd])
            ->whereNotNull('assigned_to_user_id')
            ->whereNotIn('stage', ['won', 'lost'])
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            $user = User::find($lead->assigned_to_user_id);
            if (!$user)
                continue;

            // Avoid duplicate: check if same notification already sent today
            $exists = $user->notifications()
                ->whereDate('created_at', now()->toDateString())
                ->where('type', FollowupTodayNotification::class)
                ->whereJsonContains('data->entity_type', 'lead')
                ->whereJsonContains('data->entity_id', $lead->id)
                ->exists();

            if (!$exists) {
                $followUpTime = $lead->next_follow_up_at->format('h:i A');
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

        $microTasks = MicroTask::whereDate('follow_up_date', $today)
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
