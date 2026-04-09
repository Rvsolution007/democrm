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

            Log::info("ListBotJob: Resolved company", [
                'user_id' => $userId,
                'company_id' => $companyId,
                'instance' => $this->instanceName,
            ]);

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

    /**
     * Resolve the correct company_id for this instance.
     * 
     * Primary: user->company_id
     * Fallback: Search whatsapp api_config settings for the instance name
     *           to find which company actually owns this WhatsApp instance.
     * 
     * This handles cases where the user's company_id doesn't match the
     * company that has the WhatsApp config and products (e.g., after
     * company reassignment or multi-tenant setups).
     */
    private function resolveCompanyId(\App\Models\User $user): int
    {
        $primaryCompanyId = $user->company_id ?? 1;

        // Quick check: does this company have active products?
        $hasProducts = \App\Models\Product::where('company_id', $primaryCompanyId)
            ->where('status', 'active')
            ->exists();

        if ($hasProducts) {
            Log::info("ListBotJob: Company resolved from user", [
                'user_id' => $user->id,
                'company_id' => $primaryCompanyId,
            ]);
            return $primaryCompanyId;
        }

        // Fallback: find the company that owns this WhatsApp instance
        Log::warning("ListBotJob: No active products in company {$primaryCompanyId}, searching by instance name", [
            'instance' => $this->instanceName,
        ]);

        $apiConfigs = \App\Models\Setting::where('group', 'whatsapp')
            ->where('key', 'api_config')
            ->get();

        foreach ($apiConfigs as $setting) {
            $config = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
            $instanceName = $config['instance_name'] ?? '';

            if (!empty($instanceName) && $instanceName === $this->instanceName) {
                $fallbackCompanyId = $setting->company_id;
                Log::info("ListBotJob: Company resolved from WhatsApp api_config", [
                    'company_id' => $fallbackCompanyId,
                    'instance' => $this->instanceName,
                ]);
                return $fallbackCompanyId;
            }
        }

        // Last resort: try to find any company with active products that the user might belong to
        Log::warning("ListBotJob: Could not resolve company from instance, using user company_id={$primaryCompanyId}");
        return $primaryCompanyId;
    }
}
