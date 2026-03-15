<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Get the notification data
        $data = $notification->toArray($notifiable);
        
        // We only proceed if we have a phone number for the user
        $phone = $notifiable->phone;
        if (empty($phone)) {
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
            Log::warning('WhatsApp Channel: Evolution API not configured in settings for company ' . $companyId);
            return;
        }

        // Generate the expected instance name for this user Option 1 (User to User)
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $notifiable->name));
        $instanceName = 'rvcrm_' . $cleanName . '_' . $notifiable->id;

        // Prepare the message text
        $messageText = "*" . ($data['title'] ?? 'Notification') . "*\n\n";
        $messageText .= $data['message'] ?? '';
        
        if (!empty($data['url'])) {
            $messageText .= "\n\n🔗 " . $data['url'];
        }

        try {
            // Text only endpoint
            $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
            $payload = [
                'number' => $phone,
                'text' => $messageText,
            ];
            
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(30)->post($endpoint, $payload);
            
            if (!$response->successful()) {
                Log::error("WhatsApp Channel API Error: " . $response->body());
            } else {
                Log::info("WhatsApp Channel: Successfully sent notification to {$phone} via instance {$instanceName}");
            }

        } catch (\Exception $e) {
            Log::error("WhatsApp Channel Error for {$phone}: " . $e->getMessage());
        }
    }
}
