<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class GlobalSettingsController extends Controller
{
    public function index()
    {
        // AI Credit Settings
        $creditSettings = [
            'credits_per_1k_tokens' => Setting::getGlobalValue('ai_credits', 'credits_per_1k_tokens', 1.2),
            'min_credits_to_operate' => Setting::getGlobalValue('ai_credits', 'min_credits_to_operate', 10),
            'low_balance_threshold' => Setting::getGlobalValue('ai_credits', 'low_balance_threshold', 50),
            'alert_admin_on_low' => Setting::getGlobalValue('ai_credits', 'alert_admin_on_low', true),
            'alert_sa_on_low' => Setting::getGlobalValue('ai_credits', 'alert_sa_on_low', true),
        ];

        // Platform settings
        $platformSettings = [
            'maintenance_mode' => Setting::getGlobalValue('platform', 'maintenance_mode', false),
            'default_trial_days' => Setting::getGlobalValue('platform', 'default_trial_days', 14),
            'grace_period_days' => Setting::getGlobalValue('platform', 'grace_period_days', 3),
            'support_email' => Setting::getGlobalValue('platform', 'support_email', ''),
            'support_phone' => Setting::getGlobalValue('platform', 'support_phone', ''),
        ];

        // Razorpay settings
        $razorpaySettings = [
            'key_id' => config('services.razorpay.key', env('RAZORPAY_KEY_ID', '')),
            'key_secret' => !empty(env('RAZORPAY_KEY_SECRET')) ? '••••••••' : '',
            'webhook_secret' => !empty(env('RAZORPAY_WEBHOOK_SECRET')) ? '••••••••' : '',
            'enabled' => Setting::getGlobalValue('payment', 'razorpay_enabled', false),
        ];

        // ─── AI Config (Vertex + Prompts) ───
        $vertexConfig = Setting::getGlobalValue('ai_bot', 'vertex_config', []);
        if (is_string($vertexConfig)) $vertexConfig = json_decode($vertexConfig, true) ?? [];

        $aiConfig = [
            'project_id' => $vertexConfig['project_id'] ?? '',
            'location' => $vertexConfig['location'] ?? 'us-central1',
            'model' => $vertexConfig['model'] ?? 'gemini-1.5-flash-001',
            'service_account' => $vertexConfig['service_account'] ?? null,
        ];

        $aiPrompts = [
            'system_prompt' => Setting::getGlobalValue('ai_bot', 'system_prompt', ''),
            'greeting_prompt' => Setting::getGlobalValue('ai_bot', 'greeting_prompt', ''),
            'business_prompt' => Setting::getGlobalValue('ai_bot', 'business_prompt', ''),
            'spell_prompt' => Setting::getGlobalValue('ai_bot', 'spell_correction_prompt', ''),
            'tier3_prompt' => Setting::getGlobalValue('ai_bot', 'tier3_prompt', ''),
        ];

        // ─── Evolution API Config ───
        $evoConfig = Setting::getGlobalValue('whatsapp', 'api_config', []);
        if (is_string($evoConfig)) $evoConfig = json_decode($evoConfig, true) ?? [];

        $evolutionConfig = [
            'api_url' => $evoConfig['api_url'] ?? '',
            'api_key' => $evoConfig['api_key'] ?? '',
            'webhook_base_url' => $evoConfig['webhook_base_url'] ?? '',
        ];

        return view('superadmin.settings.index', compact(
            'creditSettings', 'platformSettings', 'razorpaySettings',
            'aiConfig', 'aiPrompts', 'evolutionConfig'
        ));
    }

    public function saveCredits(Request $request)
    {
        $request->validate([
            'credits_per_1k_tokens' => 'required|numeric|min:0.01',
            'min_credits_to_operate' => 'required|integer|min:1',
            'low_balance_threshold' => 'required|integer|min:1',
        ]);

        Setting::setGlobalValue('ai_credits', 'credits_per_1k_tokens', (float) $request->credits_per_1k_tokens);
        Setting::setGlobalValue('ai_credits', 'min_credits_to_operate', (int) $request->min_credits_to_operate);
        Setting::setGlobalValue('ai_credits', 'low_balance_threshold', (int) $request->low_balance_threshold);
        Setting::setGlobalValue('ai_credits', 'alert_admin_on_low', $request->has('alert_admin_on_low'));
        Setting::setGlobalValue('ai_credits', 'alert_sa_on_low', $request->has('alert_sa_on_low'));

        return back()->with('success', 'AI Credit settings saved successfully!');
    }

    public function savePlatform(Request $request)
    {
        $request->validate([
            'default_trial_days' => 'required|integer|min:0',
            'grace_period_days' => 'required|integer|min:0',
            'support_email' => 'nullable|email|max:100',
            'support_phone' => 'nullable|string|max:20',
        ]);

        Setting::setGlobalValue('platform', 'maintenance_mode', $request->has('maintenance_mode'));
        Setting::setGlobalValue('platform', 'default_trial_days', (int) $request->default_trial_days);
        Setting::setGlobalValue('platform', 'grace_period_days', (int) $request->grace_period_days);
        Setting::setGlobalValue('platform', 'support_email', $request->support_email ?? '');
        Setting::setGlobalValue('platform', 'support_phone', $request->support_phone ?? '');

        return back()->with('success', 'Platform settings saved successfully!');
    }

    public function savePayment(Request $request)
    {
        Setting::setGlobalValue('payment', 'razorpay_enabled', $request->has('razorpay_enabled'));

        return back()->with('success', 'Payment settings saved successfully!');
    }

    // ═══════════════════════════════════════════════════════
    // AI Config — Global (all businesses)
    // ═══════════════════════════════════════════════════════

    public function saveAiConfig(Request $request)
    {
        $request->validate([
            'project_id' => 'required|string',
            'location' => 'required|string',
            'model' => 'required|string',
            'service_account_json' => 'required|string',
        ]);

        $serviceAccount = json_decode($request->service_account_json, true);
        if (!$serviceAccount || !isset($serviceAccount['client_email']) || !isset($serviceAccount['private_key'])) {
            return back()->with('error', 'Invalid service account JSON. Must contain client_email and private_key.');
        }

        Setting::setGlobalValue('ai_bot', 'vertex_config', [
            'project_id' => $request->project_id,
            'location' => $request->location,
            'model' => $request->model,
            'service_account' => $serviceAccount,
        ]);

        // Clear cached access token
        \Illuminate\Support\Facades\Cache::forget('vertex_ai_token_' . md5($request->project_id));

        return back()->with('success', 'Vertex AI configuration saved!');
    }

    public function saveAiPrompts(Request $request)
    {
        $request->validate([
            'system_prompt' => 'nullable|string',
            'greeting_prompt' => 'nullable|string',
            'business_prompt' => 'nullable|string',
            'spell_prompt' => 'nullable|string',
            'tier3_prompt' => 'nullable|string',
        ]);

        Setting::setGlobalValue('ai_bot', 'system_prompt', $request->system_prompt ?? '');
        Setting::setGlobalValue('ai_bot', 'greeting_prompt', $request->greeting_prompt ?? '');
        Setting::setGlobalValue('ai_bot', 'business_prompt', $request->business_prompt ?? '');
        Setting::setGlobalValue('ai_bot', 'spell_correction_prompt', $request->spell_prompt ?? '');
        Setting::setGlobalValue('ai_bot', 'tier3_prompt', $request->tier3_prompt ?? '');

        return back()->with('success', 'AI Prompts saved!');
    }

    public function saveEvolutionConfig(Request $request)
    {
        $request->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'webhook_base_url' => 'nullable|url',
        ]);

        $webhookBase = $request->webhook_base_url ?? '';
        if (!empty($webhookBase) && !str_contains($webhookBase, 'localhost') && !str_contains($webhookBase, '127.0.0.1')) {
            $webhookBase = str_replace('http://', 'https://', rtrim($webhookBase, '/'));
        } else {
            $webhookBase = rtrim($webhookBase, '/');
        }

        Setting::setGlobalValue('whatsapp', 'api_config', [
            'api_url' => rtrim($request->api_url, '/'),
            'api_key' => $request->api_key,
            'webhook_base_url' => $webhookBase,
        ]);

        return back()->with('success', 'Evolution API configuration saved!');
    }

    // ═══════════════════════════════════════════════════════
    // Per-Business Match Config (from SA business detail)
    // ═══════════════════════════════════════════════════════

    public function saveMatchConfidence(Request $request, $companyId)
    {
        $request->validate(['confidence' => 'required|integer|min:0|max:100']);
        Setting::setValue('ai_bot', 'match_confidence', (int) $request->confidence, $companyId);
        return response()->json(['success' => true, 'message' => 'Match confidence saved']);
    }

    public function testMatch(Request $request, $companyId)
    {
        $request->validate(['message' => 'required|string|max:500']);

        // Run match test against this business's products
        try {
            $service = app(\App\Services\ListBotService::class);
            $result = $service->testProductGroupMatch($request->message, $companyId);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function clearMatchCache(Request $request, $companyId)
    {
        $cacheKey = 'pgm_index_' . $companyId;
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        return response()->json(['success' => true, 'message' => 'Product match cache cleared']);
    }
}
