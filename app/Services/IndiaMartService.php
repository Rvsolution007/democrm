<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\ExternalLead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class IndiaMartService
{
    private const API_BASE_URL = 'https://mapi.indiamart.com/wservce/crm/crmListing/v2/';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1000; // milliseconds

    /**
     * Pull leads from IndiaMART for a company.
     */
    public function pullLeads(Integration $integration): array
    {
        if ($integration->provider !== 'indiamart') {
            throw new \InvalidArgumentException('Invalid integration provider');
        }

        $apiKey = $integration->getIndiamartApiKey();
        $mobile = $integration->getIndiamartMobile();

        if (!$apiKey || !$mobile) {
            throw new \RuntimeException('IndiaMART API key or mobile not configured');
        }

        $lastFetchedAt = $integration->getIndiamartLastFetchedAt();
        $fetchWindowMinutes = $integration->getSetting('fetch_window_minutes', 15);

        // Build query params
        $params = [
            'glusr_crm_key' => $apiKey,
            'start_time' => $lastFetchedAt
                ? $lastFetchedAt->modify("-{$fetchWindowMinutes} minutes")->format('d-M-Y H:i:s')
                : now()->subDay()->format('d-M-Y H:i:s'),
            'end_time' => now()->format('d-M-Y H:i:s'),
        ];

        Log::channel('integrations')->info('IndiaMART pull started', [
            'company_id' => $integration->company_id,
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'],
        ]);

        try {
            $response = $this->makeRequest(self::API_BASE_URL, $params);

            // Update last fetched timestamp
            $integration->setIndiamartLastFetchedAt(now());
            $integration->updateLastSync();

            if (!isset($response['RESPONSE']) || $response['RESPONSE'] === 'Failed') {
                Log::channel('integrations')->warning('IndiaMART API returned failure', [
                    'response' => $response,
                ]);
                return ['success' => false, 'message' => $response['MESSAGE'] ?? 'Unknown error', 'count' => 0];
            }

            $leads = $response['RESPONSE'] ?? [];
            if (!is_array($leads)) {
                $leads = [];
            }

            $processedCount = $this->processLeads($integration, $leads);

            Log::channel('integrations')->info('IndiaMART pull completed', [
                'company_id' => $integration->company_id,
                'total_leads' => count($leads),
                'processed' => $processedCount,
            ]);

            return [
                'success' => true,
                'message' => "Processed {$processedCount} leads",
                'count' => $processedCount,
            ];

        } catch (RequestException $e) {
            $error = "IndiaMART API error: " . $e->getMessage();
            Log::channel('integrations')->error($error);
            $integration->markError($error);
            throw $e;
        }
    }

    /**
     * Process fetched leads.
     */
    private function processLeads(Integration $integration, array $leads): int
    {
        $processed = 0;
        $companyId = $integration->company_id;

        // Get field mapping
        $fieldMapping = $this->getFieldMapping($integration);

        foreach ($leads as $leadData) {
            $externalId = $leadData['UNIQUE_QUERY_ID'] ?? null;

            if (!$externalId) {
                continue;
            }

            // Check for existing external lead (dedupe)
            if (ExternalLead::exists($companyId, 'indiamart', $externalId)) {
                continue;
            }

            // Create or find lead by phone/email (dedupe by contact info)
            $lead = $this->findOrCreateLead($companyId, $leadData, $fieldMapping);

            // Create external lead record
            ExternalLead::create([
                'company_id' => $companyId,
                'provider' => 'indiamart',
                'external_id' => $externalId,
                'lead_id' => $lead->id,
                'payload' => $leadData,
                'received_at' => $this->parseIndiaMartDate($leadData['QUERY_TIME'] ?? null) ?? now(),
            ]);

            $processed++;
        }

        return $processed;
    }

    /**
     * Find existing lead by phone/email or create new.
     */
    private function findOrCreateLead(int $companyId, array $data, array $fieldMapping): Lead
    {
        $phone = $data['SENDERMOBILE'] ?? $data['SENDER_MOBILE_ALT'] ?? null;
        $email = $data['SENDEREMAIL'] ?? $data['SENDER_EMAIL_ALT'] ?? null;

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
            // Update existing lead with new info if empty
            $updates = [];
            if (empty($existingLead->email) && $email) {
                $updates['email'] = $email;
            }
            if (empty($existingLead->city) && !empty($data['SENDER_CITY'])) {
                $updates['city'] = $data['SENDER_CITY'];
            }
            if (!empty($updates)) {
                $existingLead->update($updates);
            }

            return $existingLead;
        }

        // Create new lead
        return Lead::create([
            'company_id' => $companyId,
            'source' => 'indiamart',
            'source_provider' => 'indiamart',
            'source_external_id' => $data['UNIQUE_QUERY_ID'] ?? null,
            'raw_source_payload' => $data,
            'name' => $data['SENDER_NAME'] ?? 'Unknown',
            'phone' => $phone,
            'email' => $email,
            'city' => $data['SENDER_CITY'] ?? null,
            'state' => $data['SENDER_STATE'] ?? null,
            'stage' => 'new',
            'query_type' => $data['QUERY_TYPE'] ?? null,
            'query_message' => $data['QUERY_MESSAGE'] ?? $data['SUBJECT'] ?? null,
            'product_name' => $data['QUERY_PRODUCT_NAME'] ?? null,
            'notes' => $this->buildNotesFromIndiamart($data),
        ]);
    }

    /**
     * Build notes from IndiaMART data.
     */
    private function buildNotesFromIndiamart(array $data): string
    {
        $notes = [];

        if (!empty($data['QUERY_MESSAGE'])) {
            $notes[] = "Query: " . $data['QUERY_MESSAGE'];
        }
        if (!empty($data['QUERY_PRODUCT_NAME'])) {
            $notes[] = "Product: " . $data['QUERY_PRODUCT_NAME'];
        }
        if (!empty($data['QUERY_TYPE'])) {
            $notes[] = "Type: " . $data['QUERY_TYPE'];
        }
        if (!empty($data['SENDER_COMPANY'])) {
            $notes[] = "Company: " . $data['SENDER_COMPANY'];
        }

        return implode("\n", $notes);
    }

    /**
     * Get field mapping for company.
     */
    private function getFieldMapping(Integration $integration): array
    {
        $customMapping = $integration->getSetting('field_mapping');

        if (!empty($customMapping)) {
            return $customMapping;
        }

        return LeadSource::getDefaultFieldMapping('indiamart');
    }

    /**
     * Parse IndiaMART date format.
     */
    private function parseIndiaMartDate(?string $dateStr): ?\DateTime
    {
        if (!$dateStr) {
            return null;
        }

        try {
            return new \DateTime($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Make HTTP request with retry logic.
     */
    private function makeRequest(string $url, array $params): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(30)
                    ->retry(3, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                throw new RequestException($response);

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY * 1000 * pow(2, $attempt - 1)); // Exponential backoff
                }
            }
        }

        throw $lastException;
    }
}
