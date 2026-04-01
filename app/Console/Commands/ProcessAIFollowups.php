<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiChatSession;
use App\Models\AiChatTrace;
use App\Models\ChatFollowupSchedule;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\Company;
use App\Services\VertexAIService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessAIFollowups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:process-followups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process AI smart follow-ups for abandoned WhatsApp sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting ProcessAIFollowups command...");
        
        // Group active schedules by company
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $schedules = ChatFollowupSchedule::where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('delay_minutes', 'asc')
                ->get();
                
            if ($schedules->isEmpty()) {
                continue;
            }
            
            // Initialize VertexAI for this specific company
            $vertexAI = new VertexAIService($company->id);
            
            $stopStage = Setting::getValue('ai_bot', 'stop_stage', '', $company->id);
            
            // Get active sessions that are not completed or cancelled, and still eligible for followups.
            // followup_status is integer: 0=active, 1+=followups sent, -1=stopped
            // Legacy string 'active' is treated as 0
            $sessions = AiChatSession::where('company_id', $company->id)
                ->whereNotIn('status', ['completed', 'cancelled', 'stopped'])
                ->where(function ($q) {
                    $q->where('followup_status', '>=', 0)
                      ->orWhere('followup_status', 'active')
                      ->orWhereNull('followup_status');
                })
                ->get();
                
            $processedCount = 0;
            
            foreach ($sessions as $session) {
                // If lead reached the stop stage, mark followups as stopped (-1)
                $lead = $session->lead_id ? Lead::find($session->lead_id) : null;
                if ($lead && $stopStage && $lead->stage === $stopStage) {
                    $session->update(['followup_status' => -1]);
                    continue;
                }
                
                // If lead is lost or won, we probably should stop too
                if ($lead && in_array(strtolower($lead->stage), ['lost', 'won'])) {
                    $session->update(['followup_status' => -1]);
                    continue;
                }
                
                // Normalize followup_status: treat 'active', null, '' as 0
                $rawStatus = $session->followup_status;
                $nextIndex = is_numeric($rawStatus) ? (int)$rawStatus : 0;
                
                // If followup_status is -1 (stopped), skip
                if ($nextIndex < 0) {
                    continue;
                }
                
                // If we've sent all schedules, mark as stopped
                if ($nextIndex >= $schedules->count()) {
                    $session->update(['followup_status' => -1]);
                    continue;
                }
                
                $nextSchedule = $schedules[$nextIndex];
                
                // Calculate elapsed time since last BOT message (or creation time if none)
                $lastBotTime = $session->last_bot_message_at ? Carbon::parse($session->last_bot_message_at) : $session->created_at;
                
                // Use absolute time diff in minutes
                $elapsedMinutes = $lastBotTime->diffInMinutes(now(), false);
                
                // Wait, if the user replied and bot has NOT replied?
                // The AI replies automatically immediately, so last_bot_message_at should be accurate.
                // However, what if last_user_message_at is > last_bot_message_at?
                // That means the user replied but bot hasn't replied yet. Followups should only fire when bot is awaiting response.
                $lastUserMsgAt = \App\Models\AiChatMessage::where('session_id', $session->id)
                    ->where('role', 'user')
                    ->max('created_at');
                    
                if ($lastUserMsgAt) {
                    $lastUserTime = Carbon::parse($lastUserMsgAt);
                    if ($lastUserTime->greaterThan($lastBotTime)) {
                        // User replied, bot hasn't processed. Don't followup yet.
                        continue;
                    }
                }
                
                if ($elapsedMinutes >= $nextSchedule->delay_minutes) {
                    // Time to send this follow-up
                    try {
                        $this->info("Sending followup {$nextSchedule->name} for session {$session->id}");
                        
                        // ── TRACE: Follow-up Schedule Matched ──
                        AiChatTrace::create([
                            'session_id' => $session->id,
                            'node_name' => 'FollowupScheduleMatched',
                            'node_group' => 'followup',
                            'status' => 'success',
                            'input_data' => [
                                'schedule_name' => $nextSchedule->name,
                                'delay_minutes' => $nextSchedule->delay_minutes,
                                'elapsed_minutes' => round($elapsedMinutes, 1),
                                'followup_index' => $nextIndex,
                            ],
                            'output_data' => [
                                'action' => 'generating_message',
                                'phone' => $session->phone_number,
                            ],
                        ]);

                        // Generate Context-Aware Message via Vertex AI Flash
                        $prompt = "You are a helpful sales assistant following up with a customer.\n";
                        $prompt .= "The customer hasn't replied to your last message.\n";
                        $prompt .= "Follow up with them politely to re-engage them. Keep it very short (1-2 sentences max) and conversational.\n";
                        $prompt .= "DO NOT ask too many questions. Nudge them gently to continue the process.\n";
                        
                        // Optionally add specific wording based on the follow-up step
                        $prompt .= "Context for this specific follow-up stage: '{$nextSchedule->name}'. Try to match this tone.\n";
                        
                        // Pass recent history for context
                        $history = $this->buildMiniHistory($session);
                        
                        $t = microtime(true);
                        $aiResult = $vertexAI->generateContent($prompt, $history, null);
                        $aiMs = (int)((microtime(true) - $t) * 1000);
                        $replyText = trim($aiResult['text'] ?? "Hi there, just checking in to see if you have any questions!");
                        
                        // Clean markdown
                        $replyText = preg_replace('/```.*?```/s', '', $replyText);

                        // ── TRACE: Follow-up AI Message Generated ──
                        AiChatTrace::create([
                            'session_id' => $session->id,
                            'node_name' => 'FollowupAIGenerated',
                            'node_group' => 'followup',
                            'status' => 'success',
                            'input_data' => [
                                'prompt_preview' => mb_substr($prompt, 0, 150),
                                'history_length' => count($history),
                            ],
                            'output_data' => [
                                'response' => mb_substr($replyText, 0, 200),
                                'tokens_used' => $aiResult['total_tokens'] ?? 0,
                            ],
                            'execution_time_ms' => $aiMs,
                        ]);

                        // Use company settings for evolution API
                        $config = Setting::getValue('whatsapp', 'api_config', [
                            'api_url' => '',
                            'api_key' => '',
                        ], $company->id);

                        $apiUrl = rtrim($config['api_url'] ?? '', '/');
                        $apiKey = $config['api_key'] ?? '';

                        if (empty($apiUrl) || empty($apiKey)) {
                            Log::error("ProcessAIFollowups: Missing API config for company {$company->id}");
                            continue;
                        }

                        $endpoint = "{$apiUrl}/message/sendText/{$session->instance_name}";
                        $payload = [
                            'number' => preg_replace('/\D/', '', $session->phone_number),
                            'text' => $replyText,
                            'delay' => 1200,
                            'linkPreview' => true,
                        ];

                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'apikey' => $apiKey,
                            'Content-Type' => 'application/json',
                        ])->connectTimeout(5)->timeout(10)->post($endpoint, $payload);

                        if ($response->successful()) {
                            // Increment followup_status
                            $session->update([
                                'followup_status' => $nextIndex + 1,
                                'last_bot_message_at' => now()
                            ]);
                            
                            // Log the bot message in AiChatMessage
                            \App\Models\AiChatMessage::create([
                                'session_id' => $session->id,
                                'role' => 'bot',
                                'message' => $replyText
                            ]);
                            
                            // Log in timeline if lead exists
                            if ($lead) {
                                \App\Models\LeadFollowup::create([
                                    'company_id' => $company->id,
                                    'lead_id' => $lead->id,
                                    'type' => 'whatsapp',
                                    'status' => 'completed',
                                    'description' => "Smart AI Follow-Up ({$nextSchedule->name}) Sent: " . $replyText,
                                    'followup_date' => now()->toDateString(),
                                    'time' => now()->toTimeString()
                                ]);
                                
                                // Set next_follow_up_at on lead for CRM visibility
                                $nextScheduleAfterThis = $schedules[$nextIndex + 1] ?? null;
                                if ($nextScheduleAfterThis) {
                                    $nextFollowupTime = now()->addMinutes($nextScheduleAfterThis->delay_minutes);
                                    $lead->update(['next_follow_up_at' => $nextFollowupTime]);
                                } else {
                                    $lead->update(['next_follow_up_at' => null]);
                                }
                                
                                // Add to lead notes
                                $lead->update([
                                    'notes' => ($lead->notes ? $lead->notes . "\n" : '') . "[AI Smart Followup] " . $replyText
                                ]);
                            }
                            
                            // ── TRACE: Follow-up Sent Successfully ──
                            AiChatTrace::create([
                                'session_id' => $session->id,
                                'node_name' => 'FollowupSent',
                                'node_group' => 'followup',
                                'status' => 'success',
                                'input_data' => [
                                    'phone' => $session->phone_number,
                                    'schedule_name' => $nextSchedule->name,
                                ],
                                'output_data' => [
                                    'message_preview' => mb_substr($replyText, 0, 150),
                                    'followup_index' => $nextIndex + 1,
                                    'lead_id' => $lead ? $lead->id : null,
                                    'next_followup_at' => isset($nextScheduleAfterThis) ? $nextFollowupTime->toDateTimeString() : 'none (last)',
                                ],
                            ]);
                            
                            $processedCount++;
                            Log::info("ProcessAIFollowups: Sent schedule {$nextSchedule->id} for session {$session->id}");
                        } else {
                            // ── TRACE: Follow-up Send Failed ──
                            AiChatTrace::create([
                                'session_id' => $session->id,
                                'node_name' => 'FollowupSendFailed',
                                'node_group' => 'followup',
                                'status' => 'error',
                                'input_data' => [
                                    'phone' => $session->phone_number,
                                    'schedule_name' => $nextSchedule->name,
                                ],
                                'error_message' => 'Evolution API returned: ' . mb_substr($response->body(), 0, 200),
                            ]);
                            Log::error("ProcessAIFollowups: Failed to send via Evolution for session {$session->id}", ['error' => $response->body()]);
                        }
                    } catch (\Exception $e) {
                        // ── TRACE: Follow-up Exception ──
                        try {
                            AiChatTrace::create([
                                'session_id' => $session->id,
                                'node_name' => 'FollowupException',
                                'node_group' => 'followup',
                                'status' => 'error',
                                'error_message' => $e->getMessage(),
                            ]);
                        } catch (\Exception $traceErr) {
                            // Ignore trace save errors
                        }
                         Log::error("ProcessAIFollowups Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                    }
                }
            }
            
            if ($processedCount > 0) {
                $this->info("Processed {$processedCount} followups for Company {$company->id}");
            }
        }
        
        $this->info("Finished processing AIFollowups.");
    }
    
    private function buildMiniHistory(AiChatSession $session): array
    {
        $history = [];
        $messages = \App\Models\AiChatMessage::where('session_id', $session->id)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get()
            ->reverse();

        foreach ($messages as $msg) {
            $role = $msg->role === 'bot' ? 'model' : 'user';
            $history[] = ['role' => $role, 'text' => $msg->message];
        }

        return $history;
    }
}
