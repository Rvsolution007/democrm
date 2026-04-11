<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Company;
use Illuminate\Http\Request;

class SetupTourController extends Controller
{
    /**
     * Show setup tour management page
     */
    public function index()
    {
        // Tour configuration
        $tourConfig = [
            'enabled' => Setting::getGlobalValue('setup_tour', 'enabled', true),
            'auto_show' => Setting::getGlobalValue('setup_tour', 'auto_show_new_business', true),
            'welcome_title' => Setting::getGlobalValue('setup_tour', 'welcome_title', 'Welcome to VyaparCRM! 🚀'),
            'welcome_subtitle' => Setting::getGlobalValue('setup_tour', 'welcome_subtitle', "Let's set up your product catalogue in minutes using AI"),
            'intro_message' => Setting::getGlobalValue('setup_tour', 'intro_message', 'Upload your product catalogue PDF or share your website URL — our AI will automatically analyze your products and create the perfect database structure for you.'),
            'column_analysis_prompt' => Setting::getGlobalValue('setup_tour', 'column_analysis_prompt', ''),
            'product_extraction_prompt' => Setting::getGlobalValue('setup_tour', 'product_extraction_prompt', ''),
        ];

        // Get default prompts from the service for display
        $catalogueService = new \App\Services\CatalogueAIService(0);
        $tourConfig['default_column_prompt'] = $catalogueService->getDefaultColumnAnalysisPromptPublic();
        $tourConfig['default_product_prompt'] = $catalogueService->getDefaultProductExtractionPromptPublic();

        // Stats: businesses that completed the tour
        $totalBusinesses = Company::where('status', 'active')->count();
        $completedTour = Setting::where('group', 'setup_tour')
            ->where('key', 'completed')
            ->where('value', json_encode(true))
            ->whereNotNull('company_id')
            ->count();

        $stats = [
            'total_businesses' => $totalBusinesses,
            'completed_tour' => $completedTour,
            'pending_tour' => $totalBusinesses - $completedTour,
            'completion_rate' => $totalBusinesses > 0 ? round(($completedTour / $totalBusinesses) * 100) : 0,
        ];

        // Recent completions
        $recentCompletions = Setting::where('group', 'setup_tour')
            ->where('key', 'completed_at')
            ->whereNotNull('company_id')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($setting) {
                $company = Company::find($setting->company_id);
                return [
                    'company_name' => $company ? $company->name : 'Unknown',
                    'completed_at' => $setting->value,
                ];
            });

        return view('superadmin.setup-tour.index', compact('tourConfig', 'stats', 'recentCompletions'));
    }

    /**
     * Save tour configuration
     */
    public function save(Request $request)
    {
        $request->validate([
            'welcome_title' => 'required|string|max:200',
            'welcome_subtitle' => 'required|string|max:300',
            'intro_message' => 'required|string|max:1000',
            'column_analysis_prompt' => 'nullable|string|max:20000',
            'product_extraction_prompt' => 'nullable|string|max:20000',
        ]);

        Setting::setGlobalValue('setup_tour', 'enabled', $request->has('enabled'));
        Setting::setGlobalValue('setup_tour', 'auto_show_new_business', $request->has('auto_show'));
        Setting::setGlobalValue('setup_tour', 'welcome_title', $request->welcome_title);
        Setting::setGlobalValue('setup_tour', 'welcome_subtitle', $request->welcome_subtitle);
        Setting::setGlobalValue('setup_tour', 'intro_message', $request->intro_message);

        if ($request->filled('column_analysis_prompt')) {
            Setting::setGlobalValue('setup_tour', 'column_analysis_prompt', $request->column_analysis_prompt);
        } else {
            Setting::setGlobalValue('setup_tour', 'column_analysis_prompt', '');
        }

        if ($request->filled('product_extraction_prompt')) {
            Setting::setGlobalValue('setup_tour', 'product_extraction_prompt', $request->product_extraction_prompt);
        } else {
            Setting::setGlobalValue('setup_tour', 'product_extraction_prompt', '');
        }

        return back()->with('success', 'Setup Tour settings saved successfully!');
    }
}
