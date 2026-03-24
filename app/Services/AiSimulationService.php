<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiTokenLog;
use App\Models\ChatflowStep;
use App\Models\CatalogueCustomColumn;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AiSimulationService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999999';

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        // Need to use reflection or partial mock if we want to intercept sendWhatsAppMessage.
        // But we actually CAN just use the real service and let sendWhatsAppMessage return false silently
        // or actually intercept it. Since we are in HTTP request context, we will instantiate it.
        $this->chatbotService = new AIChatbotService($companyId, $userId);
    }

    /**
     * Run the simulation and stream output via the callback.
     */
    public function run(string $rules, callable $log)
    {
        try {
            $log('info', "Starting Phase 1: System Config & DB Checks...");

            // 1. Check Vertex AI Config
            $config = Setting::getValue('ai_bot', 'vertex_config', null, $this->companyId);
            if (!$config || empty($config['project_id'])) {
                $log('error', "Vertex AI is not configured. Go to Settings > AI Bot and set it up.");
                return;
            }
            $log('success', "Vertex AI Setup Found.");

            // 2. Check Database Tables
            if (!DB::getSchemaBuilder()->hasTable('ai_chat_sessions')) {
                $log('error', "Database missing required AI tables. Please run migrations.");
                return;
            }
            $log('success', "Database Connectivity OK.");

            // 3. Check Chatflow
            $stepsCount = ChatflowStep::where('company_id', $this->companyId)->count();
            if ($stepsCount === 0) {
                $log('error', "Warning: No Chatflow steps defined. The bot may default to Tier 2 for all requests.");
            } else {
                $log('success', "Found {$stepsCount} active Chatflow steps.");
            }

            // Clean up old simulation data
            $this->cleanup();

            $log('info', "--------------------------------");
            $log('info', "Starting Phase 2: Interactive AI Simulation...");
            $log('info', "Tester AI is reading your custom rules...");
            
            // Generate Tester prompt instruction
            $testerSystemPrompt = "You are a customer testing a WhatsApp CRM bot. You must follow these exact testing rules provided by the admin:\n" . strip_tags($rules) . "\n\nIn each turn, observe how the bot responds to you and generate your NEXT message to push the conversation forward towards ordering a product. If the bot fails the rules (e.g. shows price when it shouldn't, or speaks wrong language), reply ONLY with 'FAILURE: [Reason]'. Note that the BOT response will be provided to you. You MUST initiate the chat first by saying 'Hi'. Maximum 4 conversational turns.";

            $chatHistory = [];
            
            // Loop for 4 interactions
            for ($i = 0; $i < 4; $i++) {
                // Tester AI thinking...
                if ($i === 0) {
                    $userMsg = "Hi";
                } else {
                    $testerResult = $this->vertexAI->generateContent($testerSystemPrompt, $chatHistory);
                    $userMsg = trim($testerResult['text'] ?? '');
                }

                if (empty($userMsg)) {
                    $log('error', "Tester AI failed to generate a response.");
                    break;
                }

                if (str_starts_with($userMsg, 'FAILURE:')) {
                    $log('error', "Bot failed conditions during evaluation: " . $userMsg);
                    break;
                }
                
                $log('user', $userMsg);
                $chatHistory[] = ['role' => 'user', 'text' => $userMsg];

                // Send to Bot (using the real service)
                try {
                    $botResult = $this->chatbotService->processMessage('simulator_1', $this->simPhone, $userMsg);
                    $botMsg = $botResult['response'] ?? 'No text response generated.';
                    
                    $log('bot', $botMsg);
                    $chatHistory[] = ['role' => 'model', 'text' => $botMsg];
                } catch (\Exception $e) {
                    $log('error', "AIChatbotService crashed: " . $e->getMessage());
                    break;
                }
                
                // Slight delay to mimic real chat
                sleep(2);
            }

            $log('info', "--------------------------------");
            $log('info', "Starting Phase 3: Module Evaluation...");

            $session = AiChatSession::where('phone_number', $this->simPhone)->first();
            if (!$session) {
                $log('error', "AiChatSession was not created.");
            } else {
                $log('success', "AiChatSession generated and tracked. (ID: {$session->id})");
            }

            if ($session && $session->lead_id) {
                $lead = Lead::find($session->lead_id);
                if ($lead) {
                    $log('success', "Lead automatically created! (ID: {$lead->id})");
                }
            } else {
                $log('info', "No Lead was created during this simulation run.");
            }

            $tokenLogs = AiTokenLog::where('phone_number', $this->simPhone)->get();
            if ($tokenLogs->count() > 0) {
                $tier1Count = $tokenLogs->where('tier', 1)->count();
                $tier2Count = $tokenLogs->where('tier', 2)->count();
                $totalTokens = $tokenLogs->sum('total_tokens');
                $log('success', "API Token logging active: {$tier1Count} Tier-1 calls, {$tier2Count} Tier-2 calls. Total Tokens Used: {$totalTokens}");
            } else {
                $log('error', "Token metrics weren't logged.");
            }

            $log('info', "Cleaning up simulation test data...");
            $this->cleanup();
            $log('success', "All Diagnostics Completed!");

        } catch (\Exception $e) {
            $log('error', "Fatal Simulator Error: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    private function cleanup()
    {
        $session = AiChatSession::where('phone_number', $this->simPhone)->first();
        if ($session) {
            AiChatMessage::where('session_id', $session->id)->delete();
            if ($session->lead_id) { Lead::where('id', $session->lead_id)->delete(); }
            if ($session->quote_id) { 
                \App\Models\QuoteItem::where('quote_id', $session->quote_id)->delete();
                \App\Models\Quote::where('id', $session->quote_id)->delete(); 
            }
            $session->delete();
        }
        AiTokenLog::where('phone_number', $this->simPhone)->delete();
    }
}
