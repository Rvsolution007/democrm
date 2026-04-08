<?php

namespace App\Jobs;

use App\Services\ListBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessListBotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [10, 30];
    public $timeout = 60; // Shorter than AI Bot — no AI calls

    public function __construct(
        private string $instanceName,
        private string $senderPhone,
        private string $messageText,
        private ?string $listRowId = null
    ) {
    }

    public function handle(): void
    {
        try {
            $userId = $this->getUserIdFromInstance($this->instanceName);
            if (!$userId) {
                Log::warning("ListBotJob: No user found for instance {$this->instanceName}");
                return;
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                Log::warning("ListBotJob: User {$userId} not found");
                return;
            }

            $companyId = $user->company_id ?? 1;

            // Advisory lock — same pattern as AI Bot to prevent race conditions
            $lockKey = "list_bot_lock_{$companyId}_{$this->senderPhone}";
            $lock = Cache::lock($lockKey, 30); // 30 second max hold (shorter — no AI delays)

            try {
                $lock->block(15); // Wait up to 15 seconds

                try {
                    $service = new ListBotService($companyId, $userId);
                    $service->processMessage(
                        $this->instanceName,
                        $this->senderPhone,
                        $this->messageText,
                        $this->listRowId
                    );
                    Log::info("ListBotJob: Processed message from {$this->senderPhone}", [
                        'rowId' => $this->listRowId,
                        'text' => mb_substr($this->messageText, 0, 50),
                    ]);
                } finally {
                    $lock->release();
                }
            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                Log::warning("ListBotJob: Lock timeout for {$this->senderPhone}, processing without lock");
                $service = new ListBotService($companyId, $userId);
                $service->processMessage(
                    $this->instanceName,
                    $this->senderPhone,
                    $this->messageText,
                    $this->listRowId
                );
            }

        } catch (\Exception $e) {
            Log::error("ListBotJob: Failed for {$this->senderPhone}: " . $e->getMessage());
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
