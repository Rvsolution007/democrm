<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FacebookLeadAdsService;
use App\Jobs\FetchFacebookLeadJob;
use App\Models\Integration;
use App\Models\ExternalLead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function __construct(
        private FacebookLeadAdsService $facebookService
    ) {
    }

    /**
     * Verify webhook (GET request from Meta).
     * Meta sends: hub.mode, hub.verify_token, hub.challenge
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::channel('integrations')->info('Facebook webhook verification attempt', [
            'mode' => $mode,
            'token' => $token ? '***' : null,
        ]);

        // Check mode and token
        if ($mode === 'subscribe') {
            // Find any active Facebook integration with matching verify token
            $integration = Integration::where('provider', 'facebook')
                ->where('status', 'active')
                ->get()
                ->first(function ($integration) use ($token) {
                    return $integration->getFacebookVerifyToken() === $token;
                });

            if ($integration) {
                Log::channel('integrations')->info('Facebook webhook verified successfully', [
                    'company_id' => $integration->company_id,
                ]);
                return response($challenge, 200);
            }

            // Also check env token for initial setup
            if ($token === config('services.facebook.verify_token')) {
                return response($challenge, 200);
            }
        }

        Log::channel('integrations')->warning('Facebook webhook verification failed', [
            'mode' => $mode,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle webhook payload (POST request from Meta).
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        Log::channel('integrations')->info('Facebook webhook received', [
            'object' => $payload['object'] ?? null,
            'entry_count' => count($payload['entry'] ?? []),
        ]);

        // Verify it's a page webhook
        if (($payload['object'] ?? null) !== 'page') {
            return response('OK', 200);
        }

        // Process each entry
        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) === 'leadgen') {
                    $this->processLeadgenChange($pageId, $change['value'] ?? []);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Process leadgen change event.
     */
    private function processLeadgenChange(string $pageId, array $value): void
    {
        $leadgenId = $value['leadgen_id'] ?? null;
        $formId = $value['form_id'] ?? null;

        if (!$leadgenId) {
            Log::channel('integrations')->warning('Facebook leadgen event without leadgen_id');
            return;
        }

        Log::channel('integrations')->info('Facebook leadgen event', [
            'page_id' => $pageId,
            'leadgen_id' => $leadgenId,
            'form_id' => $formId,
        ]);

        // Find integration by page_id
        $integration = Integration::where('provider', 'facebook')
            ->where('status', 'active')
            ->get()
            ->first(function ($integration) use ($pageId) {
                return $integration->getFacebookPageId() === $pageId;
            });

        if (!$integration) {
            Log::channel('integrations')->warning('No active Facebook integration for page', [
                'page_id' => $pageId,
            ]);
            return;
        }

        // Check form allowlist if configured
        $allowedForms = $integration->getSetting('form_ids');
        if (!empty($allowedForms) && !in_array($formId, $allowedForms)) {
            Log::channel('integrations')->info('Facebook form not in allowlist, skipping', [
                'form_id' => $formId,
            ]);
            return;
        }

        // Check if already processed
        if (ExternalLead::exists($integration->company_id, 'facebook', $leadgenId)) {
            Log::channel('integrations')->info('Facebook lead already processed', [
                'leadgen_id' => $leadgenId,
            ]);
            return;
        }

        // Store initial external lead record
        ExternalLead::create([
            'company_id' => $integration->company_id,
            'provider' => 'facebook',
            'external_id' => $leadgenId,
            'payload' => $value, // Store initial webhook payload
            'received_at' => now(),
        ]);

        // Dispatch job to fetch full lead details
        FetchFacebookLeadJob::dispatch($integration->id, $leadgenId);
    }
}
