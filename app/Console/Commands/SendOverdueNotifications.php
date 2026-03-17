<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Notifications\OverdueNotification;

class SendOverdueNotifications extends Command
{
    protected $signature = 'notifications:overdue';
    protected $description = 'Send notifications for overdue tasks and projects';

    public function handle()
    {
        $today = now()->toDateString();
        $count = 0;

        // Overdue Tasks (due_at < today AND status != done)
        $tasks = Task::where('due_at', '<', $today)
            ->where('status', '!=', 'done')
            ->whereHas('assignedUsers')
            ->with('assignedUsers')
            ->get();

        foreach ($tasks as $task) {
            foreach ($task->assignedUsers as $user) {
                $exists = $user->notifications()
                    ->whereDate('created_at', $today)
                    ->where('type', OverdueNotification::class)
                    ->whereJsonContains('data->entity_type', 'task')
                    ->whereJsonContains('data->entity_id', $task->id)
                    ->exists();

                if (!$exists) {
                    $user->notify(new OverdueNotification(
                        'task',
                        $task->id,
                        $task->title,
                        $task->due_at->format('d M Y')
                    ));
                    $count++;
                }
            }
        }

        // Overdue Projects (due_date < today AND status != completed)
        $projects = Project::where('due_date', '<', $today)
            ->where('status', '!=', 'completed')
            ->whereHas('assignedUsers')
            ->with('assignedUsers')
            ->get();

        foreach ($projects as $project) {
            foreach ($project->assignedUsers as $user) {
                $exists = $user->notifications()
                    ->whereDate('created_at', $today)
                    ->where('type', OverdueNotification::class)
                    ->whereJsonContains('data->entity_type', 'project')
                    ->whereJsonContains('data->entity_id', $project->id)
                    ->exists();

                if (!$exists) {
                    $user->notify(new OverdueNotification(
                        'project',
                        $project->id,
                        $project->name,
                        $project->due_date->format('d M Y')
                    ));
                    $count++;
                }
            }
        }

        $this->info("Sent {$count} overdue notifications.");
        return Command::SUCCESS;
    }
}
