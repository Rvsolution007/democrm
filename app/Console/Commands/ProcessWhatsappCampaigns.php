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
        // Find processing or pending campaigns
        $campaigns = WhatsappCampaign::whereIn('status', ['pending', 'processing'])->get();

        if ($campaigns->isEmpty()) {
            return; // Nothing to do
        }

        // Get Evolution API Config from DB settings (server-level: URL + API Key)
        $apiConfig = Setting::getValue('whatsapp', 'api_config', [], 1);
        $apiUrl = $apiConfig['api_url'] ?? '';
        $apiKey = $apiConfig['api_key'] ?? '';

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning('WhatsApp Bulk: Evolution API not configured in settings. Skipping.');
            return;
        }

        // Find the first connected instance (any user's instance that starts with rvcrm_)
        $instanceName = $this->findConnectedInstance($apiUrl, $apiKey);

        if (!$instanceName) {
            Log::warning('WhatsApp Bulk: No connected WhatsApp instance found. Ask a user to scan QR.');
            return;
        }

        Log::info("WhatsApp Bulk: Using connected instance '{$instanceName}'");

        foreach ($campaigns as $campaign) {
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
                if ($template->type === 'text') {
                    $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
                    $payload = [
                        'number' => $phone,
                        'options' => ['delay' => 1200],
                        'textMessage' => [
                            'text' => $template->message_text
                        ]
                    ];
                } else if (in_array($template->type, ['image', 'video', 'pdf'])) {
                    $endpoint = "{$apiUrl}/message/sendMedia/{$instanceName}";

                    // Convert local path to absolute URL for Evolution API to download
                    $mediaUrl = asset('storage/' . $template->media_path);

                    $payload = [
                        'number' => $phone,
                        'options' => ['delay' => 1200],
                        'mediaMessage' => [
                            'mediatype' => $template->type === 'pdf' ? 'document' : $template->type,
                            'caption' => $template->message_text ?? '',
                            'media' => $mediaUrl,
                        ]
                    ];
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

                // Find the first rvcrm_ instance that is connected
                foreach ($instances as $inst) {
                    $name = $inst['instance']['instanceName'] ?? $inst['instanceName'] ?? '';
                    $state = $inst['instance']['state'] ?? $inst['state'] ?? '';

                    if (str_starts_with($name, 'rvcrm_') && in_array(strtolower($state), ['open', 'connected'])) {
                        return $name;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Failed to fetch instances - ' . $e->getMessage());
        }

        return null;
    }
}
