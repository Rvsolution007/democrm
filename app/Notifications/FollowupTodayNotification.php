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
        public string $entityName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = ucfirst($this->entityType);
        return [
            'type' => 'followup_today',
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'message' => "Follow-up due today for {$typeLabel}: {$this->entityName}",
            'url' => $this->buildUrl(),
            'icon' => $this->getIcon(),
        ];
    }

    private function buildUrl(): string
    {
        return match ($this->entityType) {
            'lead' => route('admin.leads.index'),
            'task' => route('admin.tasks.index'),
            'project' => route('admin.projects.show', $this->entityId),
            default => route('admin.dashboard'),
        };
    }

    private function getIcon(): string
    {
        return match ($this->entityType) {
            'lead' => 'lead',
            'task' => 'task',
            'project' => 'project',
            default => 'task',
        };
    }
}
