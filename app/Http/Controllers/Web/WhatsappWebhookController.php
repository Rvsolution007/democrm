<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAutoReplyJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp message webhook from Evolution API
     * Route: POST /webhook/whatsapp/incoming/{instanceName}
     * 
     * IMPORTANT: Returns 200 OK immediately, then processes auto-reply AFTER response.
     * This prevents shared hosting timeout when many webhooks arrive simultaneously.
     */
    public function handleIncoming(Request $request, string $instanceName)
    {
        $event = $request->input('event');

        // Log webhook receipt (minimal — detailed logging happens in background)
        Log::info("Webhook received for instance: {$instanceName}", [
            'event' => $event,
        ]);

        // Evolution API sends different event types — we only care about incoming messages
        if ($event !== 'messages.upsert') {
            return response()->json(['status' => 'ok', 'event' => $event]);
        }

        $data = $request->input('data', []);
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
            return response()->json(['status' => 'ignored', 'reason' => 'group_or_empty']);
        }

        $senderPhone = explode('@', $remoteJid)[0] ?? '';
        if (empty($senderPhone)) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_phone']);
        }

        // Extract message text BEFORE dispatching (we need the full message object)
        $messageText = $this->extractMessageText($messageContent);

        Log::info("AutoReply: Extracted message text from {$senderPhone}", [
            'extracted_text' => $messageText,
            'message_keys' => array_keys($messageContent),
        ]);

        if (empty($messageText)) {
            $messageText = '[media]';
        }

        // ═══════════════════════════════════════════════════════════
        // DISPATCH TO QUEUE — Process auto-reply in background via queued job
        // This is the key fix: webhook returns 200 OK instantly,
        // auto-reply processing happens via queue worker (cron-driven).
        // Evolution API won't timeout, shared hosting won't drop requests.
        // Jobs are stored in DB and retried automatically if they fail.
        // ═══════════════════════════════════════════════════════════
        ProcessAutoReplyJob::dispatch($instanceName, $senderPhone, $messageText);

        // Return 200 OK immediately — Evolution API is happy, no timeout
        return response()->json(['status' => 'queued', 'message' => 'Processing in background']);
    }

    /**
     * Extract text content from Evolution API message object
     * Supports all known WhatsApp message types including:
     * - Regular text, extended text
     * - Media captions (image/video/document)
     * - Button responses (from template buttons, ads)
     * - List responses
     * - Template button replies (Facebook/Instagram ad responses)
     * - Interactive messages
     * - Ephemeral/ViewOnce wrapper messages
     * - Highly structured messages (HSM)
     * - Contact and location messages
     */
    private function extractMessageText(array $message): string
    {
        // ═══════════════════════════════════════════════════════
        // 1. WRAPPER MESSAGES — unwrap first before checking content
        //    These wrap other message types inside them
        // ═══════════════════════════════════════════════════════

        // Ephemeral message (disappearing messages — wraps inner message)
        if (isset($message['ephemeralMessage']['message'])) {
            return $this->extractMessageText($message['ephemeralMessage']['message']);
        }

        // View once message (wraps inner message)
        if (isset($message['viewOnceMessage']['message'])) {
            return $this->extractMessageText($message['viewOnceMessage']['message']);
        }

        // View once message V2 (newer version)
        if (isset($message['viewOnceMessageV2']['message'])) {
            return $this->extractMessageText($message['viewOnceMessageV2']['message']);
        }

        // Document with caption message (wrapper)
        if (isset($message['documentWithCaptionMessage']['message'])) {
            return $this->extractMessageText($message['documentWithCaptionMessage']['message']);
        }

        // ═══════════════════════════════════════════════════════
        // 2. DIRECT TEXT MESSAGES
        // ═══════════════════════════════════════════════════════

        // Regular text message
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        // Extended text message (with link preview, formatting etc.)
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        // ═══════════════════════════════════════════════════════
        // 3. BUTTON & TEMPLATE RESPONSES (Facebook/Instagram Ads)
        //    This is the key fix — ad-generated messages use these types
        // ═══════════════════════════════════════════════════════

        // Template button reply (from Facebook/Instagram ad "Send Message" button)
        if (isset($message['templateButtonReplyMessage']['selectedDisplayText'])) {
            return $message['templateButtonReplyMessage']['selectedDisplayText'];
        }

        // Regular button response
        if (isset($message['buttonsResponseMessage']['selectedDisplayText'])) {
            return $message['buttonsResponseMessage']['selectedDisplayText'];
        }

        // List response
        if (isset($message['listResponseMessage']['title'])) {
            return $message['listResponseMessage']['title'];
        }

        // Interactive response message (newer WhatsApp interactive messages)
        if (isset($message['interactiveResponseMessage']['body']['text'])) {
            return $message['interactiveResponseMessage']['body']['text'];
        }

        // Interactive response — nativeFlowResponseMessage (button flows)
        if (isset($message['interactiveResponseMessage']['nativeFlowResponseMessage']['paramsJson'])) {
            $params = json_decode($message['interactiveResponseMessage']['nativeFlowResponseMessage']['paramsJson'], true);
            if (isset($params['body'])) {
                return $params['body'];
            }
        }

        // Interactive message (CTA button, product, etc.)
        if (isset($message['interactiveMessage']['body']['text'])) {
            return $message['interactiveMessage']['body']['text'];
        }

        // Highly structured message (HSM — older template messages)
        if (isset($message['highlyStructuredMessage']['hydratedHsm']['hydratedTemplate']['hydratedBodyText'])) {
            return $message['highlyStructuredMessage']['hydratedHsm']['hydratedTemplate']['hydratedBodyText'];
        }

        // ═══════════════════════════════════════════════════════
        // 4. MEDIA MESSAGES WITH CAPTIONS
        // ═══════════════════════════════════════════════════════

        // Image with caption
        if (isset($message['imageMessage']['caption'])) {
            return $message['imageMessage']['caption'];
        }

        // Video with caption
        if (isset($message['videoMessage']['caption'])) {
            return $message['videoMessage']['caption'];
        }

        // Document with caption
        if (isset($message['documentMessage']['caption'])) {
            return $message['documentMessage']['caption'];
        }

        // Audio message (no text, but acknowledge)
        if (isset($message['audioMessage'])) {
            return '[audio]';
        }

        // Sticker message
        if (isset($message['stickerMessage'])) {
            return '[sticker]';
        }

        // ═══════════════════════════════════════════════════════
        // 5. OTHER MESSAGE TYPES
        // ═══════════════════════════════════════════════════════

        // Contact message
        if (isset($message['contactMessage']['displayName'])) {
            return '[contact: ' . $message['contactMessage']['displayName'] . ']';
        }

        // Location message
        if (isset($message['locationMessage'])) {
            return '[location]';
        }

        // Live location
        if (isset($message['liveLocationMessage'])) {
            return '[live_location]';
        }

        // Protocol message (read receipts, etc.) — ignore
        if (isset($message['protocolMessage'])) {
            return '';
        }

        // Reaction message — ignore
        if (isset($message['reactionMessage'])) {
            return '';
        }

        // If we still can't identify the message type, log it for debugging
        if (!empty($message)) {
            Log::warning('AutoReply: Unknown message type received', [
                'message_keys' => array_keys($message),
                'message_preview' => json_encode(array_slice($message, 0, 3, true)),
            ]);
        }

        return '';
    }
}

