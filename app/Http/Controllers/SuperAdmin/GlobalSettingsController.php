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

        return view('superadmin.settings.index', compact('creditSettings', 'platformSettings', 'razorpaySettings'));
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
}
