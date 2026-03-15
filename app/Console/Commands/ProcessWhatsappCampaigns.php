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
            $apiUrl = rtrim($apiConfig['api_url'] ?? '', '/');
            $apiKey = $apiConfig['api_key'] ?? '';

            if (empty($apiUrl) || empty($apiKey)) {
                $campaign->update([
                    'status' => 'failed',
                    'error_message' => 'WhatsApp Bulk: Evolution API not configured in settings.'
                ]);
                Log::warning('WhatsApp Bulk: Evolution API not configured in settings for company ' . $companyId);
                continue;
            }

            // Get the user who created the campaign
            $user = $campaign->user;
            if (!$user) {
                $campaign->update([
                    'status' => 'failed',
                    'error_message' => 'WhatsApp Bulk: User who created this campaign does not exist anymore.'
                ]);
                Log::warning('WhatsApp Bulk: User missing for campaign ' . $campaign->id);
                continue;
            }

            // Generate the expected instance name for this user
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
            $instanceName = 'rvcrm_' . $cleanName . '_' . $user->id;

            $isConnected = $this->checkInstanceConnection($apiUrl, $apiKey, $instanceName);

            if (!$isConnected) {
                $campaign->update([
                    'status' => 'failed',
                    'error_message' => 'WhatsApp Bulk: No connected WhatsApp instance found for your account. Please scan QR in WhatsApp Connect.'
                ]);
                Log::warning("WhatsApp Bulk: Instance '{$instanceName}' is not connected. User needs to scan QR.");
                continue;
            }

            Log::info("WhatsApp Bulk: Using connected instance '{$instanceName}' for user {$user->id}");

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

                $mediaFiles = is_array($template->media_files) ? $template->media_files : [];
                $textMsg = trim($template->message_text ?? '');
                $totalMedia = count($mediaFiles);

                try {
                    if ($totalMedia === 0 && !empty($textMsg)) {
                        // Text only
                        $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
                        $payload = [
                            'number' => $phone,
                            'text' => $textMsg,
                            'delay' => 1200,
                        ];
                        
                        $response = Http::withHeaders([
                            'apikey' => $apiKey,
                            'Content-Type' => 'application/json'
                        ])->timeout(60)->post($endpoint, $payload);
                        
                        if (!$response->successful()) {
                            throw new \Exception("API Error: " . $response->body());
                        }

                    } else if ($totalMedia > 0) {
                        // Multiple media files
                        $endpoint = "{$apiUrl}/message/sendMedia/{$instanceName}";

                        foreach ($mediaFiles as $index => $media) {
                            $filePath = storage_path('app/public/' . $media['path']);
                            if (!file_exists($filePath)) {
                                Log::error("WhatsApp Bulk: Media file not found at {$filePath}");
                                continue; // Skip missing file but try others
                            }

                            $fileContent = file_get_contents($filePath);
                            $mimeType = mime_content_type($filePath);
                            $base64Media = base64_encode($fileContent);
                            
                            // Determine type dynamically or fallback to template type
                            $ext = strtolower(pathinfo($media['path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) $mediaType = 'image';
                            elseif (in_array($ext, ['mp4', '3gp'])) $mediaType = 'video';
                            else $mediaType = 'document';

                            $isLast = ($index === $totalMedia - 1);
                            $caption = ($isLast && !empty($textMsg)) ? $textMsg : '';

                            $payload = [
                                'number' => $phone,
                                'mediatype' => $mediaType,
                                'mimetype' => $mimeType,
                                'caption' => $caption,
                                'media' => $base64Media,
                                'fileName' => basename($media['path']),
                                'delay' => 1200,
                            ];

                            $currentNum = $index + 1;
                            Log::info("WhatsApp Bulk: Sending {$mediaType} ({$currentNum}/{$totalMedia}) to {$phone}");
                            
                            $response = Http::withHeaders([
                                'apikey' => $apiKey,
                                'Content-Type' => 'application/json'
                            ])->timeout(60)->post($endpoint, $payload);
                            
                            if (!$response->successful()) {
                                throw new \Exception("API Error on media {$index}: " . $response->body());
                            }
                            
                            // Prevent spam banning if multiple files go out too fast
                            if (!$isLast) sleep(2);
                        }
                    } else {
                        throw new \Exception("Template has no media and no text.");
                    }

                    // If we reach here, it means all parts succeeded
                    $recipient->update([
                        'status' => 'sent',
                        'sent_at' => now()
                    ]);
                    $campaign->increment('total_sent');
                    Log::info("WhatsApp Bulk: Successfully sent sequence to {$phone}");

                } catch (\Exception $e) {
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
                    $campaign->increment('total_failed');
                    Log::error("WhatsApp Bulk Error for {$phone}: " . $e->getMessage());
                }

                // CRITICAL SECURITY LOGIC: Sleep for 20 seconds
                // This mimics human typing speed and prevents Meta from banning the number.
                sleep(20);
            }
        }
        Log::info('--- WhatsApp Bulk Processor Finished ---');
    }

    /**
     * Check if a specific instance is connected
     */
    private function checkInstanceConnection($apiUrl, $apiKey, $instanceName)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$apiUrl}/instance/connectionState/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                $state = $data['instance']['state'] ?? $data['state'] ?? 'unknown';
                Log::info("WhatsApp Bulk: Checked instance '{$instanceName}', state is '{$state}'");

                $validStates = ['open', 'connected', 'connecting']; // Allow connecting as a temporary state

                if (in_array(strtolower($state), $validStates)) {
                    return true;
                }
            } else if ($response->status() === 404) {
                Log::warning("WhatsApp Bulk: Instance '{$instanceName}' not found in API.");
            } else {
                Log::error('WhatsApp Bulk: API returned error status ' . $response->status() . ' while checking instance state: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Failed to fetch instance state - ' . $e->getMessage());
        }

        return false;
    }
}
