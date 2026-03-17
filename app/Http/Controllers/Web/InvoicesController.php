<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteItem;
use App\Models\Company;
use App\Models\User;
use App\Models\Setting;
use App\Models\Project;
use App\Models\Task;
use App\Models\Purchase;
use App\Models\ServiceTemplate;
use App\Models\MicroTask;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Quote::with(['client', 'lead', 'assignedUsers', 'items']);

        // Global permission filter
        if (!can('quotes.global')) {
            $query->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    });
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('business_name', 'like', "%{$search}%")
                           ->orWhere('contact_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // User Filter
        if ($request->filled('assigned_to_user_id')) {
            $query->whereHas('assignedUsers', function($q) use($request) {
                $q->where('user_id', $request->assigned_to_user_id);
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Start Date filter
        if ($request->filled('start_date')) {
            $query->whereDate('quote_date', '>=', $request->start_date);
        }

        // Due/Valid Until Date filter
        if ($request->filled('due_date')) {
            $query->whereDate('valid_until', '<=', $request->due_date);
        }

        // Only fetch accepted quotes (Invoices)
        $query->where('status', 'accepted');

        $clientSummaryQuery = clone $query;
        $clientTotalAmount = $clientSummaryQuery->sum('grand_total') / 100;

        $clientDueQuery = clone $query;
        $clientDueAmount = $clientDueQuery->sum('grand_total') / 100;

        $invoices = $query->with(['payments', 'items'])->latest()->paginate(20)->withQueryString();
        $clients = Client::all();
        $products = Product::all();

        $leadsQuery = Lead::query();
        if (!can('leads.global')) {
            $leadsQuery->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    });
            });
        }
        $leads = $leadsQuery->orderBy('name')->get();

        $users = (can('quotes.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('quotes')->orderBy('name')->get()
            : collect();

        $companyId = auth()->check() ? auth()->user()->company_id : 1;
        
        // Fetch company specific taxes
        $companyTaxes = Setting::getValue('quotes', 'taxes', [], $companyId);
        
        // Fetch global taxes (company 1) as fallback/addition
        $globalTaxes = $companyId !== 1 ? Setting::getValue('quotes', 'taxes', [], 1) : [];
        
        // Merge and ensure uniqueness by name and rate
        $allTaxes = array_merge($globalTaxes, $companyTaxes);
        
        $quoteTaxes = collect($allTaxes)->unique(function ($item) {
            return ($item['name'] ?? '') . '_' . ($item['rate'] ?? 0);
        })->values()->toArray();
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        // Users with projects.global permission (for convert-to-client assign dropdown)
        $projectGlobalUsers = User::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('role', function ($rq) {
                    $rq->whereJsonContains('permissions', 'projects.global');
                })->orWhereHas('role', function ($rq) {
                    $rq->whereJsonContains('permissions', 'all');
                });
            })
            ->orderBy('name')
            ->get();

        return view('admin.invoices.index', compact('invoices', 'clients', 'products', 'leads', 'users', 'quoteTaxes', 'paymentTypes', 'clientTotalAmount', 'clientDueAmount', 'projectGlobalUsers'));
    }

    public function store(Request $request)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'client_type' => 'required|in:client,lead',
            'client_id' => 'required_if:client_type,client|nullable|exists:clients,id',
            'lead_id' => 'required_if:client_type,lead|nullable|exists:leads,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate invoice number
        $company = auth()->user()->company;
        $quoteNumber = Quote::generateInvoiceNumber($company);

        // Convert rupees to paise (model stores in paise)
        $subtotal = (int) (($validated['subtotal'] ?? 0) * 100);
        $discount = (int) (($validated['discount'] ?? 0) * 100);
        $taxAmount = (int) (($validated['tax_amount'] ?? 0) * 100);
        $grandTotal = $subtotal - $discount + $taxAmount;

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        Quote::create([
            'company_id' => auth()->user()->company_id,
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'created_by_user_id' => auth()->id(),
            'quote_no' => $quoteNumber,
            'date' => $validated['quote_date'],
            'valid_till' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gst_total' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => 'accepted',
            'notes' => $validated['notes'] ?? null,
        ]);

        $quote = Quote::latest()->first();

        $assignedUsers = [];
        if (!can('quotes.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $quote->assignedUsers()->sync($assignedUsers);

        // Create QuoteItems from selected products
        if ($request->has('product_ids')) {
            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);

                    // Respect user provided price or fallback to product price
                    if (isset($request->product_prices[$index])) {
                        $unitPrice = (float) $request->product_prices[$index];
                    } else {
                        $unitPrice = ($product->mrp ?: $product->sale_price) / 100;
                    }

                    $ratePaise = (int) ($unitPrice * 100);
                    $discountPaise = (int) ($discountPerUnit * 100);
                    $finalUnitPricePaise = $ratePaise; // Just save original rate in unit_price or ignore it, but we calculate flat discount. Wait, unit_price isn't used for subtotal if we change calculateTotals but let's keep unit_price = rate.

                    $desc = $request->product_descriptions[$index] ?? $product->description ?? '';

                    $purchaseAmountPaise = (int) round(((float) ($request->product_purchase_amounts[$index] ?? 0)) * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $desc,
                        'qty' => $qty,
                        'rate' => $ratePaise,
                        'discount' => $discountPaise,
                        'purchase_amount' => $purchaseAmountPaise,
                        'unit_price' => $finalUnitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();
        }

        // If directly creating an Invoice for a client, we may want to auto-create projects.
        $project = null;
        if ($quote->client_id && $quote->status === 'accepted') {
            $project = $this->autoCreateProjectAndPurchases($quote);
        }

        $response = ['message' => 'Invoice created successfully', 'redirect' => route('admin.invoices.index')];
        if ($project) {
            $response['project_id'] = $project->id;
            $response['project_name'] = $project->name;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($response);
        }

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice created successfully');
    }

    public function edit($id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'assignedUsers'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own quotes.');
        }

        return response()->json($quote);
    }

    public function show($id)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'client', 'assignedUsers', 'createdBy'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only view your own quotes.');
        }

        return response()->json($quote);
    }

    public function download($id)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'client', 'company', 'createdBy', 'payments'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only download your own quotes.');
        }

        // Fetch tax configuration
        $companyId = auth()->check() ? auth()->user()->company_id : 1;
        $companyTaxes = Setting::getValue('quotes', 'taxes', [], $companyId);
        $globalTaxes = $companyId !== 1 ? Setting::getValue('quotes', 'taxes', [], 1) : [];
        $allTaxes = array_merge($globalTaxes, $companyTaxes);
        $quoteTaxes = collect($allTaxes)->unique(function ($item) {
            return ($item['name'] ?? '') . '_' . ($item['rate'] ?? 0);
        })->values()->toArray();

        return view('admin.invoices.download', compact('quote', 'quoteTaxes'));
    }

    public function update(Request $request, $id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        // Non-global users can only update their own quotes
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own quotes.');
        }

        $validated = $request->validate([
            'client_type' => 'required|in:client,lead',
            'client_id' => 'required_if:client_type,client|nullable|exists:clients,id',
            'lead_id' => 'required_if:client_type,lead|nullable|exists:leads,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Convert rupees to paise
        $subtotal = (int) (($validated['subtotal'] ?? 0) * 100);
        $discount = (int) (($validated['discount'] ?? 0) * 100);
        $taxAmount = (int) (($validated['tax_amount'] ?? 0) * 100);
        $grandTotal = $subtotal - $discount + $taxAmount;

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        // Capture original product IDs to check if they changed
        $originalProductIds = $quote->items->pluck('product_id')->toArray();
        $productsChanged = false;

        $quote->update([
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'date' => $validated['quote_date'],
            'valid_till' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gst_total' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $assignedUsers = [];
        if (!can('quotes.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $quote->assignedUsers()->sync($assignedUsers);

        // Sync products: Delete old items and recreate new ones
        if ($request->has('product_ids')) {
            // Check if product IDs differ from original
            $newProductIds = array_map('intval', $request->product_ids);
            sort($originalProductIds);
            sort($newProductIds);
            if ($originalProductIds !== $newProductIds) {
                $productsChanged = true;
            }

            $quote->items()->delete();

            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);

                    // Respect user provided price or fallback to product price
                    if (isset($request->product_prices[$index])) {
                        $unitPrice = (float) $request->product_prices[$index];
                    } else {
                        $unitPrice = ($product->mrp ?: $product->sale_price) / 100;
                    }

                    $ratePaise = (int) ($unitPrice * 100);
                    $discountPaise = (int) ($discountPerUnit * 100);
                    $finalUnitPricePaise = $ratePaise;

                    $desc = $request->product_descriptions[$index] ?? $product->description ?? '';

                    $purchaseAmountPaise = (int) round(((float) ($request->product_purchase_amounts[$index] ?? 0)) * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $desc,
                        'qty' => $qty,
                        'rate' => $ratePaise,
                        'discount' => $discountPaise,
                        'purchase_amount' => $purchaseAmountPaise,
                        'unit_price' => $finalUnitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();

            // Sync purchase_amount to existing purchases linked to this quote's project
            $projectQuery = \App\Models\Project::query();
            if ($quote->lead_id) {
                $projectQuery->where(function($q) use ($quote) {
                    $q->where('lead_id', $quote->lead_id)
                      ->orWhere('quote_id', $quote->id);
                });
            } else {
                $projectQuery->where('quote_id', $quote->id);
            }
            $project = $projectQuery->first();
            if ($project) {
                foreach ($quote->items as $item) {
                    if ($item->product_id && $item->purchase_amount > 0) {
                        $purchase = Purchase::where('project_id', $project->id)
                            ->where('product_id', $item->product_id)
                            ->first();
                        if ($purchase) {
                            $purchase->update(['total_amount' => $item->purchase_amount]);
                        }
                    }
                }
            }
        } elseif ($request->has('clear_products')) {
            $quote->items()->delete();
        }

        // Since this is an invoice, it remains accepted. We will run auto-creation if client is attached and not yet created.
        if ($quote->client_id && $quote->status === 'accepted') {
            $this->autoCreateProjectAndPurchases($quote);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Invoice updated successfully', 'redirect' => route('admin.invoices.index')]);
        }

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice updated successfully');
    }

    // Removed convert method entirely since we convert quotes in QuotesController

    /**
     * Auto-create a Project (with tasks) and Purchases for a client quote.
     * This runs when a quote is created/updated with a client_id.
     * It mirrors the logic from LeadsController::createProjectsAndTasks.
     */
    private function autoCreateProjectAndPurchases(Quote $quote): ?Project
    {
        $quote->load('items');

        if ($quote->items->isEmpty()) {
            return null;
        }

        $client = Client::find($quote->client_id);
        if (!$client) {
            return null;
        }

        $companyId = $client->company_id ?? auth()->user()->company_id;

        // Check if a project already exists for this quote
        $project = Project::where('quote_id', $quote->id)->first();

        // Also check by client_id + lead_id if quote has a lead
        if (!$project && $quote->lead_id) {
            $project = Project::where('lead_id', $quote->lead_id)
                ->where('client_id', $client->id)
                ->first();
        }

        if ($project) {
            // Update the budget
            $project->update(['budget' => $quote->grand_total]);
        } else {
            // Create a new project for this quote
            $project = Project::create([
                'company_id' => $companyId,
                'client_id' => $client->id,
                'quote_id' => $quote->id,
                'lead_id' => $quote->lead_id,
                'created_by_user_id' => auth()->id(),
                'name' => $client->display_name . ' - Project',
                'status' => 'pending',
                'start_date' => now()->toDateString(),
                'budget' => $quote->grand_total,
            ]);

            $assignedUsers = $quote->assignedUsers->pluck('id')->toArray();
            if (empty($assignedUsers)) {
                $assignedUsers = [auth()->id()];
            }
            $project->assignedUsers()->sync($assignedUsers);
        }

        // Get existing task titles to avoid duplicates
        $existingTaskTitles = Task::where('project_id', $project->id)
            ->pluck('title')
            ->map(fn($t) => strtolower(trim($t)))
            ->toArray();

        // Get existing purchase product IDs to avoid duplicates
        $existingPurchaseProductIds = Purchase::where('project_id', $project->id)
            ->pluck('product_id')
            ->toArray();

        $sortOrder = Task::where('project_id', $project->id)->max('sort_order') ?? 0;

        foreach ($quote->items as $item) {
            // Auto-create purchase if product has is_purchase_enabled
            if ($item->product_id && !in_array($item->product_id, $existingPurchaseProductIds)) {
                $product = Product::find($item->product_id);
                if ($product && $product->is_purchase_enabled) {
                    $company = Company::find($companyId);
                    // Use custom purchase_amount if set, otherwise fall back to line item total
                    $purchaseTotalPaise = ($item->purchase_amount > 0)
                        ? $item->purchase_amount
                        : $item->unit_price * max(1, $item->qty);
                    Purchase::create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'project_id' => $project->id,
                        'product_id' => $product->id,
                        'purchase_no' => Purchase::generatePurchaseNumber($company),
                        'date' => now()->toDateString(),
                        'total_amount' => $purchaseTotalPaise,
                        'paid_amount' => 0,
                        'status' => 'draft',
                        'notes' => 'Auto-generated from quote ' . $quote->quote_no . ' for product: ' . $product->name,
                    ]);
                    $existingPurchaseProductIds[] = $product->id;
                }
            }

            $taskTitle = $item->product_name;

            // Skip if this project already has a task for this product
            if (in_array(strtolower(trim($taskTitle)), $existingTaskTitles)) {
                continue;
            }

            // Check for a linked ServiceTemplate
            $template = null;
            if ($item->product_id) {
                $template = ServiceTemplate::where('product_id', $item->product_id)
                    ->where('is_active', true)
                    ->first();
            }

            $sortOrder++;
            $task = Task::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'created_by_user_id' => auth()->id(),
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'title' => $taskTitle,
                'description' => ($item->description ? $item->description . ' | ' : '') . 'Qty: ' . $item->qty,
                'priority' => 'medium',
                'status' => 'todo',
                'sort_order' => $sortOrder,
            ]);

            $assignedUsers = $quote->assignedUsers->pluck('id')->toArray();
            if (empty($assignedUsers)) {
                $assignedUsers = [auth()->id()];
            }
            $task->assignedUsers()->sync($assignedUsers);

            $existingTaskTitles[] = strtolower(trim($taskTitle));

            // Auto-create micro tasks if template exists
            if ($template && !empty($template->getTaskSteps())) {
                foreach ($template->getTaskSteps() as $stepIndex => $step) {
                    MicroTask::create([
                        'task_id' => $task->id,
                        'role_id' => $step['role_id'] ?? null,
                        'title' => $step['title'],
                        'status' => 'todo',
                        'sort_order' => $step['order'] ?? ($stepIndex + 1),
                    ]);
                }
            }
        }

        return $project;
    }

    public function destroy($id)
    {
        if (!can('quotes.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        // Non-global users can only delete their own quotes
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only delete your own quotes.');
        }

        $quote->delete();

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted successfully');
    }
}
