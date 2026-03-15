<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public string $entityName,
        public string $dueDate
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\WhatsAppChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = ucfirst($this->entityType);
        return [
            'type' => 'overdue',
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'message' => "{$typeLabel} \"{$this->entityName}\" is overdue (was due {$this->dueDate})",
            'url' => $this->buildUrl(),
            'icon' => $this->getIcon(),
        ];
    }

    private function buildUrl(): string
    {
        return match ($this->entityType) {
            'task' => route('admin.tasks.index', [], false),
            'project' => route('admin.projects.show', $this->entityId, false),
            default => route('admin.dashboard', [], false),
        };
    }

    private function getIcon(): string
    {
        return match ($this->entityType) {
            'task' => 'task',
            'project' => 'project',
            default => 'task',
        };
    }
}
