<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    /**
     * Send the given notification via WhatsApp.
     * Uses the user's own connected WhatsApp instance (Option 1: sender = receiver).
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            Log::info("WhatsApp Channel: === START === Notification for user_id: {$notifiable->id} ({$notifiable->name})");

            // Get the notification data
            $data = $notification->toArray($notifiable);

            // We only proceed if we have a phone number for the user
            $phone = $notifiable->phone;
            if (empty($phone)) {
                Log::warning("WhatsApp Channel: SKIP — user_id:{$notifiable->id} has no phone number.");
                return;
            }
            Log::info("WhatsApp Channel: User phone found: {$phone}");

            // Format phone to International standard (Evolution API needs country code)
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) == 10) {
                $phone = '91' . $phone;
            }
            Log::info("WhatsApp Channel: Formatted phone: {$phone}");

            // Get Evolution API Config from DB settings
            $companyId = $notifiable->company_id ?? 1;
            $apiConfig = Setting::getValue('whatsapp', 'api_config', [], $companyId);
            $apiUrl = rtrim($apiConfig['api_url'] ?? '', '/');
            $apiKey = $apiConfig['api_key'] ?? '';

            Log::info("WhatsApp Channel: API Config — company_id:{$companyId}, api_url:" . ($apiUrl ?: 'EMPTY') . ", api_key:" . ($apiKey ? 'SET(' . strlen($apiKey) . ' chars)' : 'EMPTY'));

            if (empty($apiUrl) || empty($apiKey)) {
                Log::warning("WhatsApp Channel: SKIP — API not configured for company_id:{$companyId}");
                return;
            }

            // Generate the expected instance name for this user
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $notifiable->name));
            $instanceName = 'rvcrm_' . $cleanName . '_' . $notifiable->id;
            Log::info("WhatsApp Channel: Instance name: {$instanceName}");

            // Quick connection check
            $connectionResult = $this->checkInstanceConnection($apiUrl, $apiKey, $instanceName);
            if (!$connectionResult['connected']) {
                Log::warning("WhatsApp Channel: SKIP — instance '{$instanceName}' not connected. State: {$connectionResult['state']}. Response: {$connectionResult['raw']}");
                return;
            }
            Log::info("WhatsApp Channel: Instance '{$instanceName}' is CONNECTED (state: {$connectionResult['state']})");

            // Prepare the message text
            $notificationType = $data['type'] ?? 'notification';
            $title = match ($notificationType) {
                'assigned'        => '📌 New Assignment',
                'followup_today'  => '⏰ Follow-up Reminder',
                'overdue'         => '🚨 Overdue Alert',
                default           => '🔔 Notification',
            };

            $messageText = "*{$title}*\n\n";
            $messageText .= $data['message'] ?? '';

            if (!empty($data['url'])) {
                $baseUrl = config('app.url', url('/'));
                $fullUrl = str_starts_with($data['url'], 'http') ? $data['url'] : rtrim($baseUrl, '/') . '/' . ltrim($data['url'], '/');
                $messageText .= "\n\n🔗 {$fullUrl}";
            }

            Log::info("WhatsApp Channel: Sending message to {$phone} — " . substr($messageText, 0, 100) . "...");

            // Send text message via Evolution API
            $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
            $payload = [
                'number' => $phone,
                'text'   => $messageText,
            ];

            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json'
            ])->connectTimeout(5)->timeout(10)->post($endpoint, $payload);

            $statusCode = $response->status();
            $body = $response->body();

            if (!$response->successful()) {
                Log::error("WhatsApp Channel: ❌ FAILED — Status:{$statusCode}, Body: {$body}");
            } else {
                Log::info("WhatsApp Channel: ✅ SUCCESS — Sent to {$phone} via {$instanceName}. Response: {$body}");
            }

        } catch (\Exception $e) {
            Log::error("WhatsApp Channel: 💥 EXCEPTION — " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }

    /**
     * Check if the user's WhatsApp instance is connected.
     * Returns detailed info for logging.
     */
    private function checkInstanceConnection(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $result = ['connected' => false, 'state' => 'unknown', 'raw' => ''];

        try {
            $url = "{$apiUrl}/instance/connectionState/{$instanceName}";
            Log::info("WhatsApp Channel: Checking connection at: {$url}");

            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])->connectTimeout(3)->timeout(5)->get($url);

            $result['raw'] = substr($response->body(), 0, 300);

            if ($response->successful()) {
                $data = $response->json();
                $state = strtolower($data['instance']['state'] ?? $data['state'] ?? 'unknown');
                $result['state'] = $state;
                $result['connected'] = in_array($state, ['open', 'connected']);
            } else {
                $result['state'] = 'http_error_' . $response->status();
            }
        } catch (\Exception $e) {
            $result['state'] = 'exception';
            $result['raw'] = $e->getMessage();
            Log::warning("WhatsApp Channel: Connection check exception for '{$instanceName}' — " . $e->getMessage());
        }

        return $result;
    }
}
