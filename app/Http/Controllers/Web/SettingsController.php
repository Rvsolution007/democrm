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

        return view('admin.settings.index', compact('company', 'columnVisibility', 'quoteTaxes', 'leadStages', 'leadSources', 'taskStatuses', 'paymentTypes', 'whatsappApiConfig'));
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

        Setting::setValue('whatsapp', 'api_config', [
            'api_url' => rtrim($request->api_url, '/'),
            'api_key' => $request->api_key,
            'webhook_base_url' => rtrim($request->webhook_base_url ?? '', '/'),
        ], auth()->user()->company_id);

        return response()->json(['success' => true, 'message' => 'WhatsApp API configuration saved']);
    }
}
