<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\Lead;
use App\Models\ExternalLead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class FacebookLeadAdsService
{
    private const GRAPH_API_BASE = 'https://graph.facebook.com/';
    private const MAX_RETRIES = 3;

    /**
     * Fetch lead details from Graph API.
     */
    public function fetchLeadDetails(Integration $integration, string $leadgenId): ?array
    {
        $accessToken = $integration->getFacebookAccessToken();
        $apiVersion = config('services.facebook.graph_api_version', 'v18.0');

        if (!$accessToken) {
            throw new \RuntimeException('Facebook access token not configured');
        }

        $url = self::GRAPH_API_BASE . $apiVersion . '/' . $leadgenId;

        $params = [
            'access_token' => $accessToken,
            'fields' => 'id,created_time,ad_id,ad_name,campaign_id,campaign_name,form_id,field_data',
        ];

        Log::channel('integrations')->info('Fetching Facebook lead details', [
            'leadgen_id' => $leadgenId,
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(self::MAX_RETRIES, 100)
                ->get($url, $params);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Unknown error';
                throw new RequestException($response);
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::channel('integrations')->error('Facebook API error', [
                'leadgen_id' => $leadgenId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process fetched lead data.
     */
    public function processLead(Integration $integration, string $leadgenId, array $leadData): Lead
    {
        $companyId = $integration->company_id;

        // Parse field_data into key-value pairs
        $fields = $this->parseFieldData($leadData['field_data'] ?? []);

        Log::channel('integrations')->info('Processing Facebook lead', [
            'leadgen_id' => $leadgenId,
            'fields' => array_keys($fields),
        ]);

        // Get or create external lead record
        $externalLead = ExternalLead::where('company_id', $companyId)
            ->where('provider', 'facebook')
            ->where('external_id', $leadgenId)
            ->first();

        if ($externalLead) {
            // Update payload with full data
            $externalLead->update(['payload' => $leadData]);
        } else {
            $externalLead = ExternalLead::create([
                'company_id' => $companyId,
                'provider' => 'facebook',
                'external_id' => $leadgenId,
                'payload' => $leadData,
                'received_at' => now(),
            ]);
        }

        // Find or create lead
        $lead = $this->findOrCreateLead($companyId, $leadgenId, $fields, $leadData);

        // Link external lead to lead
        $externalLead->linkToLead($lead);

        $integration->updateLastSync();

        return $lead;
    }

    /**
     * Parse field_data array into key-value pairs.
     */
    private function parseFieldData(array $fieldData): array
    {
        $result = [];

        foreach ($fieldData as $field) {
            $name = strtolower($field['name'] ?? '');
            $values = $field['values'] ?? [];
            $result[$name] = is_array($values) ? ($values[0] ?? null) : $values;
        }

        return $result;
    }

    /**
     * Find existing lead or create new one.
     */
    private function findOrCreateLead(int $companyId, string $leadgenId, array $fields, array $rawData): Lead
    {
        // Extract contact info
        $name = $fields['full_name'] ?? $fields['name'] ??
            (($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
        $name = trim($name) ?: 'Facebook Lead';

        $phone = $fields['phone_number'] ?? $fields['phone'] ?? $fields['mobile'] ?? null;
        $email = $fields['email'] ?? null;
        $city = $fields['city'] ?? null;
        $state = $fields['state'] ?? $fields['province'] ?? null;

        // Clean phone number
        if ($phone) {
            $phone = preg_replace('/[^0-9+]/', '', $phone);
        }

        // Try to find existing lead by phone or email
        $existingLead = null;

        if ($phone) {
            $existingLead = Lead::where('company_id', $companyId)
                ->where('phone', $phone)
                ->first();
        }

        if (!$existingLead && $email) {
            $existingLead = Lead::where('company_id', $companyId)
                ->where('email', $email)
                ->first();
        }

        if ($existingLead) {
            // Update with any new info
            $updates = [];
            if (empty($existingLead->email) && $email) {
                $updates['email'] = $email;
            }
            if (empty($existingLead->phone) && $phone) {
                $updates['phone'] = $phone;
            }
            if (!empty($updates)) {
                $existingLead->update($updates);
            }
            return $existingLead;
        }

        // Create new lead
        return Lead::create([
            'company_id' => $companyId,
            'source' => 'facebook',
            'source_provider' => 'facebook',
            'source_external_id' => $leadgenId,
            'raw_source_payload' => $rawData,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'city' => $city,
            'state' => $state,
            'stage' => 'new',
            'notes' => $this->buildNotesFromFacebook($fields, $rawData),
        ]);
    }

    /**
     * Build notes from Facebook lead data.
     */
    private function buildNotesFromFacebook(array $fields, array $rawData): string
    {
        $notes = ["Source: Facebook Lead Ad"];

        if (!empty($rawData['ad_name'])) {
            $notes[] = "Ad: " . $rawData['ad_name'];
        }
        if (!empty($rawData['campaign_name'])) {
            $notes[] = "Campaign: " . $rawData['campaign_name'];
        }

        // Add any custom fields
        $skipFields = ['full_name', 'first_name', 'last_name', 'email', 'phone_number', 'phone', 'city', 'state'];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $skipFields) && $value) {
                $notes[] = ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
        }

        return implode("\n", $notes);
    }

    /**
     * Backfill leads from Facebook (if webhook was down).
     */
    public function backfillLeads(Integration $integration, ?string $since = null): array
    {
        $pageId = $integration->getFacebookPageId();
        $accessToken = $integration->getFacebookAccessToken();
        $apiVersion = config('services.facebook.graph_api_version', 'v18.0');

        if (!$pageId || !$accessToken) {
            throw new \RuntimeException('Facebook page ID or access token not configured');
        }

        // Get leadgen forms for page
        $formsUrl = self::GRAPH_API_BASE . $apiVersion . "/{$pageId}/leadgen_forms";

        $formsResponse = Http::get($formsUrl, [
            'access_token' => $accessToken,
            'fields' => 'id,name,status',
        ]);

        if (!$formsResponse->successful()) {
            throw new \RuntimeException('Failed to fetch Facebook forms');
        }

        $forms = $formsResponse->json('data') ?? [];
        $processedCount = 0;

        foreach ($forms as $form) {
            $formId = $form['id'];

            // Get leads for form
            $leadsUrl = self::GRAPH_API_BASE . $apiVersion . "/{$formId}/leads";
            $params = [
                'access_token' => $accessToken,
                'fields' => 'id,created_time,field_data',
                'limit' => 50,
            ];

            if ($since) {
                $params['filtering'] = json_encode([
                    ['field' => 'time_created', 'operator' => 'GREATER_THAN', 'value' => strtotime($since)]
                ]);
            }

            do {
                $leadsResponse = Http::get($leadsUrl, $params);

                if (!$leadsResponse->successful()) {
                    break;
                }

                $leads = $leadsResponse->json('data') ?? [];

                foreach ($leads as $leadData) {
                    $leadgenId = $leadData['id'];

                    // Skip if already processed
                    if (ExternalLead::exists($integration->company_id, 'facebook', $leadgenId)) {
                        continue;
                    }

                    // Process lead
                    $this->processLead($integration, $leadgenId, $leadData);
                    $processedCount++;
                }

                // Handle pagination
                $nextUrl = $leadsResponse->json('paging.next');
                if ($nextUrl) {
                    $leadsUrl = $nextUrl;
                    $params = []; // URL already contains params
                }

            } while ($nextUrl);
        }

        Log::channel('integrations')->info('Facebook backfill completed', [
            'company_id' => $integration->company_id,
            'processed' => $processedCount,
        ]);

        return [
            'success' => true,
            'message' => "Processed {$processedCount} leads",
            'count' => $processedCount,
        ];
    }
}
