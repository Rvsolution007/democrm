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
    public function processIncomingMessage(string $instanceName, string $senderPhone, string $messageText, bool $skipAnyMessage = false): array
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
            ->when($skipAnyMessage, function ($q) {
                // In list_bot mode, skip 'any_message' rules — they intercept everything
                $q->where('match_type', '!=', 'any_message');
            })
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
                    // Clear any previous error on successful send
                    $rule->update(['last_error' => null, 'last_error_at' => null]);
                    $this->logReply($userId, $instanceName, $rule->id, $cleanPhone, $messageText, $rule->template_id, 'sent', null);
                    Log::info("AutoReply: Sent reply for rule '{$rule->name}' to {$cleanPhone}");

                    // Auto-create lead if enabled on this rule
                    if ($rule->create_lead) {
                        $this->createLeadFromAutoReply($userId, $cleanPhone, $messageText, $rule);
                    }

                    return ['status' => 'sent', 'rule' => $rule->name, 'template' => $rule->template->name ?? 'N/A'];
                } else {
                    $errorMsg = $sendResult['error'] ?? 'send_failed';
                    // Save error on the rule itself for UI display
                    $rule->update(['last_error' => $errorMsg, 'last_error_at' => now()]);
                    $this->logReply($userId, $instanceName, $rule->id, $cleanPhone, $messageText, $rule->template_id, 'failed', $errorMsg);
                    Log::error("AutoReply: Failed to send reply for rule '{$rule->name}': {$errorMsg}");
                    return ['status' => 'failed', 'error' => $errorMsg];
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
     * Send the auto-reply via Evolution API or Meta Template API
     */
    private function sendReply(WhatsappAutoReplyRule $rule, string $instanceName, string $recipientPhone, int $userId): array
    {
        // ── Meta Template Flow ──
        // If rule uses a Meta approved template, send via Official API
        if ($rule->template_source === 'meta' && $rule->meta_template_id) {
            $metaTemplate = $rule->metaTemplate;
            if (!$metaTemplate) {
                return ['success' => false, 'error' => 'meta_template_not_found'];
            }
            if (!$metaTemplate->isApproved()) {
                return ['success' => false, 'error' => 'meta_template_not_approved (status: ' . $metaTemplate->status . ')'];
            }

            // Wait for delay (human-like feel)
            if ($rule->reply_delay_seconds > 0) {
                sleep($rule->reply_delay_seconds);
            }

            $companyId = $this->getCompanyId($userId);
            $service = new \App\Services\MetaTemplateService($companyId);
            $result = $service->sendTemplateMessage($metaTemplate, $recipientPhone);

            return ['success' => $result['success'], 'error' => $result['error'] ?? null];
        }

        // ── Evolution API Flow (existing) ──
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

            // Ensure media_files is properly decoded
            $mediaFiles = is_string($template->media_files) ? json_decode($template->media_files, true) : (is_array($template->media_files) ? $template->media_files : []);
            if (!is_array($mediaFiles)) $mediaFiles = [];
            $textMsg = trim($template->message_text ?? '');
            $totalMedia = count($mediaFiles);

            if ($totalMedia === 0 && !empty($textMsg)) {
                // Send text message
                $response = Http::withHeaders([
                    'apikey' => $config['api_key'],
                    'Content-Type' => 'application/json',
                ])->post("{$config['api_url']}/message/sendText/{$instanceName}", [
                    'number' => $formattedPhone,
                    'text' => $textMsg,
                ]);
                
                if (!$response->successful()) {
                    return ['success' => false, 'error' => 'api_error: ' . $response->body()];
                }
            } else if ($totalMedia > 0) {
                // Send multiple media files sequentially
                foreach ($mediaFiles as $index => $media) {
                    $mediaPath = $media['path'] ?? '';
                    $mediaUrl = $this->getMediaUrl($mediaPath, $userId);
                    
                    // Determine type locally
                    $ext = strtolower(pathinfo($media['path'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) $mediaType = 'image';
                    elseif (in_array($ext, ['mp4', '3gp'])) $mediaType = 'video';
                    else $mediaType = 'document';

                    $isLast = ($index === $totalMedia - 1);
                    $caption = ($isLast && !empty($textMsg)) ? $textMsg : '';

                    // IMPORTANT FIX: Convert file to Base64 locally to avoid AxiosError
                    $mediaData = null;
                    $mimeType = $this->getMimeType($mediaType, $media['path']);
                    try {
                        if (!empty($mediaPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($mediaPath)) {
                            $mediaContent = \Illuminate\Support\Facades\Storage::disk('public')->get($mediaPath);
                            if ($mediaContent) {
                                // Evolution API expects raw base64 string WITHOUT data:image/jpeg;base64, prefix
                                $mediaData = base64_encode($mediaContent);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::error("AutoReply: Media file missing on disk: {$mediaPath}");
                            return ['success' => false, 'error' => "missing_media_file: The uploaded image is missing from the server storage. Please edit your auto-reply template and re-upload the image."];
                        }
                    } catch (\Exception $e) {
                         \Illuminate\Support\Facades\Log::warning("AutoReply: Failed to base64 encode media: " . $e->getMessage());
                         $mediaData = $mediaUrl; // Fallback to URL
                    }

                    $response = Http::withHeaders([
                        'apikey' => $config['api_key'],
                        'Content-Type' => 'application/json',
                    ])->post("{$config['api_url']}/message/sendMedia/{$instanceName}", [
                        'number' => $formattedPhone,
                        'mediatype' => $mediaType,
                        'mimetype' => $mimeType,
                        'caption' => $caption,
                        'media' => $mediaData ?? $mediaUrl,
                        'fileName' => basename($media['path']),
                    ]);

                    if (!$response->successful()) {
                        Log::error("AutoReply: Media send {$index} failed for {$formattedPhone}: " . $response->body());
                        return ['success' => false, 'error' => 'api_error: ' . $response->body()];
                    }
                    if (!$isLast) sleep(1); // minor delay to ensure chronological ordering via API
                }
            } else {
                return ['success' => false, 'error' => 'empty_template'];
            }

            return ['success' => true];
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
    private function getMediaUrl(?string $mediaPath, int $userId = 0): string
    {
        if (!$mediaPath) return '';
        
        // Use webhook_base_url from settings if available (public server URL)
        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
            'webhook_base_url' => '',
        ], $this->getCompanyId($userId));
        
        $baseUrl = !empty($config['webhook_base_url']) ? $config['webhook_base_url'] : secure_url('');
        // Force HTTPS for production domains
        if (!str_contains($baseUrl, 'localhost') && !str_contains($baseUrl, '127.0.0.1')) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }
        return rtrim($baseUrl, '/') . '/storage/' . $mediaPath;
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

    /**
     * Auto-create a lead from an auto-reply interaction
     * Only creates lead if no existing lead with same phone exists for this company
     */
    private function createLeadFromAutoReply(int $userId, string $phone, string $messageText, WhatsappAutoReplyRule $rule): void
    {
        try {
            $companyId = $this->getCompanyId($userId);

            // Check if a lead with this phone already exists for this company (avoid duplicates)
            $existingLead = Lead::where('company_id', $companyId)
                ->where('phone', $phone)
                ->first();

            if ($existingLead) {
                Log::info("AutoReply: Lead already exists for phone {$phone} (Lead #{$existingLead->id}), skipping creation");
                return;
            }

            // Create new lead
            $lead = Lead::create([
                'company_id' => $companyId,
                'created_by_user_id' => $userId,
                'source' => 'whatsapp',
                'source_provider' => 'auto-reply',
                'name' => 'WhatsApp Lead - ' . $phone,
                'phone' => $phone,
                'stage' => 'new',
                'query_message' => $messageText,
                'notes' => "Auto-created from WhatsApp Auto-Reply rule: {$rule->name}",
            ]);

            // Assign lead to the user who owns the auto-reply rule
            $lead->assignedUsers()->attach($userId);

            Log::info("AutoReply: Lead #{$lead->id} created for phone {$phone} via rule '{$rule->name}'");
        } catch (\Exception $e) {
            Log::error("AutoReply: Failed to create lead for {$phone}: " . $e->getMessage());
        }
    }
}
