<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AutoReplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp message webhook from Evolution API
     * Route: POST /webhook/whatsapp/incoming/{instanceName}
     */
    public function handleIncoming(Request $request, string $instanceName)
    {
        Log::info("Webhook received for instance: {$instanceName}", [
            'event' => $request->input('event'),
        ]);

        // Evolution API sends different event types — we only care about incoming messages
        $event = $request->input('event');

        // Handle MESSAGES_UPSERT event (incoming message)
        if ($event === 'messages.upsert') {
            $data = $request->input('data', []);

            // Get the message details
            $key = $data['key'] ?? [];
            $messageContent = $data['message'] ?? [];

            // Only process incoming messages (not our own sent messages)
            $fromMe = $key['fromMe'] ?? false;
            if ($fromMe) {
                return response()->json(['status' => 'ignored', 'reason' => 'own_message']);
            }

            // Extract sender phone (remoteJid format: 919876543210@s.whatsapp.net)
            $remoteJid = $key['remoteJid'] ?? '';
            if (empty($remoteJid) || str_contains($remoteJid, '@g.us')) {
                // Ignore group messages
                return response()->json(['status' => 'ignored', 'reason' => 'group_or_empty']);
            }

            // Extract phone number from JID
            $senderPhone = explode('@', $remoteJid)[0] ?? '';
            if (empty($senderPhone)) {
                return response()->json(['status' => 'ignored', 'reason' => 'no_phone']);
            }

            // Extract message text
            $messageText = $this->extractMessageText($messageContent);
            if (empty($messageText)) {
                $messageText = '[media]'; // Media message without text
            }

            // Process through auto-reply service
            $service = new AutoReplyService();
            $result = $service->processIncomingMessage($instanceName, $senderPhone, $messageText);

            return response()->json($result);
        }

        // For other events, just acknowledge
        return response()->json(['status' => 'ok', 'event' => $event]);
    }

    /**
     * Extract text content from Evolution API message object
     */
    private function extractMessageText(array $message): string
    {
        // Text message
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        // Extended text message (with link preview etc.)
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        // Image/video/document with caption
        if (isset($message['imageMessage']['caption'])) {
            return $message['imageMessage']['caption'];
        }
        if (isset($message['videoMessage']['caption'])) {
            return $message['videoMessage']['caption'];
        }
        if (isset($message['documentMessage']['caption'])) {
            return $message['documentMessage']['caption'];
        }

        // Button response
        if (isset($message['buttonsResponseMessage']['selectedDisplayText'])) {
            return $message['buttonsResponseMessage']['selectedDisplayText'];
        }

        // List response
        if (isset($message['listResponseMessage']['title'])) {
            return $message['listResponseMessage']['title'];
        }

        return '';
    }
}
