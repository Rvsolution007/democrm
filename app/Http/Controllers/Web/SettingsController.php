<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        if (!can('settings.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $company = auth()->user()->company;

        // Load all column_visibility settings from DB
        $columnVisibility = [];
        $settings = Setting::where('company_id', auth()->user()->company_id)
            ->where('group', 'column_visibility')
            ->get();

        foreach ($settings as $setting) {
            $columnVisibility[$setting->key] = $setting->value;
        }

        $quoteTaxes = Setting::getValue('quotes', 'taxes', []);
        $leadStages = Setting::getValue('leads', 'stages', Lead::STAGES);
        $leadSources = Setting::getValue('leads', 'sources', Lead::SOURCES);
        $taskStatuses = Setting::getValue('tasks', 'statuses', Task::STATUSES);
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);
        // AI Bot Configuration (only per-business settings remain)
        $aiBotEnabled = Setting::getValue('ai_bot', 'enabled', false);
        $aiReplyLanguage = Setting::getValue('ai_bot', 'reply_language', 'auto');
        $aiSessionValidDays = Setting::getValue('ai_bot', 'session_valid_days', 10);
        $aiGreetingWords = Setting::getValue('ai_bot', 'greeting_words', '');
        $aiFollowupStopStage = Setting::getValue('ai_bot', 'followup_stop_stage', '');
        $aiTargetStage = Setting::getValue('ai_bot', 'target_stage', '');

        // Bot Mode Configuration (2-way: ai_bot / list_bot — auto_reply merged into list_bot)
        $botMode = Setting::getValue('whatsapp', 'bot_mode', null);
        if ($botMode === null || $botMode === 'auto_reply') {
            $botMode = $aiBotEnabled ? 'ai_bot' : 'list_bot';
        }
        $interactiveListMode = Setting::getValue('ai_bot', 'interactive_list_mode', false);
        $listBotWelcome = Setting::getValue('list_bot', 'welcome_message', '');
        $listBotButtonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu');

        // Dual API Configuration
        $officialApiEnabled = (bool) Setting::getValue('whatsapp', 'official_api_enabled', false);
        $officialApiConfig = Setting::getValue('whatsapp', 'official_api_config', [
            'phone_number_id' => '', 'access_token' => '', 'waba_id' => '',
        ]);
        $evolutionApiEnabled = (bool) Setting::getValue('whatsapp', 'evolution_api_enabled', true);
        $officialVerifyToken = Setting::getValue('whatsapp', 'official_verify_token', 'rvcrm_verify_token');

        // Evolution API granular sub-toggles (default ON when evolution enabled)
        $evoFollowupEnabled = (bool) Setting::getValue('whatsapp', 'evolution_followup_enabled', true);
        $evoBulkEnabled = (bool) Setting::getValue('whatsapp', 'evolution_bulk_enabled', true);
        $evoTextmenuEnabled = (bool) Setting::getValue('whatsapp', 'evolution_textmenu_enabled', true);

        $followupSchedules = \App\Models\ChatFollowupSchedule::where('company_id', auth()->user()->company_id)
            ->orderBy('delay_minutes')
            ->get();

        // Load backup files for Backup & Restore tab
        $backupFiles = [];
        
        $spatieDir = config('backup.backup.name', 'Laravel');
        
        $files = [];
        if (Storage::disk('local')->exists('backups')) {
            $files = array_merge($files, Storage::disk('local')->files('backups'));
        }
        if (Storage::disk('local')->exists($spatieDir)) {
            $files = array_merge($files, Storage::disk('local')->files($spatieDir));
        }
        
        $files = array_unique($files);
        
        foreach ($files as $f) {
            if (str_ends_with($f, '.json') || str_ends_with($f, '.zip') || str_ends_with($f, '.sql')) {
                $backupFiles[] = [
                    'name' => basename($f),
                    'size' => round(Storage::disk('local')->size($f) / 1024, 1),
                    'date' => date('d M Y, h:i A', Storage::disk('local')->lastModified($f)),
                ];
            }
        }
        usort($backupFiles, fn($a, $b) => strcmp($b['name'], $a['name']));

        return view('admin.settings.index', compact(
            'company', 'columnVisibility', 'quoteTaxes', 'leadStages', 'leadSources',
            'taskStatuses', 'paymentTypes', 'backupFiles',
            'aiBotEnabled', 'aiReplyLanguage', 'aiSessionValidDays', 'aiGreetingWords', 'aiFollowupStopStage', 'aiTargetStage', 'followupSchedules',
            'botMode', 'interactiveListMode', 'listBotWelcome', 'listBotButtonText',
            'officialApiEnabled', 'officialApiConfig', 'evolutionApiEnabled', 'officialVerifyToken',
            'evoFollowupEnabled', 'evoBulkEnabled', 'evoTextmenuEnabled'
        ));
    }

    /**
     * Save column visibility settings via AJAX
     */
    public function saveColumnVisibility(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'columns' => 'required|array',
        ]);

        Setting::setValue(
            'column_visibility',
            $request->module,
            $request->columns
        );

        return response()->json(['success' => true, 'message' => 'Settings saved']);
    }

    /**
     * Save quote taxes settings via AJAX
     */
    public function saveTaxes(Request $request)
    {
        $request->validate([
            'taxes' => 'present|array',
        ]);

        Setting::setValue('quotes', 'taxes', $request->taxes);

        return response()->json(['success' => true, 'message' => 'Taxes saved']);
    }

    /**
     * Get column visibility settings for a module via AJAX
     */
    public function getColumnVisibility($module)
    {
        $value = Setting::getValue('column_visibility', $module, null);
        return response()->json(['module' => $module, 'columns' => $value ?? new \stdClass()]);
    }

    /**
     * Save lead stages via AJAX
     */
    public function saveLeadStages(Request $request)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*' => 'required|string|distinct'
        ]);

        Setting::setValue('leads', 'stages', $request->stages);

        return response()->json(['success' => true, 'message' => 'Lead stages saved']);
    }

    /**
     * Save lead sources via AJAX
     */
    public function saveLeadSources(Request $request)
    {
        $request->validate([
            'sources' => 'required|array',
            'sources.*' => 'required|string|distinct'
        ]);

        Setting::setValue('leads', 'sources', $request->sources);

        return response()->json(['success' => true, 'message' => 'Lead sources saved']);
    }

    /**
     * Save task statuses via AJAX
     */
    public function saveTaskStatuses(Request $request)
    {
        $request->validate([
            'statuses' => 'required|array',
            'statuses.*' => 'required|string|distinct'
        ]);

        Setting::setValue('tasks', 'statuses', $request->statuses);

        return response()->json(['success' => true, 'message' => 'Task statuses saved']);
    }

    /**
     * Save payment types via AJAX
     */
    public function savePaymentTypes(Request $request)
    {
        $request->validate([
            'types' => 'required|array',
            'types.*' => 'required|string|distinct'
        ]);

        Setting::setValue('payments', 'types', $request->types);

        return response()->json(['success' => true, 'message' => 'Payment types saved']);
    }

    /**
     * Save WhatsApp API configuration (server-level only: URL + API Key)
     */
    public function saveWhatsappApi(Request $request)
    {
        $request->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'webhook_base_url' => 'nullable|url',
        ]);

        $webhookBase = $request->webhook_base_url ?? '';
        // Automatically convert http:// to https:// for production domains (avoid 301 POST body drop)
        if (!empty($webhookBase) && !str_contains($webhookBase, 'localhost') && !str_contains($webhookBase, '127.0.0.1')) {
            $webhookBase = str_replace('http://', 'https://', rtrim($webhookBase, '/'));
        } else {
            $webhookBase = rtrim($webhookBase, '/');
        }

        Setting::setValue('whatsapp', 'api_config', [
            'api_url' => rtrim($request->api_url, '/'),
            'api_key' => $request->api_key,
            'webhook_base_url' => $webhookBase,
        ], auth()->user()->company_id);
        
        // Register webhook for the current user's instance
        $user = auth()->user();
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
        $instanceName = 'rvcrm_' . $cleanName . '_' . $user->id;
        
        $baseUrl = !empty($webhookBase) ? $webhookBase : secure_url('');
        $webhookUrl = rtrim($baseUrl, '/') . "/webhook/whatsapp/incoming/{$instanceName}";
        
        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $request->api_key,
                'Content-Type' => 'application/json',
            ])->post(rtrim($request->api_url, '/') . "/webhook/set/{$instanceName}", [
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'webhookByEvents' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ],
            ]);
            \Illuminate\Support\Facades\Log::info("Settings: Registered webhook for {$instanceName} -> {$webhookUrl}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to set webhook on save config: " . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'WhatsApp API configuration saved and webhook refreshed.']);
    }

    /**
     * Update Company Information
     */
    public function updateCompany(Request $request)
    {
        if (!can('settings.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'gstin' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $company = auth()->user()->company;
        
        if (!$company) {
            return back()->with('error', 'Company not found.');
        }

        $company->name = $request->name;
        $company->gstin = $request->gstin;
        $company->phone = $request->phone;
        
        // Parse address from textarea (comma-separated) into structured array
        if ($request->address) {
            $parts = array_map('trim', explode(',', $request->address));
            $address = [];
            if (!empty($parts[0])) $address['street'] = $parts[0];
            if (!empty($parts[1])) $address['city'] = $parts[1];
            if (!empty($parts[2])) $address['state'] = $parts[2];
            if (!empty($parts[3])) $address['postal_code'] = $parts[3];
            if (!empty($parts[4])) $address['country'] = $parts[4];
            $company->address = $address;
        } else {
            $company->address = null;
        }

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }

            $logoPath = $request->file('logo')->store('company_logos', 'public');
            $company->logo = $logoPath;
        }

        $company->save();

        return back()->with('success', 'Company information updated successfully.');
    }

    /**
     * Check how many leads are on a specific stage (for delete protection)
     */
    public function checkStageLeads(Request $request)
    {
        $request->validate(['stage' => 'required|string']);

        $count = Lead::where('stage', $request->stage)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'stage' => $request->stage,
        ]);
    }

    /**
     * Transfer all leads from one stage to another, then remove old stage from settings
     */
    public function transferStageLeads(Request $request)
    {
        $request->validate([
            'from_stage' => 'required|string',
            'to_stage' => 'required|string|different:from_stage',
        ]);

        $count = Lead::where('stage', $request->from_stage)
            ->update(['stage' => $request->to_stage]);

        return response()->json([
            'success' => true,
            'transferred' => $count,
        ]);
    }

    /**
     * Save AI Bot Vertex AI configuration
     */
    public function saveAiConfig(Request $request)
    {
        $request->validate([
            'project_id' => 'required|string',
            'location' => 'required|string',
            'model' => 'required|string',
            'service_account_json' => 'required|string',
        ]);

        // Parse service account JSON
        $serviceAccount = json_decode($request->service_account_json, true);
        if (!$serviceAccount || !isset($serviceAccount['client_email']) || !isset($serviceAccount['private_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service account JSON. Must contain client_email and private_key.',
            ], 422);
        }

        Setting::setValue('ai_bot', 'vertex_config', [
            'project_id' => $request->project_id,
            'location' => $request->location,
            'model' => $request->model,
            'service_account' => $serviceAccount,
        ]);

        // Clear cached access token so new credentials take effect immediately
        \Illuminate\Support\Facades\Cache::forget('vertex_ai_token_' . md5($request->project_id));

        return response()->json(['success' => true, 'message' => 'AI configuration saved']);
    }

    /**
     * Save AI Bot session validation settings
     */
    public function saveAiSessionSettings(Request $request)
    {
        $request->validate([
            'session_valid_days' => 'required|integer|min:1|max:365',
        ]);

        Setting::setValue('ai_bot', 'session_valid_days', (int) $request->session_valid_days);

        return response()->json(['success' => true, 'message' => 'Session settings saved']);
    }

    /**
     * Save AI Bot system prompt and other specific prompts
     */
    public function saveAiPrompt(Request $request)
    {
        $request->validate([
            'system_prompt' => 'required|string',
            'greeting_prompt' => 'nullable|string',
            'business_prompt' => 'nullable|string',
            'spell_correction_prompt' => 'nullable|string',
        ]);

        Setting::setValue('ai_bot', 'system_prompt', $request->system_prompt);
        Setting::setValue('ai_bot', 'greeting_prompt', $request->greeting_prompt ?? '');
        Setting::setValue('ai_bot', 'business_prompt', $request->business_prompt ?? '');
        Setting::setValue('ai_bot', 'spell_correction_prompt', $request->spell_correction_prompt ?? '');

        return response()->json(['success' => true, 'message' => 'AI prompts saved']);
    }

    /**
     * Save AI Architecture rules
     */
    public function saveAiArchitectureRules(Request $request)
    {
        $request->validate([
            'architecture_rules' => 'nullable|string',
        ]);

        Setting::setValue('ai_bot', 'architecture_rules', $request->architecture_rules ?? '');

        return response()->json(['success' => true, 'message' => 'AI architecture rules saved']);
    }

    /**
     * Save AI Bot reply language
     */
    public function saveAiLanguage(Request $request)
    {
        $request->validate([
            'reply_language' => 'required|in:auto,en,hi',
        ]);

        Setting::setValue('ai_bot', 'reply_language', $request->reply_language);

        return response()->json(['success' => true, 'message' => 'Reply language saved']);
    }

    /**
     * Toggle AI Bot on/off
     * When ON: auto-reply rules are automatically disabled
     * When OFF: auto-reply rules are re-enabled
     */
    public function toggleAiBot(Request $request)
    {
        $enabled = $request->boolean('enabled');

        Setting::setValue('ai_bot', 'enabled', $enabled);

        // Auto-disable/enable all auto-reply rules for this company
        if ($enabled) {
            \App\Models\WhatsappAutoReplyRule::where('company_id', auth()->user()->company_id)
                ->update(['is_active' => false]);
                
            // Register webhook to ensure it wasn't lost
            $config = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => '', 'webhook_base_url' => ''], auth()->user()->company_id);
            if (!empty($config['api_url']) && !empty($config['api_key'])) {
                $user = auth()->user();
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
                $instanceName = 'rvcrm_' . $cleanName . '_' . $user->id;
                
                $baseUrl = !empty($config['webhook_base_url']) ? $config['webhook_base_url'] : secure_url('');
                // Force HTTPS for production domains
                if (!str_contains($baseUrl, 'localhost') && !str_contains($baseUrl, '127.0.0.1')) {
                    $baseUrl = str_replace('http://', 'https://', $baseUrl);
                }
                $webhookUrl = rtrim($baseUrl, '/') . "/webhook/whatsapp/incoming/{$instanceName}";
                
                try {
                    \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $config['api_key'],
                        'Content-Type' => 'application/json',
                    ])->post(rtrim($config['api_url'], '/') . "/webhook/set/{$instanceName}", [
                        'webhook' => [
                            'enabled' => true,
                            'url' => $webhookUrl,
                            'webhookByEvents' => false,
                            'events' => ['MESSAGES_UPSERT'],
                        ],
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to set webhook on AI toggle: " . $e->getMessage());
                }
            }
        }


        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'AI Bot enabled. Auto-reply rules have been disabled.'
                : 'AI Bot disabled. You can now enable auto-reply rules.',
        ]);
    }
    /**
     * View the trailing applications logs
     */
    public function systemLogsIndex()
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        
        if (file_exists($logFile) && is_readable($logFile)) {
            // Read last N lines efficiently using fseek
            $lines = 500;
            $fp = fopen($logFile, 'r');
            if ($fp) {
                fseek($fp, -1, SEEK_END);
                $pos = ftell($fp);
                $logsString = '';
                $linesCount = 0;

                // Read backwards finding newlines
                while ($pos > 0 && $linesCount < $lines) {
                    $char = fgetc($fp);
                    $logsString = $char . $logsString;
                    if ($char === "\n") {
                        $linesCount++;
                    }
                    fseek($fp, --$pos);
                }
                fclose($fp);

                // Split into array and remove any empty trailing lines
                $logs = array_filter(explode("\n", $logsString));
            }
        }

        return view('admin.system_logs', compact('logs'));
    }

    /**
     * Clear application logs
     */
    public function systemLogsClear()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, ''); // Empty file
        }

        return redirect()->route('admin.system-logs.index')->with('success', 'System error logs have been cleared.');
    }

    /**
     * Save AI Bot Follow-up Settings
     */
    public function saveFollowupSettings(Request $request)
    {
        $request->validate([
            'followup_stop_stage' => 'nullable|string',
            'target_stage' => 'nullable|string',
            'schedules' => 'present|array',
            'schedules.*.name' => 'required|string',
            'schedules.*.delay_minutes' => 'required|integer|min:0',
            'schedules.*.is_active' => 'required|boolean',
        ]);

        Setting::setValue('ai_bot', 'followup_stop_stage', $request->followup_stop_stage ?? '');
        Setting::setValue('ai_bot', 'target_stage', $request->target_stage ?? '');

        $companyId = auth()->user()->company_id;
        
        // Remove old schedules and recreate
        \App\Models\ChatFollowupSchedule::where('company_id', $companyId)->delete();
        
        foreach ($request->schedules as $schedule) {
            \App\Models\ChatFollowupSchedule::create([
                'company_id' => $companyId,
                'name' => $schedule['name'],
                'delay_minutes' => $schedule['delay_minutes'],
                'is_active' => $schedule['is_active'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Follow-up settings saved']);
    }

    /**
     * Save Tier 3 Column Analytics prompt
     */
    public function saveAiTier3Prompt(Request $request)
    {
        $request->validate([
            'tier3_prompt' => 'nullable|string',
        ]);

        Setting::setValue('ai_bot', 'tier3_prompt', $request->tier3_prompt ?? '');

        return response()->json(['success' => true, 'message' => 'Tier 3 prompt saved']);
    }

    /**
     * Save custom greeting words
     */
    public function saveAiGreetingWords(Request $request)
    {
        $request->validate([
            'greeting_words' => 'nullable|string',
        ]);

        Setting::setValue('ai_bot', 'greeting_words', $request->greeting_words ?? '');

        return response()->json(['success' => true, 'message' => 'Greeting words saved']);
    }

    /**
     * Save match confidence threshold
     */
    public function saveAiMatchConfidence(Request $request)
    {
        $request->validate([
            'match_min_confidence' => 'required|integer|min:0|max:100',
        ]);

        Setting::setValue('ai_bot', 'match_min_confidence', (int) $request->match_min_confidence);

        return response()->json(['success' => true, 'message' => 'Match confidence threshold saved']);
    }

    /**
     * Clear Product Group Match cache
     */
    public function clearPgmCache()
    {
        $companyId = auth()->user()->company_id;
        \App\Services\AIChatbotService::clearProductGroupCache($companyId);

        return response()->json(['success' => true, 'message' => 'Product match cache cleared']);
    }

    /**
     * Match Playground — Test PHP Product Group Match
     */
    public function testProductGroupMatch(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        $service = new \App\Services\AIChatbotService($companyId, $userId);
        $matches = $service->phpProductGroupMatch($request->message);

        // Also return the current product group index for display
        $indexMethod = new \ReflectionMethod($service, 'buildProductGroupIndex');
        $indexMethod->setAccessible(true);
        $index = $indexMethod->invoke($service);

        // Format index for display
        $indexDisplay = [];
        $totalWords = 0;
        foreach ($index as $colId => $colData) {
            $indexDisplay[] = [
                'column_name' => $colData['column_name'],
                'values' => $colData['unique_values'],
                'count' => count($colData['unique_values']),
            ];
            $totalWords += count($colData['unique_values']);
        }

        // Count total products
        $totalProducts = \App\Models\Product::where('company_id', $companyId)
            ->where('status', 'active')->count();

        return response()->json([
            'success' => true,
            'message' => $request->message,
            'matches' => $matches,
            'match_count' => count($matches),
            'index' => $indexDisplay,
            'total_products' => $totalProducts,
            'total_columns' => count($index),
            'total_words' => $totalWords,
            'min_confidence' => (int) \App\Models\Setting::getValue('ai_bot', 'match_min_confidence', 60, $companyId),
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // BOT MODE & LIST BOT SETTINGS
    // ═══════════════════════════════════════════════════════

    /**
     * Save WhatsApp Bot Mode (ai_bot / list_bot — auto_reply merged into list_bot)
     */
    public function saveBotMode(Request $request)
    {
        $request->validate([
            'bot_mode' => 'required|in:list_bot,ai_bot',
        ]);

        $mode = $request->bot_mode;
        $companyId = auth()->user()->company_id;
        $company = Company::find($companyId);

        // Package enforcement
        if ($mode === 'ai_bot' && $company && !$company->hasFeature('ai_bot')) {
            return response()->json([
                'success' => false,
                'message' => 'AI Bot requires Enterprise package. Please upgrade your plan.',
            ], 403);
        }

        Setting::setValue('whatsapp', 'bot_mode', $mode);
        Setting::setValue('ai_bot', 'enabled', $mode === 'ai_bot');

        return response()->json([
            'success' => true,
            'mode' => $mode,
            'message' => 'Bot mode updated to ' . str_replace('_', ' ', ucfirst($mode)),
        ]);
    }

    /**
     * Save Official WhatsApp Cloud API configuration
     */
    public function saveOfficialApiConfig(Request $request)
    {
        $request->validate([
            'phone_number_id' => 'required|string',
            'access_token' => 'required|string',
            'waba_id' => 'nullable|string',
        ]);

        Setting::setValue('whatsapp', 'official_api_config', [
            'phone_number_id' => $request->phone_number_id,
            'access_token' => $request->access_token,
            'waba_id' => $request->waba_id ?? '',
        ], auth()->user()->company_id);

        // Auto-enable if saving config
        Setting::setValue('whatsapp', 'official_api_enabled', true, auth()->user()->company_id);

        return response()->json(['success' => true, 'message' => 'Official WhatsApp API configuration saved & enabled.']);
    }

    /**
     * Toggle Official WhatsApp Cloud API ON/OFF
     */
    public function toggleOfficialApi(Request $request)
    {
        $enabled = $request->boolean('enabled');
        Setting::setValue('whatsapp', 'official_api_enabled', $enabled, auth()->user()->company_id);

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Official Cloud API enabled. Bot will use native interactive lists.'
                : 'Official Cloud API disabled. Bot will use text-based menus.',
        ]);
    }

    /**
     * Toggle Evolution API (QR Scan) ON/OFF
     */
    public function toggleEvolutionApi(Request $request)
    {
        $enabled = $request->boolean('enabled');
        $companyId = auth()->user()->company_id;
        Setting::setValue('whatsapp', 'evolution_api_enabled', $enabled, $companyId);

        // When master toggle is OFF, sub-features also go OFF
        if (!$enabled) {
            Setting::setValue('whatsapp', 'evolution_followup_enabled', false, $companyId);
            Setting::setValue('whatsapp', 'evolution_bulk_enabled', false, $companyId);
            Setting::setValue('whatsapp', 'evolution_textmenu_enabled', false, $companyId);
        }

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Evolution API (QR Scan) enabled.'
                : 'Evolution API disabled. All features will use Official API.',
        ]);
    }

    /**
     * Toggle individual Evolution API sub-features (followup/bulk/textmenu)
     */
    public function toggleEvolutionSubFeature(Request $request)
    {
        $request->validate([
            'feature' => 'required|in:followup,bulk,textmenu',
            'enabled' => 'required',
        ]);

        $feature = $request->feature;
        $enabled = $request->boolean('enabled');
        $companyId = auth()->user()->company_id;

        Setting::setValue('whatsapp', "evolution_{$feature}_enabled", $enabled, $companyId);

        $labels = [
            'followup' => 'Follow-ups',
            'bulk' => 'Bulk Sender',
            'textmenu' => 'Text Menu',
        ];

        $apiUsed = $enabled ? 'Evolution API (FREE)' : 'Official Cloud API';

        return response()->json([
            'success' => true,
            'feature' => $feature,
            'enabled' => $enabled,
            'message' => "{$labels[$feature]} will now use {$apiUsed}.",
        ]);
    }

    /**
     * Save List Bot settings (welcome message, button text)
     */
    public function saveListBotSettings(Request $request)
    {
        $request->validate([
            'welcome_message' => 'nullable|string|max:1024',
            'menu_button_text' => 'nullable|string|max:20',
        ]);

        Setting::setValue('list_bot', 'welcome_message', $request->welcome_message ?? '');
        Setting::setValue('list_bot', 'menu_button_text', $request->menu_button_text ?? '🛍 Menu');

        return response()->json(['success' => true, 'message' => 'List Bot settings saved']);
    }

    /**
     * Save AI Bot Interactive List Mode toggle
     */
    public function saveInteractiveListMode(Request $request)
    {
        $enabled = $request->boolean('enabled');
        Setting::setValue('ai_bot', 'interactive_list_mode', $enabled);

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Interactive List Mode enabled. Menus will be sent as native WhatsApp lists.'
                : 'Interactive List Mode disabled. Text-based lists will be used.',
        ]);
    }
}
