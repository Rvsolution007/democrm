<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public string $entityName,
        public string $assignerName
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
            'type' => 'assigned',
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'message' => "{$this->assignerName} assigned you a {$typeLabel}: {$this->entityName}",
            'url' => $this->buildUrl(),
            'icon' => $this->getIcon(),
        ];
    }

    private function buildUrl(): string
    {
        $baseUrl = rtrim(config('app.url', url('/')), '/');
        
        $path = match ($this->entityType) {
            'lead' => '/admin/leads',
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
            'task' => 'task',
            'project' => 'project',
            default => 'task',
        };
    }
}
