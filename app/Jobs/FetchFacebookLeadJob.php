<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\FacebookLeadAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchFacebookLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // 30s, 1min, 2min

    public function __construct(
        private int $integrationId,
        private string $leadgenId
    ) {
    }

    public function handle(FacebookLeadAdsService $service): void
    {
        $integration = Integration::find($this->integrationId);

        if (!$integration || !$integration->isActive()) {
            Log::channel('integrations')->warning('Facebook integration not found or inactive', [
                'integration_id' => $this->integrationId,
            ]);
            return;
        }

        try {
            // Fetch full lead details from Graph API
            $leadData = $service->fetchLeadDetails($integration, $this->leadgenId);

            if (!$leadData) {
                Log::channel('integrations')->warning('No lead data returned from Facebook', [
                    'leadgen_id' => $this->leadgenId,
                ]);
                return;
            }

            // Process and create lead
            $lead = $service->processLead($integration, $this->leadgenId, $leadData);

            Log::channel('integrations')->info('Facebook lead processed successfully', [
                'leadgen_id' => $this->leadgenId,
                'lead_id' => $lead->id,
            ]);

        } catch (\Exception $e) {
            Log::channel('integrations')->error('Facebook lead fetch failed', [
                'leadgen_id' => $this->leadgenId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }
}
