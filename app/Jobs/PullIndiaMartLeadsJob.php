<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\IndiaMartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullIndiaMartLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600]; // 1min, 5min, 10min

    public function __construct(
        private int $integrationId
    ) {
    }

    public function handle(IndiaMartService $service): void
    {
        $integration = Integration::find($this->integrationId);

        if (!$integration || !$integration->isActive()) {
            Log::channel('integrations')->warning('IndiaMART integration not found or inactive', [
                'integration_id' => $this->integrationId,
            ]);
            return;
        }

        try {
            $result = $service->pullLeads($integration);

            Log::channel('integrations')->info('IndiaMART leads pulled successfully', [
                'integration_id' => $this->integrationId,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::channel('integrations')->error('IndiaMART lead pull failed', [
                'integration_id' => $this->integrationId,
                'error' => $e->getMessage(),
            ]);

            $integration->markError($e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }
}
