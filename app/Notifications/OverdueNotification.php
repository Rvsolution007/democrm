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
        $baseUrl = rtrim(config('app.url', url('/')), '/');
        
        $path = match ($this->entityType) {
            'task' => '/admin/tasks',
            'project' => '/admin/projects/' . $this->entityId,
            default => '/admin/dashboard',
        };
        
        return $baseUrl . $path;
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
