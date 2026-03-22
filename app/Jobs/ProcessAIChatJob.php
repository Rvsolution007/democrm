<?php

namespace App\Jobs;

use App\Services\AIChatbotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessAIChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [10, 30];
    public $timeout = 120;

    public function __construct(
        private string $instanceName,
        private string $senderPhone,
        private string $messageText,
        private ?array $replyContext = null,
        private ?string $imageUrl = null
    ) {
    }

    public function handle(): void
    {
        try {
            // Get user_id and company_id from instance name
            $userId = $this->getUserIdFromInstance($this->instanceName);
            if (!$userId) {
                Log::warning("AIChatJob: No user found for instance {$this->instanceName}");
                return;
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                Log::warning("AIChatJob: User {$userId} not found");
                return;
            }

            $companyId = $user->company_id ?? 1;

            // Use advisory lock to prevent race conditions for same phone number
            $lockKey = "ai_chat_lock_{$companyId}_{$this->senderPhone}";
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 60); // 60 second lock

            if ($lock->get()) {
                try {
                    $service = new AIChatbotService($companyId, $userId);
                    $result = $service->processMessage(
                        $this->instanceName,
                        $this->senderPhone,
                        $this->messageText,
                        $this->replyContext,
                        $this->imageUrl
                    );
                    Log::info("AIChatJob: Processed message from {$this->senderPhone}", $result);
                } finally {
                    $lock->release();
                }
            } else {
                // Could not acquire lock — another job is processing this phone number
                // Release back to queue with delay
                $this->release(5); // Retry in 5 seconds
                Log::info("AIChatJob: Lock held for {$this->senderPhone}, retrying in 5s");
            }

        } catch (\Exception $e) {
            Log::error("AIChatJob: Failed for {$this->senderPhone}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract user_id from instance name (format: rvcrm_{username}_{id})
     */
    private function getUserIdFromInstance(string $instanceName): ?int
    {
        $parts = explode('_', $instanceName);
        if (count($parts) >= 3) {
            $id = (int) end($parts);
            if ($id > 0) return $id;
        }
        return null;
    }
}
