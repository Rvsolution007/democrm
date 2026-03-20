<?php

namespace App\Jobs;

use App\Services\AutoReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [10, 30]; // retry after 10s, then 30s
    public $timeout = 120; // max 2 minutes per job

    public function __construct(
        private string $instanceName,
        private string $senderPhone,
        private string $messageText
    ) {
    }

    public function handle(): void
    {
        try {
            $service = new AutoReplyService();
            $result = $service->processIncomingMessage(
                $this->instanceName,
                $this->senderPhone,
                $this->messageText
            );
            Log::info("AutoReply Job: Processed message from {$this->senderPhone}", $result);
        } catch (\Exception $e) {
            Log::error("AutoReply Job: Failed for {$this->senderPhone}: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }
}
