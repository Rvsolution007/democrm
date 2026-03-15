<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowupTodayNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public string $entityName,
        public ?string $followUpTime = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\WhatsAppChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = match ($this->entityType) {
            'lead' => 'Lead',
            'micro_task' => 'Micro Task',
            'task' => 'Task',
            default => ucfirst($this->entityType),
        };

        $message = "Follow-up due for {$typeLabel}: {$this->entityName}";
        if ($this->followUpTime) {
            $message = "Follow-up at {$this->followUpTime} for {$typeLabel}: {$this->entityName}";
        }

        return [
            'type' => 'followup_today',
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'message' => $message,
            'url' => $this->buildUrl(),
            'icon' => $this->getIcon(),
            'time' => $this->followUpTime,
        ];
    }

    private function buildUrl(): string
    {
        $baseUrl = rtrim(config('app.url', url('/')), '/');
        
        $path = match ($this->entityType) {
            'lead' => '/admin/leads/' . $this->entityId,
            'micro_task' => '/admin/micro-tasks',
            'task' => '/admin/tasks',
            'project' => '/admin/projects/' . $this->entityId,
            default => '/admin/dashboard',
        };
        
        return $baseUrl . $path;
    }

    private function getIcon(): string
    {
        return match ($this->entityType) {
            'lead' => 'lead',
            'micro_task' => 'micro_task',
            'task' => 'task',
            'project' => 'project',
            default => 'task',
        };
    }
}
