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
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            // Get the notification data
            $data = $notification->toArray($notifiable);

            // We only proceed if we have a phone number for the user
            $phone = $notifiable->phone;
            if (empty($phone)) {
                Log::info('WhatsApp Channel: Skipped — user has no phone number (user_id: ' . $notifiable->id . ')');
                return;
            }

            // Format phone to International standard (Evolution API needs country code)
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) == 10) {
                $phone = '91' . $phone; // Assuming India default
            }

            // Get Evolution API Config from DB settings (server-level: URL + API Key)
            $companyId = $notifiable->company_id ?? 1;
            $apiConfig = Setting::getValue('whatsapp', 'api_config', [], $companyId);
            $apiUrl = rtrim($apiConfig['api_url'] ?? '', '/');
            $apiKey = $apiConfig['api_key'] ?? '';

            if (empty($apiUrl) || empty($apiKey)) {
                Log::info('WhatsApp Channel: Skipped — API not configured (company_id: ' . $companyId . ')');
                return;
            }

            // Generate the expected instance name for this user
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $notifiable->name));
            $instanceName = 'rvcrm_' . $cleanName . '_' . $notifiable->id;

            // Quick connection check first — if instance is not connected, skip immediately
            if (!$this->isInstanceConnected($apiUrl, $apiKey, $instanceName)) {
                Log::info("WhatsApp Channel: Skipped — instance '{$instanceName}' not connected.");
                return;
            }

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

            // Send text message via Evolution API (short 5s timeout)
            $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
            $payload = [
                'number' => $phone,
                'text'   => $messageText,
            ];

            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json'
            ])->connectTimeout(5)->timeout(10)->post($endpoint, $payload);

            if (!$response->successful()) {
                Log::warning("WhatsApp Channel: API returned error for {$phone} — " . $response->body());
            } else {
                Log::info("WhatsApp Channel: ✅ Sent notification to {$phone} via {$instanceName}");
            }

        } catch (\Exception $e) {
            // Never let WhatsApp channel crash the notification pipeline
            Log::error("WhatsApp Channel: Exception — " . $e->getMessage());
        }
    }

    /**
     * Quick check if the user's WhatsApp instance is connected.
     * Returns false immediately if not reachable (2s connect timeout).
     */
    private function isInstanceConnected(string $apiUrl, string $apiKey, string $instanceName): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])->connectTimeout(3)->timeout(5)->get("{$apiUrl}/instance/connectionState/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                $state = strtolower($data['instance']['state'] ?? $data['state'] ?? 'unknown');
                return in_array($state, ['open', 'connected']);
            }
        } catch (\Exception $e) {
            Log::warning("WhatsApp Channel: Connection check failed for '{$instanceName}' — " . $e->getMessage());
        }

        return false;
    }
}
