<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappCampaignRecipient;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappCampaigns extends Command
{
    protected $signature = 'whatsapp:process-campaigns';
    protected $description = 'Process pending WhatsApp bulk campaigns in batches to avoid bans';

    public function handle()
    {
        Log::info('--- WhatsApp Bulk Processor Started ---');
        // Find processing or pending campaigns
        $campaigns = WhatsappCampaign::whereIn('status', ['pending', 'processing'])->get();

        if ($campaigns->isEmpty()) {
            Log::info('No pending or processing campaigns found.');
            return; // Nothing to do
        }

        Log::info('Found ' . $campaigns->count() . ' campaigns to process.');

        foreach ($campaigns as $campaign) {
            $companyId = $campaign->company_id ?? 1;

            // Get Evolution API Config from DB settings (server-level: URL + API Key)
            $apiConfig = Setting::getValue('whatsapp', 'api_config', [], $companyId);
            $apiUrl = $apiConfig['api_url'] ?? '';
            $apiKey = $apiConfig['api_key'] ?? '';

            if (empty($apiUrl) || empty($apiKey)) {
                $campaign->update([
                    'status' => 'failed',
                    'error_message' => 'WhatsApp Bulk: Evolution API not configured in settings.'
                ]);
                Log::warning('WhatsApp Bulk: Evolution API not configured in settings for company ' . $companyId);
                continue;
            }

            // Find the first connected instance (any user's instance that starts with rvcrm_)
            $instanceName = $this->findConnectedInstance($apiUrl, $apiKey);

            if (!$instanceName) {
                $campaign->update([
                    'status' => 'failed',
                    'error_message' => 'WhatsApp Bulk: No connected WhatsApp instance found. Ask a user to scan QR.'
                ]);
                Log::warning('WhatsApp Bulk: No connected WhatsApp instance found. Ask a user to scan QR.');
                continue;
            }

            Log::info("WhatsApp Bulk: Using connected instance '{$instanceName}'");

            // Mark as processing if pending
            if ($campaign->status === 'pending') {
                $campaign->update(['status' => 'processing']);
            }

            // Fetch a small batch of recipients to process in this run
            $recipients = WhatsappCampaignRecipient::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->limit(3) // 3 * 20s = 60s execution per cron cycle
                ->get();

            if ($recipients->isEmpty()) {
                $campaign->update(['status' => 'completed']);
                continue;
            }

            $template = $campaign->template;

            foreach ($recipients as $recipient) {
                // Formatting phone to International standard (Evolution API needs country code)
                $phone = preg_replace('/\D/', '', $recipient->phone_number);
                if (strlen($phone) == 10) {
                    $phone = '91' . $phone; // Assuming India default
                }

                $payload = [];
                $endpoint = '';

                // Prepare payload based on template type
                if (strtolower($template->type) === 'text') {
                    $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
                    $payload = [
                        'number' => $phone,
                        'options' => ['delay' => 1200],
                        'textMessage' => [
                            'text' => $template->message_text
                        ]
                    ];
                } else if (in_array(strtolower($template->type), ['image', 'video', 'pdf'])) {
                    $mediaEndpointType = strtolower($template->type);
                    $endpoint = "{$apiUrl}/message/sendMedia/{$instanceName}";

                    // Convert local path to absolute URL for Evolution API to download
                    $mediaUrl = asset('storage/' . $template->media_path);

                    $payload = [
                        'number' => $phone,
                        'options' => ['delay' => 1200],
                        'mediaMessage' => [
                            'mediatype' => strtolower($template->type) === 'pdf' ? 'document' : strtolower($template->type),
                            'caption' => $template->message_text ?? '',
                            'media' => $mediaUrl,
                        ]
                    ];
                } else {
                    Log::error("WhatsApp Bulk Error: Unknown template type {$template->type}");
                    $recipient->update(['status' => 'failed', 'error_message' => "Unknown template type: {$template->type}"]);
                    $campaign->increment('total_failed');
                    continue;
                }

                try {
                    // Send to Evolution API
                    $response = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json'
                    ])->post($endpoint, $payload);

                    if ($response->successful()) {
                        $recipient->update([
                            'status' => 'sent',
                            'sent_at' => now()
                        ]);
                        $campaign->increment('total_sent');
                    } else {
                        $recipient->update([
                            'status' => 'failed',
                            'error_message' => $response->body()
                        ]);
                        $campaign->increment('total_failed');
                    }
                } catch (\Exception $e) {
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
                    $campaign->increment('total_failed');
                    Log::error("WhatsApp Bulk Error: " . $e->getMessage());
                }

                // CRITICAL SECURITY LOGIC: Sleep for 20 seconds
                // This mimics human typing speed and prevents Meta from banning the number.
                sleep(20);
            }
        }
        Log::info('--- WhatsApp Bulk Processor Finished ---');
    }

    /**
     * Find the first connected instance from Evolution API (rvcrm_ instances)
     */
    private function findConnectedInstance($apiUrl, $apiKey)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$apiUrl}/instance/fetchInstances");

            if ($response->successful()) {
                $instances = $response->json();
                Log::info('WhatsApp Bulk: Fetched ' . count($instances) . ' instances from API.');

                // Find the first rvcrm_ instance that is connected
                foreach ($instances as $inst) {
                    $name = $inst['instance']['instanceName'] ?? $inst['instanceName'] ?? '';
                    $state = $inst['instance']['state'] ?? $inst['state'] ?? '';

                    Log::info("WhatsApp Bulk: Checking instance '{$name}' with state '{$state}'");

                    if (str_starts_with($name, 'rvcrm_') && in_array(strtolower($state), ['open', 'connected'])) {
                        Log::info("WhatsApp Bulk: Found match! Using '{$name}'");
                        return $name;
                    }
                }
            } else {
                Log::error('WhatsApp Bulk: API returned error status ' . $response->status() . ' while fetching instances: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Failed to fetch instances - ' . $e->getMessage());
        }

        return null;
    }
}
