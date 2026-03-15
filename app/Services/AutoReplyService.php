<?php

namespace App\Services;

use App\Models\WhatsappAutoReplyRule;
use App\Models\WhatsappAutoReplyLog;
use App\Models\WhatsappAutoReplyBlacklist;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoReplyService
{
    /**
     * Process an incoming WhatsApp message and send auto-reply if rules match
     */
    public function processIncomingMessage(string $instanceName, string $senderPhone, string $messageText): array
    {
        Log::info("AutoReply: Processing message from {$senderPhone} on instance {$instanceName}", [
            'message' => $messageText,
        ]);

        // 1. Find the user who owns this instance
        $userId = $this->getUserIdFromInstance($instanceName);
        if (!$userId) {
            Log::warning("AutoReply: No user found for instance {$instanceName}");
            return ['status' => 'no_user', 'message' => 'No user found for this instance'];
        }

        // 2. Normalize phone number
        $cleanPhone = $this->normalizePhone($senderPhone);

        // 3. Check if number is blacklisted for this user
        if ($this->isBlacklisted($userId, $cleanPhone)) {
            $this->logReply($userId, $instanceName, null, $cleanPhone, $messageText, null, 'skipped', 'blacklist');
            return ['status' => 'skipped', 'reason' => 'blacklist'];
        }

        // 4. Load active rules for this user, sorted by priority (highest first)
        $rules = WhatsappAutoReplyRule::where('user_id', $userId)
            ->where('instance_name', $instanceName)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        if ($rules->isEmpty()) {
            return ['status' => 'no_rules', 'message' => 'No active rules for this user'];
        }

        // 5. Try each rule until one matches
        foreach ($rules as $rule) {
            $matchResult = $this->checkRuleMatch($rule, $cleanPhone, $messageText, $userId);

            if ($matchResult['matched']) {
                // Rule matched! Increment trigger counter
                $rule->increment('total_triggered');

                // Check anti-spam controls
                $spamCheck = $this->checkAntiSpam($rule, $cleanPhone, $userId);
                if ($spamCheck['blocked']) {
                    $rule->increment('total_skipped');
                    $this->logReply($userId, $instanceName, $rule->id, $cleanPhone, $messageText, $rule->template_id, 'skipped', $spamCheck['reason']);
                    Log::info("AutoReply: Skipped (anti-spam: {$spamCheck['reason']}) for {$cleanPhone} on rule {$rule->name}");
                    continue; // Try next rule
                }

                // All checks passed — send reply!
                $sendResult = $this->sendReply($rule, $instanceName, $senderPhone, $userId);

                if ($sendResult['success']) {
                    $rule->increment('total_sent');
                    $this->logReply($userId, $instanceName, $rule->id, $cleanPhone, $messageText, $rule->template_id, 'sent', null);
                    Log::info("AutoReply: Sent reply for rule '{$rule->name}' to {$cleanPhone}");
                    return ['status' => 'sent', 'rule' => $rule->name, 'template' => $rule->template->name ?? 'N/A'];
                } else {
                    $this->logReply($userId, $instanceName, $rule->id, $cleanPhone, $messageText, $rule->template_id, 'failed', $sendResult['error'] ?? 'send_failed');
                    Log::error("AutoReply: Failed to send reply for rule '{$rule->name}': " . ($sendResult['error'] ?? 'unknown'));
                    return ['status' => 'failed', 'error' => $sendResult['error'] ?? 'unknown'];
                }
            }
        }

        return ['status' => 'no_match', 'message' => 'No rules matched this message'];
    }

    /**
     * Check if a message matches a rule's trigger condition
     */
    private function checkRuleMatch(WhatsappAutoReplyRule $rule, string $phone, string $message, int $userId): array
    {
        $message = strtolower(trim($message));

        switch ($rule->match_type) {
            case 'exact':
                $keywords = $rule->keywords ?? [];
                foreach ($keywords as $keyword) {
                    if ($message === strtolower(trim($keyword))) {
                        return ['matched' => true];
                    }
                }
                return ['matched' => false];

            case 'contains':
                $keywords = $rule->keywords ?? [];
                foreach ($keywords as $keyword) {
                    if (str_contains($message, strtolower(trim($keyword)))) {
                        return ['matched' => true];
                    }
                }
                return ['matched' => false];

            case 'any_message':
                return ['matched' => true];

            case 'first_message':
                // Check if we've EVER received a message from this number for this user
                $hasHistory = WhatsappAutoReplyLog::where('user_id', $userId)
                    ->where('phone_number', $phone)
                    ->exists();
                return ['matched' => !$hasHistory];

            default:
                return ['matched' => false];
        }
    }

    /**
     * Check anti-spam controls for a rule
     */
    private function checkAntiSpam(WhatsappAutoReplyRule $rule, string $phone, int $userId): array
    {
        // 1. One-time check
        if ($rule->is_one_time) {
            $alreadySent = WhatsappAutoReplyLog::where('rule_id', $rule->id)
                ->where('phone_number', $phone)
                ->where('status', 'sent')
                ->exists();
            if ($alreadySent) {
                return ['blocked' => true, 'reason' => 'one_time'];
            }
        }

        // 2. Cooldown check
        if ($rule->cooldown_hours > 0) {
            $lastSent = WhatsappAutoReplyLog::where('rule_id', $rule->id)
                ->where('phone_number', $phone)
                ->where('status', 'sent')
                ->where('sent_at', '>=', Carbon::now()->subHours($rule->cooldown_hours))
                ->exists();
            if ($lastSent) {
                return ['blocked' => true, 'reason' => 'cooldown'];
            }
        }

        // 3. Business hours check
        if ($rule->business_hours_only && $rule->business_hours_start && $rule->business_hours_end) {
            $now = Carbon::now();
            $start = Carbon::createFromTimeString($rule->business_hours_start);
            $end = Carbon::createFromTimeString($rule->business_hours_end);

            // Handle overnight ranges (e.g., 21:00 to 09:00)
            if ($start->gt($end)) {
                // Overnight: active from start to midnight AND midnight to end
                $inBusinessHours = $now->gte($start) || $now->lte($end);
            } else {
                $inBusinessHours = $now->gte($start) && $now->lte($end);
            }

            if (!$inBusinessHours) {
                return ['blocked' => true, 'reason' => 'hours'];
            }
        }

        // 4. Max replies per day check
        if ($rule->max_replies_per_day > 0) {
            $todayCount = WhatsappAutoReplyLog::where('user_id', $userId)
                ->where('phone_number', $phone)
                ->where('status', 'sent')
                ->whereDate('created_at', today())
                ->count();
            if ($todayCount >= $rule->max_replies_per_day) {
                return ['blocked' => true, 'reason' => 'max_daily'];
            }
        }

        return ['blocked' => false];
    }

    /**
     * Send the auto-reply via Evolution API
     */
    private function sendReply(WhatsappAutoReplyRule $rule, string $instanceName, string $recipientPhone, int $userId): array
    {
        $template = $rule->template;
        if (!$template) {
            return ['success' => false, 'error' => 'no_template'];
        }

        // Get API config
        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], $this->getCompanyId($userId));

        if (empty($config['api_url']) || empty($config['api_key'])) {
            return ['success' => false, 'error' => 'api_not_configured'];
        }

        // Wait for delay (human-like feel)
        if ($rule->reply_delay_seconds > 0) {
            sleep($rule->reply_delay_seconds);
        }

        try {
            // Format phone for Evolution API (with @s.whatsapp.net)
            $formattedPhone = $this->formatPhoneForApi($recipientPhone);

            if ($template->type === 'text') {
                // Send text message
                $response = Http::withHeaders([
                    'apikey' => $config['api_key'],
                    'Content-Type' => 'application/json',
                ])->post("{$config['api_url']}/message/sendText/{$instanceName}", [
                    'number' => $formattedPhone,
                    'text' => $template->message_text ?? '',
                ]);
            } else {
                // Send media message (image, video, pdf)
                $mediaUrl = $this->getMediaUrl($template->media_path);
                $mediaType = $this->getMediaType($template->type);

                $response = Http::withHeaders([
                    'apikey' => $config['api_key'],
                    'Content-Type' => 'application/json',
                ])->post("{$config['api_url']}/message/sendMedia/{$instanceName}", [
                    'number' => $formattedPhone,
                    'mediatype' => $mediaType,
                    'mimetype' => $this->getMimeType($template->type, $template->media_path),
                    'caption' => $template->message_text ?? '',
                    'media' => $mediaUrl,
                ]);
            }

            if ($response->successful()) {
                return ['success' => true];
            } else {
                Log::error("AutoReply: Evolution API send failed: " . $response->body());
                return ['success' => false, 'error' => 'api_error: ' . $response->status()];
            }
        } catch (\Exception $e) {
            Log::error("AutoReply: Send exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract user_id from instance name (format: rvcrm_{username}_{id})
     */
    private function getUserIdFromInstance(string $instanceName): ?int
    {
        // Instance name format: rvcrm_{cleanname}_{id}
        $parts = explode('_', $instanceName);
        if (count($parts) >= 3) {
            $id = (int) end($parts);
            if ($id > 0) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Normalize phone number — strip non-digits, remove country code
     */
    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        // Remove @s.whatsapp.net or @c.us suffix
        $clean = preg_replace('/@.*$/', '', $clean);
        // Remove leading 91 for Indian numbers (12 digits starting with 91)
        if (strlen($clean) == 12 && str_starts_with($clean, '91')) {
            $clean = substr($clean, 2);
        }
        return $clean;
    }

    /**
     * Format phone for Evolution API
     */
    private function formatPhoneForApi(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        $clean = preg_replace('/@.*$/', '', $clean);
        // Ensure it has country code
        if (strlen($clean) == 10) {
            $clean = '91' . $clean;
        }
        return $clean;
    }

    /**
     * Check if a phone number is blacklisted for a user
     */
    private function isBlacklisted(int $userId, string $phone): bool
    {
        return WhatsappAutoReplyBlacklist::where('user_id', $userId)
            ->where('phone_number', $phone)
            ->exists();
    }

    /**
     * Log a reply (sent, skipped, or failed)
     */
    private function logReply(int $userId, string $instanceName, ?int $ruleId, string $phone, string $message, ?int $templateId, string $status, ?string $skipReason): void
    {
        try {
            WhatsappAutoReplyLog::create([
                'company_id' => $this->getCompanyId($userId),
                'user_id' => $userId,
                'rule_id' => $ruleId,
                'instance_name' => $instanceName,
                'phone_number' => $phone,
                'incoming_message' => $message,
                'reply_template_id' => $templateId,
                'status' => $status,
                'skip_reason' => $skipReason,
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error("AutoReply: Failed to log reply: " . $e->getMessage());
        }
    }

    /**
     * Get full URL for media file
     */
    private function getMediaUrl(?string $mediaPath): string
    {
        if (!$mediaPath) return '';
        return url('storage/' . $mediaPath);
    }

    /**
     * Map template type to Evolution API media type
     */
    private function getMediaType(string $type): string
    {
        return match ($type) {
            'image' => 'image',
            'video' => 'video',
            'pdf' => 'document',
            default => 'document',
        };
    }

    /**
     * Get MIME type for media
     */
    private function getMimeType(string $type, ?string $mediaPath): string
    {
        if ($type === 'pdf') return 'application/pdf';
        if ($type === 'video') return 'video/mp4';
        if ($type === 'image') {
            $ext = strtolower(pathinfo($mediaPath ?? '', PATHINFO_EXTENSION));
            return match ($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
        }
        return 'application/octet-stream';
    }

    /**
     * Get company_id for a user
     */
    private function getCompanyId(int $userId): int
    {
        $user = \App\Models\User::find($userId);
        return $user->company_id ?? 1;
    }
}
