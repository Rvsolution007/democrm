<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    /**
     * All available modules for feature gating.
     */
    private function getAllModules(): array
    {
        return [
            // ═══ Tier 1 — Core CRM ═══
            'leads' => ['label' => 'Leads', 'tier' => 1, 'group' => 'CRM Core'],
            'clients' => ['label' => 'Clients', 'tier' => 1, 'group' => 'CRM Core'],
            'quotes' => ['label' => 'Quotes', 'tier' => 1, 'group' => 'CRM Core'],
            'invoices' => ['label' => 'Invoices', 'tier' => 1, 'group' => 'CRM Core'],
            'payments' => ['label' => 'Payments', 'tier' => 1, 'group' => 'CRM Core'],
            'followups' => ['label' => 'Follow-ups', 'tier' => 1, 'group' => 'CRM Core'],
            'products' => ['label' => 'Products', 'tier' => 1, 'group' => 'Catalog'],
            'categories' => ['label' => 'Categories', 'tier' => 1, 'group' => 'Catalog'],
            'users' => ['label' => 'Users', 'tier' => 1, 'group' => 'Team'],
            'roles' => ['label' => 'Roles', 'tier' => 1, 'group' => 'Team'],
            'activities' => ['label' => 'Activities', 'tier' => 1, 'group' => 'Team'],
            'profile' => ['label' => 'My Profile', 'tier' => 1, 'group' => 'Analytics'],
            'reports' => ['label' => 'Reports', 'tier' => 1, 'group' => 'Analytics'],
            'settings' => ['label' => 'Settings', 'tier' => 1, 'group' => 'Analytics'],

            // ═══ Tier 2 — Bot List (WhatsApp + Chatflow + Auto Reply) ═══
            'whatsapp_connect' => ['label' => 'WhatsApp Connect', 'tier' => 2, 'group' => 'Bot List'],
            'whatsapp_campaigns' => ['label' => 'Bulk Campaigns', 'tier' => 2, 'group' => 'Bot List'],
            'whatsapp_templates' => ['label' => 'Templates', 'tier' => 2, 'group' => 'Bot List'],
            'whatsapp_auto_reply' => ['label' => 'Auto-Reply Rules', 'tier' => 2, 'group' => 'Bot List'],
            'whatsapp_analytics' => ['label' => 'Reply Analytics', 'tier' => 2, 'group' => 'Bot List'],
            'chatflow' => ['label' => 'Chatflow Builder', 'tier' => 2, 'group' => 'Bot List'],
            'list_bot' => ['label' => 'List Bot Engine', 'tier' => 2, 'group' => 'Bot List'],
            'catalogue_columns' => ['label' => 'Catalogue Columns', 'tier' => 2, 'group' => 'Bot List'],

            // ═══ Tier 3 — AI Bot (extends Bot List) ═══
            'ai_bot' => ['label' => 'AI Bot Engine', 'tier' => 3, 'group' => 'AI Bot'],
            'token_analytics' => ['label' => 'Token Analytics', 'tier' => 3, 'group' => 'AI Bot'],
            'chat_history' => ['label' => 'Chat History', 'tier' => 3, 'group' => 'AI Bot'],
            'ai_credit_wallet' => ['label' => 'AI Credit Wallet', 'tier' => 3, 'group' => 'AI Bot'],
        ];
    }

    public function index()
    {
        $packages = SubscriptionPackage::ordered()->get();
        $allModules = $this->getAllModules();

        foreach ($packages as $pkg) {
            $pkg->active_subscribers = $pkg->subscriptions()
                ->whereIn('status', ['active', 'trial'])
                ->where('expires_at', '>=', now()->toDateString())
                ->count();
        }

        return view('superadmin.packages.index', compact('packages', 'allModules'));
    }

    public function create()
    {
        $allModules = $this->getAllModules();
        return view('superadmin.packages.form', [
            'package' => null,
            'allModules' => $allModules,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'default_max_users' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
        ]);

        $modules = $request->input('modules', []);
        $modulePermissions = [];
        foreach ($this->getAllModules() as $key => $meta) {
            $modulePermissions[$key] = in_array($key, $modules);
        }

        $package = SubscriptionPackage::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'monthly_price' => $request->monthly_price,
            'yearly_price' => $request->yearly_price,
            'default_max_users' => $request->default_max_users,
            'max_leads_per_month' => $request->max_leads_per_month ?? 0,
            'features' => array_keys(array_filter($modulePermissions)),
            'module_permissions' => $modulePermissions,
            'trial_days' => $request->trial_days,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? (SubscriptionPackage::max('sort_order') + 1),
        ]);

        return redirect()->route('superadmin.packages.index')
            ->with('success', "Package '{$package->name}' created successfully!");
    }

    public function edit(SubscriptionPackage $package)
    {
        $allModules = $this->getAllModules();
        return view('superadmin.packages.form', compact('package', 'allModules'));
    }

    public function update(Request $request, SubscriptionPackage $package)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'default_max_users' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
        ]);

        $modules = $request->input('modules', []);
        $modulePermissions = [];
        foreach ($this->getAllModules() as $key => $meta) {
            $modulePermissions[$key] = in_array($key, $modules);
        }

        $package->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'monthly_price' => $request->monthly_price,
            'yearly_price' => $request->yearly_price,
            'default_max_users' => $request->default_max_users,
            'max_leads_per_month' => $request->max_leads_per_month ?? 0,
            'features' => array_keys(array_filter($modulePermissions)),
            'module_permissions' => $modulePermissions,
            'trial_days' => $request->trial_days,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? $package->sort_order,
        ]);

        return redirect()->route('superadmin.packages.index')
            ->with('success', "Package '{$package->name}' updated successfully!");
    }

    public function toggleActive(SubscriptionPackage $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        $status = $package->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Package '{$package->name}' {$status}.");
    }
}
