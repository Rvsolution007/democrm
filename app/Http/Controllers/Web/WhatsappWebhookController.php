<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAutoReplyJob;
use App\Jobs\ProcessAIChatJob;
use App\Jobs\ProcessListBotJob;
use App\Models\Company;
use App\Models\Setting;
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

        // Log webhook receipt (detailed for debugging)
        Log::info("Webhook received for instance: {$instanceName}", [
            'event' => $event,
            'method' => $request->method(),
            'payload_preview' => substr(json_encode($request->all()), 0, 500)
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
        
        // ── Webhook Deduplication ──
        // Evolution API retries Webhooks aggressively if our server doesn't respond instantly.
        // Since we dispatch jobs synchronously (onConnection sync), we must block duplicate IDs.
        $messageId = $key['id'] ?? null;
        if ($messageId) {
            $cacheKey = "webhook_processed_{$messageId}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info("Webhook: Ignored duplicate message (Evolution API retry)", ['messageId' => $messageId]);
                return response()->json(['status' => 'ignored', 'reason' => 'duplicate']);
            }
            // Mark as processed for the next 15 minutes
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(15));
        }

        // Extract Interactive List rowId if this is a list response
        $listRowId = $messageContent['listResponseMessage']['singleSelectReply']['selectedRowId'] ?? null;

        Log::info("Webhook: Extracted message text from {$senderPhone}", [
            'extracted_text' => $messageText,
            'message_keys' => array_keys($messageContent),
            'list_row_id' => $listRowId,
        ]);

        if (empty($messageText)) {
            $messageText = '[media]';
        }

        // Extract reply context (quoted message) if user replied to a specific message
        $replyContext = $this->extractReplyContext($data);

        // Extract image URL if user sent an image
        $imageUrl = $this->extractImageUrl($data, $instanceName);

        // ═══════════════════════════════════════════════════════════
        // ROUTING: 3-Way Bot Mode (AI Bot / List Bot / Auto-Reply)
        // Only ONE mode is active at a time per company.
        // Package enforcement: if mode isn't available, fallback.
        // ═══════════════════════════════════════════════════════════
        $userId = $this->getUserIdFromInstance($instanceName);
        $companyId = 1;
        if ($userId) {
            $user = \App\Models\User::find($userId);
            $companyId = $user->company_id ?? 1;
        }

        // Get bot mode (new 3-way setting)
        $botMode = Setting::getValue('whatsapp', 'bot_mode', null, $companyId);

        // Backward compatibility: old ai_bot.enabled → new bot_mode
        if ($botMode === null) {
            $oldAiBot = Setting::getValue('ai_bot', 'enabled', false, $companyId);
            $botMode = $oldAiBot ? 'ai_bot' : 'auto_reply';
        }

        // Package enforcement — fallback if company doesn't have the feature
        $company = Company::find($companyId);
        if ($botMode === 'ai_bot' && $company && !$company->hasFeature('ai_bot')) {
            $botMode = $company->hasFeature('list_bot') ? 'list_bot' : 'auto_reply';
        }
        if ($botMode === 'list_bot' && $company && !$company->hasFeature('list_bot')) {
            $botMode = 'auto_reply';
        }

        switch ($botMode) {
            case 'ai_bot':
                ProcessAIChatJob::dispatch($instanceName, $senderPhone, $messageText, $replyContext, $imageUrl, $listRowId)->onConnection('sync');
                Log::info("Webhook: Dispatched AI Chat Job for {$senderPhone}", ['mode' => 'ai_bot', 'rowId' => $listRowId]);
                break;

            case 'list_bot':
                ProcessListBotJob::dispatch($instanceName, $senderPhone, $messageText, $listRowId)->onConnection('sync');
                Log::info("Webhook: Dispatched List Bot Job for {$senderPhone}", ['mode' => 'list_bot', 'rowId' => $listRowId]);
                break;

            default: // 'auto_reply'
                ProcessAutoReplyJob::dispatch($instanceName, $senderPhone, $messageText)->onConnection('sync');
                Log::info("Webhook: Dispatched Auto-Reply Job for {$senderPhone}", ['mode' => 'auto_reply']);
                break;
        }

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

    /**
     * Extract reply context (quoted message) from Evolution API data
     * When a user replies to a specific message, contextInfo contains the quoted message
     */
    private function extractReplyContext(array $data): ?array
    {
        $message = $data['message'] ?? [];
        $contextInfo = null;

        // contextInfo can be at different levels depending on message type
        $contextInfo = $message['extendedTextMessage']['contextInfo'] ?? null;

        if (!$contextInfo) {
            $contextInfo = $message['imageMessage']['contextInfo'] ?? null;
        }
        if (!$contextInfo) {
            $contextInfo = $message['videoMessage']['contextInfo'] ?? null;
        }
        if (!$contextInfo) {
            $contextInfo = $message['documentMessage']['contextInfo'] ?? null;
        }
        if (!$contextInfo) {
            // Check wrapper messages
            foreach (['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2'] as $wrapper) {
                if (isset($message[$wrapper]['message'])) {
                    $inner = $message[$wrapper]['message'];
                    foreach (['extendedTextMessage', 'imageMessage', 'conversation'] as $type) {
                        if (isset($inner[$type]['contextInfo'])) {
                            $contextInfo = $inner[$type]['contextInfo'];
                            break 2;
                        }
                    }
                }
            }
        }

        if (!$contextInfo || empty($contextInfo['quotedMessage'])) {
            return null;
        }

        // Extract quoted message text
        $quotedMessage = $contextInfo['quotedMessage'];
        $quotedText = $this->extractMessageText($quotedMessage);

        return [
            'quoted_text' => $quotedText,
            'stanza_id' => $contextInfo['stanzaId'] ?? null,
            'participant' => $contextInfo['participant'] ?? null,
        ];
    }

    /**
     * Extract image URL from Evolution API data
     * Downloads image via Evolution API if needed
     */
    private function extractImageUrl(array $data, string $instanceName): ?string
    {
        $message = $data['message'] ?? [];

        // Check for image message
        $imageMessage = $message['imageMessage'] ?? null;

        // Check in wrapper messages
        if (!$imageMessage) {
            foreach (['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2'] as $wrapper) {
                if (isset($message[$wrapper]['message']['imageMessage'])) {
                    $imageMessage = $message[$wrapper]['message']['imageMessage'];
                    break;
                }
            }
        }

        if (!$imageMessage) {
            return null;
        }

        // Evolution API v2 provides direct URL in some cases
        if (!empty($imageMessage['url'])) {
            return $imageMessage['url'];
        }

        // For base64, we'd need to save it — skip for now, use mediaUrl if available
        if (!empty($data['mediaUrl'])) {
            return $data['mediaUrl'];
        }

        // Try to get media URL from Evolution API's media endpoint
        $messageId = $data['key']['id'] ?? null;
        if ($messageId) {
            try {
                $config = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => '']);
                if (!empty($config['api_url']) && !empty($config['api_key'])) {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $config['api_key'],
                    ])->post("{$config['api_url']}/chat/getBase64FromMediaMessage/{$instanceName}", [
                        'message' => ['key' => $data['key']],
                    ]);

                    if ($response->successful()) {
                        $base64 = $response->json('base64') ?? '';
                        if ($base64) {
                            // Save to storage and return URL
                            $filename = 'ai_chat_images/' . uniqid() . '.jpg';
                            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, base64_decode($base64));
                            return url('storage/' . $filename);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Webhook: Failed to extract image: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Extract user_id from instance name (format: rvcrm_{username}_{id})
     */
    private function getUserIdFromInstance(string $instanceName): ?int
    {
        $parts = explode('_', $instanceName);
        if (count($parts) >= 3) {
            $id = (int) end($parts);
            if ($id > 0) return $id;
        }
        return null;
    }

    /**
     * Test endpoint for webhook (GET request)
     * Used to verify that the webhook URL is reachable and HTTPS redirects are working
     */
    public function testWebhook(Request $request, string $instanceName)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Webhook endpoint is reachable',
            'instance' => $instanceName,
            'method' => $request->method(),
            'url' => $request->url(),
            'is_secure' => $request->secure(),
        ]);
    }
}

