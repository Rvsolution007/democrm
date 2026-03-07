<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        if (!can('settings.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $company = Company::first();

        // Load all column_visibility settings from DB
        $columnVisibility = [];
        $settings = Setting::where('company_id', 1)
            ->where('group', 'column_visibility')
            ->get();

        foreach ($settings as $setting) {
            $columnVisibility[$setting->key] = $setting->value;
        }

        $quoteTaxes = Setting::getValue('quotes', 'taxes', [], 1);
        $leadStages = Setting::getValue('leads', 'stages', Lead::STAGES, 1);
        $taskStatuses = Setting::getValue('tasks', 'statuses', Task::STATUSES, 1);
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer'], 1);
        $whatsappApiConfig = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => ''], 1);

        return view('admin.settings.index', compact('company', 'columnVisibility', 'quoteTaxes', 'leadStages', 'taskStatuses', 'paymentTypes', 'whatsappApiConfig'));
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
            $request->columns,
            1 // company_id
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

        Setting::setValue('quotes', 'taxes', $request->taxes, 1);

        return response()->json(['success' => true, 'message' => 'Taxes saved']);
    }

    /**
     * Get column visibility settings for a module via AJAX
     */
    public function getColumnVisibility($module)
    {
        $value = Setting::getValue('column_visibility', $module, null, 1);
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

        Setting::setValue('leads', 'stages', $request->stages, 1);

        return response()->json(['success' => true, 'message' => 'Lead stages saved']);
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

        Setting::setValue('tasks', 'statuses', $request->statuses, 1);

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

        Setting::setValue('payments', 'types', $request->types, 1);

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
        ]);

        Setting::setValue('whatsapp', 'api_config', [
            'api_url' => rtrim($request->api_url, '/'),
            'api_key' => $request->api_key,
        ], 1);

        return response()->json(['success' => true, 'message' => 'WhatsApp API configuration saved']);
    }
}
