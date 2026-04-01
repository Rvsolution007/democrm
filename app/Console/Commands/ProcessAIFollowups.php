<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiChatSession;
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
            // followup_status stores the count of followups already sent. If it's -1, it means stopped.
            $sessions = AiChatSession::where('company_id', $company->id)
                ->whereNotIn('status', ['completed', 'cancelled', 'stopped'])
                ->where('followup_status', '>=', 0)
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
                
                // Which schedule is next?
                $nextIndex = $session->followup_status ?? 0;
                
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
                        
                        // Generate Context-Aware Message via Vertex AI Flash
                        $prompt = "You are a helpful sales assistant following up with a customer.\n";
                        $prompt .= "The customer hasn't replied to your last message.\n";
                        $prompt .= "Follow up with them politely to re-engage them. Keep it very short (1-2 sentences max) and conversational.\n";
                        $prompt .= "DO NOT ask too many questions. Nudge them gently to continue the process.\n";
                        
                        // Optionally add specific wording based on the follow-up step
                        $prompt .= "Context for this specific follow-up stage: '{$nextSchedule->name}'. Try to match this tone.\n";
                        
                        // Pass recent history for context
                        $history = $this->buildMiniHistory($session);
                        
                        $aiResult = $vertexAI->generateContent($prompt, $history, null);
                        $replyText = trim($aiResult['text'] ?? "Hi there, just checking in to see if you have any questions!");
                        
                        // Clean markdown
                        $replyText = preg_replace('/```.*?```/s', '', $replyText);

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
                                
                                // And add to lead notes
                                $lead->update([
                                    'notes' => ($lead->notes ? $lead->notes . "\n" : '') . "[AI Smart Followup] " . $replyText
                                ]);
                            }
                            
                            $processedCount++;
                            Log::info("ProcessAIFollowups: Sent schedule {$nextSchedule->id} for session {$session->id}");
                        } else {
                            Log::error("ProcessAIFollowups: Failed to send via Evolution for session {$session->id}", ['error' => $response->body()]);
                        }
                    } catch (\Exception $e) {
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
