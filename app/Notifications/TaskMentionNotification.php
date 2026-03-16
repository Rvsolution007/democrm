<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskMentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $taskId,
        public string $taskTitle,
        public string $senderName,
        public string $activityMessage
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\WhatsAppChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'entity_type' => 'task',
            'entity_id' => $this->taskId,
            'entity_name' => $this->taskTitle,
            'message' => "{$this->senderName} mentioned you in a task: {$this->taskTitle} - \"{$this->activityMessage}\"",
            'url' => $this->buildUrl(),
            'icon' => 'message-square',
        ];
    }

    private function buildUrl(): string
    {
        $baseUrl = rtrim(config('app.url', url('/')), '/');
        return $baseUrl . '/admin/tasks';
    }
}
