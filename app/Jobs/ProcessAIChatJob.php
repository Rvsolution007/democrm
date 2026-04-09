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
        private ?string $imageUrl = null,
        private ?string $listRowId = null
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

            Log::info("AIChatJob: Resolved company", [
                'user_id' => $userId,
                'company_id' => $companyId,
                'instance' => $this->instanceName,
            ]);

            // Use advisory lock to prevent race conditions for same phone number
            // block() waits up to 30s for the lock — ensures ALL messages get processed
            // even when multiple arrive simultaneously (sync connection can't use release())
            $lockKey = "ai_chat_lock_{$companyId}_{$this->senderPhone}";
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 60); // 60 second max hold

            try {
                $lock->block(30); // Wait up to 30 seconds to acquire lock

                try {
                    $service = new AIChatbotService($companyId, $userId);
                    $result = $service->processMessage(
                        $this->instanceName,
                        $this->senderPhone,
                        $this->messageText,
                        $this->replyContext,
                        $this->imageUrl,
                        $this->listRowId
                    );
                    Log::info("AIChatJob: Processed message from {$this->senderPhone}", $result);
                } finally {
                    $lock->release();
                }
            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                // Lock couldn't be acquired in 30 seconds — process anyway to avoid message loss
                Log::warning("AIChatJob: Lock timeout for {$this->senderPhone}, processing without lock");
                $service = new AIChatbotService($companyId, $userId);
                $result = $service->processMessage(
                    $this->instanceName,
                    $this->senderPhone,
                    $this->messageText,
                    $this->replyContext,
                    $this->imageUrl,
                    $this->listRowId
                );
                Log::info("AIChatJob: Processed (after lock timeout) from {$this->senderPhone}", $result);
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

    /**
     * Resolve the correct company_id for this instance.
     * Primary: user->company_id (if that company has WhatsApp config)
     * Fallback: Search whatsapp api_config settings for matching instance name
     */
    private function resolveCompanyId(\App\Models\User $user): int
    {
        $primaryCompanyId = $user->company_id ?? 1;

        // Quick check: does this company have WhatsApp config?
        $hasConfig = \App\Models\Setting::where('company_id', $primaryCompanyId)
            ->where('group', 'whatsapp')
            ->where('key', 'api_config')
            ->exists();

        if ($hasConfig) {
            return $primaryCompanyId;
        }

        // Fallback: find the company that owns this WhatsApp instance
        $apiConfigs = \App\Models\Setting::where('group', 'whatsapp')
            ->where('key', 'api_config')
            ->get();

        foreach ($apiConfigs as $setting) {
            $config = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
            $instanceName = $config['instance_name'] ?? '';

            if (!empty($instanceName) && $instanceName === $this->instanceName) {
                Log::info("AIChatJob: Company resolved from WhatsApp api_config", [
                    'company_id' => $setting->company_id,
                    'instance' => $this->instanceName,
                ]);
                return $setting->company_id;
            }
        }

        return $primaryCompanyId;
    }
}

