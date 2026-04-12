<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MetaWhatsappTemplate;
use App\Models\Setting;
use App\Services\MetaTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaTemplateController extends Controller
{
    /**
     * Check if Official API is enabled — guard all actions
     */
    private function ensureOfficialApiEnabled(): bool
    {
        $enabled = (bool) Setting::getValue('whatsapp', 'official_api_enabled', false);
        $config = Setting::getValue('whatsapp', 'official_api_config', []);
        return $enabled && !empty($config['waba_id'] ?? '');
    }

    /**
     * List all Meta templates
     */
    public function index()
    {
        if (!$this->ensureOfficialApiEnabled()) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Please enable Official WhatsApp API and configure WABA ID first.');
        }

        $companyId = auth()->user()->company_id;

        $templates = MetaWhatsappTemplate::where('company_id', $companyId)
            ->latest()
            ->get();

        $stats = [
            'total' => $templates->count(),
            'approved' => $templates->where('status', 'APPROVED')->count(),
            'pending' => $templates->where('status', 'PENDING')->count(),
            'rejected' => $templates->where('status', 'REJECTED')->count(),
        ];

        return view('admin.meta-templates.index', compact('templates', 'stats'));
    }

    /**
     * Show create template form
     */
    public function create()
    {
        if (!$this->ensureOfficialApiEnabled()) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Please enable Official WhatsApp API and configure WABA ID first.');
        }

        return view('admin.meta-templates.create');
    }

    /**
     * Store template and submit to Meta API for review
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-z][a-z0-9_]*$/',
            'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language' => 'required|string|max:10',
            'header_type' => 'required|in:NONE,TEXT',
            'header_text' => 'nullable|string|max:60',
            'body_text' => 'required|string|max:1024',
            'footer_text' => 'nullable|string|max:60',
            'buttons' => 'nullable|array|max:3',
            'buttons.*.type' => 'required_with:buttons|in:URL,PHONE_NUMBER,QUICK_REPLY',
            'buttons.*.text' => 'required_with:buttons|string|max:25',
            'example_values' => 'nullable|array',
            'example_values.*' => 'nullable|string',
        ], [
            'name.regex' => 'Template name must be lowercase, start with a letter, and contain only letters, numbers, and underscores.',
        ]);

        $user = auth()->user();
        $companyId = $user->company_id;

        // Check for duplicate name+language
        $exists = MetaWhatsappTemplate::where('company_id', $companyId)
            ->where('name', $request->name)
            ->where('language', $request->language)
            ->exists();

        if ($exists) {
            return back()->with('error', "Template '{$request->name}' already exists for language '{$request->language}'.")->withInput();
        }

        // Filter out empty buttons
        $buttons = collect($request->buttons ?? [])->filter(fn($b) => !empty($b['text']))->values()->toArray();

        // Filter out empty example values
        $exampleValues = array_filter($request->example_values ?? [], fn($v) => $v !== null && $v !== '');
        $exampleValues = array_values($exampleValues);

        // Create in DB first
        $template = MetaWhatsappTemplate::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'name' => $request->name,
            'category' => $request->category,
            'language' => $request->language,
            'header_type' => $request->header_type,
            'header_text' => $request->header_text,
            'body_text' => $request->body_text,
            'footer_text' => $request->footer_text,
            'buttons' => !empty($buttons) ? $buttons : null,
            'example_values' => !empty($exampleValues) ? $exampleValues : null,
            'status' => 'DRAFT',
        ]);

        // Submit to Meta API
        $service = new MetaTemplateService($companyId);
        $result = $service->createTemplate($template);

        if ($result['success']) {
            $template->update([
                'meta_template_id' => $result['meta_id'],
                'status' => $result['status'] ?? 'PENDING',
                'last_synced_at' => now(),
            ]);

            return redirect()->route('admin.meta-templates.index')
                ->with('success', "Template '{$template->name}' submitted for review! Status: ⏳ PENDING");
        } else {
            // Keep as DRAFT so user can retry
            return redirect()->route('admin.meta-templates.show', $template->id)
                ->with('error', "Template saved locally but Meta API returned error: {$result['error']}");
        }
    }

    /**
     * Show template details
     */
    public function show($id)
    {
        $template = MetaWhatsappTemplate::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('admin.meta-templates.show', compact('template'));
    }

    /**
     * Delete template from Meta API + DB
     */
    public function destroy($id)
    {
        $template = MetaWhatsappTemplate::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        // Try to delete from Meta if it has a meta ID
        if (!empty($template->meta_template_id)) {
            $service = new MetaTemplateService(auth()->user()->company_id);
            $service->deleteTemplate($template);
        }

        $template->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Template deleted']);
        }

        return redirect()->route('admin.meta-templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Sync all templates from Meta API
     */
    public function syncAll()
    {
        $companyId = auth()->user()->company_id;
        $service = new MetaTemplateService($companyId);
        $result = $service->syncAllTemplates($companyId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Synced {$result['synced']} templates ({$result['updated']} status changes)",
                'synced' => $result['synced'],
                'updated' => $result['updated'],
            ]);
        }

        return response()->json(['success' => false, 'message' => $result['error'] ?? 'Sync failed'], 422);
    }

    /**
     * Sync a single template
     */
    public function syncOne($id)
    {
        $template = MetaWhatsappTemplate::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $service = new MetaTemplateService(auth()->user()->company_id);
        $result = $service->syncSingleTemplate($template);

        $template->refresh();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'status' => $template->status,
                'message' => "Template status: {$template->status}",
            ]);
        }

        return response()->json(['success' => false, 'message' => $result['error'] ?? 'Sync failed'], 422);
    }

    /**
     * Retry submitting a DRAFT template to Meta
     */
    public function retry($id)
    {
        $template = MetaWhatsappTemplate::where('company_id', auth()->user()->company_id)
            ->where('status', 'DRAFT')
            ->findOrFail($id);

        $service = new MetaTemplateService(auth()->user()->company_id);
        $result = $service->createTemplate($template);

        if ($result['success']) {
            $template->update([
                'meta_template_id' => $result['meta_id'],
                'status' => $result['status'] ?? 'PENDING',
                'last_synced_at' => now(),
            ]);

            return redirect()->route('admin.meta-templates.show', $template->id)
                ->with('success', 'Template re-submitted for review!');
        }

        return redirect()->route('admin.meta-templates.show', $template->id)
            ->with('error', "Meta API error: {$result['error']}");
    }

    /**
     * API: Get approved Meta templates for auto-reply dropdown
     */
    public function approvedTemplatesJson()
    {
        $templates = MetaWhatsappTemplate::where('company_id', auth()->user()->company_id)
            ->approved()
            ->select('id', 'name', 'category', 'language', 'body_text')
            ->get();

        return response()->json($templates);
    }
}
