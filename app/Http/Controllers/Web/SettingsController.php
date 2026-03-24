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
        $whatsappApiConfig = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => '', 'webhook_base_url' => '']);

        // AI Bot Configuration
        $aiBotEnabled = Setting::getValue('ai_bot', 'enabled', false);
        $aiVertexConfig = Setting::getValue('ai_bot', 'vertex_config', [
            'project_id' => '',
            'location' => 'us-central1',
            'model' => 'gemini-2.0-flash',
            'service_account' => [],
        ]);
        $aiSystemPrompt = Setting::getValue('ai_bot', 'system_prompt', '');
        $aiReplyLanguage = Setting::getValue('ai_bot', 'reply_language', 'auto');

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
            'taskStatuses', 'paymentTypes', 'whatsappApiConfig', 'backupFiles',
            'aiBotEnabled', 'aiVertexConfig', 'aiSystemPrompt', 'aiReplyLanguage'
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

        return response()->json(['success' => true, 'message' => 'AI configuration saved']);
    }

    /**
     * Save AI Bot system prompt
     */
    public function saveAiPrompt(Request $request)
    {
        $request->validate([
            'system_prompt' => 'required|string',
        ]);

        Setting::setValue('ai_bot', 'system_prompt', $request->system_prompt);

        return response()->json(['success' => true, 'message' => 'AI system prompt saved']);
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
        
        if (file_exists($logFile)) {
            // Read last 500 lines efficiently
            $lines = file($logFile);
            $logs = array_slice($lines, -500);
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
}
