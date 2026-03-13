<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Company;
use App\Models\User;
use App\Models\Setting;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\ServiceTemplate;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('leads.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Lead::with(['assignedTo', 'products']);

        // Global permission filter
        if (!can('leads.global')) {
            $query->where(function ($q) {
                $q->where('assigned_to_user_id', auth()->id())
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Stage filter
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        // Created Date filter (Range)
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Assigned filter
        if ($request->filled('assigned')) {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to_user_id');
            } else {
                $query->where('assigned_to_user_id', $request->assigned);
            }
        }

        // Get paginated leads for list view
        $leads = $query->latest()->paginate(20)->withQueryString();

        // Get all leads for kanban view (without pagination)
        $allLeads = $query->latest()->get();

        // Calculate total amount for all filtered leads
        $totalAmount = $allLeads->sum(function ($lead) {
            return $lead->total_amount;
        });

        $users = (can('leads.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('leads')->get()
            : collect();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $columnVisibility = Setting::getValue('column_visibility', 'leads', []);

        return view('admin.leads.index', compact('leads', 'allLeads', 'users', 'products', 'columnVisibility', 'totalAmount'));
    }

    /**
     * WhatsApp Extension: Bulk phone lookup for lead status badges.
     * GET /admin/leads/whatsapp-lookup?phones[]=91xxxxx&phones[]=91yyyyy
     */
    public function whatsappLookup(Request $request)
    {
        $phones = $request->input('phones', []);
        $names = $request->input('names', []);

        if ((empty($phones) || !is_array($phones)) && (empty($names) || !is_array($names))) {
            return response()->json(['leads' => [], 'leadsByName' => [], 'stages' => []]);
        }

        // ---- Phone-based lookup ----
        $result = [];
        if (!empty($phones) && is_array($phones)) {
            // Limit to 100 phone numbers per request
            $phones = array_slice($phones, 0, 100);

            // Clean phone numbers — keep only digits, take last 10
            $cleanedPhones = [];
            foreach ($phones as $phone) {
                $digits = preg_replace('/\D/', '', $phone);
                $last10 = substr($digits, -10);
                if (strlen($last10) === 10) {
                    $cleanedPhones[$last10] = $phone; // map last10 → original
                }
            }

            if (!empty($cleanedPhones)) {
                // Query leads matching any of the phone numbers (fuzzy: last 10 digits)
                $phoneQuery = Lead::query();

                // Apply permission filter
                if (!can('leads.global')) {
                    $phoneQuery->where(function ($q) {
                        $q->where('assigned_to_user_id', auth()->id())
                            ->orWhere('created_by_user_id', auth()->id());
                    });
                }

                $phoneLeads = $phoneQuery->get(['id', 'name', 'phone', 'stage']);

                // Build result map: original phone → lead data
                foreach ($phoneLeads as $lead) {
                    $leadLast10 = substr(preg_replace('/\D/', '', $lead->phone), -10);
                    if (isset($cleanedPhones[$leadLast10])) {
                        $originalPhone = $cleanedPhones[$leadLast10];
                        // If multiple leads match same phone, use the most recent (highest ID)
                        if (!isset($result[$originalPhone]) || $lead->id > $result[$originalPhone]['id']) {
                            $result[$originalPhone] = [
                                'id' => $lead->id,
                                'name' => $lead->name,
                                'stage' => $lead->stage,
                            ];
                        }
                    }
                }
            }
        }

        // ---- Name-based lookup ----
        $resultByName = [];
        if (!empty($names) && is_array($names)) {
            // Limit to 100 names per request
            $names = array_slice($names, 0, 100);

            $nameQuery = Lead::query();

            // Apply permission filter
            if (!can('leads.global')) {
                $nameQuery->where(function ($q) {
                    $q->where('assigned_to_user_id', auth()->id())
                        ->orWhere('created_by_user_id', auth()->id());
                });
            }

            // Build LIKE conditions for case-insensitive name matching
            $nameQuery->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhere('name', 'LIKE', trim($name));
                }
            });

            $nameLeads = $nameQuery->get(['id', 'name', 'phone', 'stage']);

            foreach ($nameLeads as $lead) {
                $key = strtolower(trim($lead->name));
                // If multiple leads match same name, use the most recent (highest ID)
                if (!isset($resultByName[$key]) || $lead->id > $resultByName[$key]['id']) {
                    $resultByName[$key] = [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'phone' => $lead->phone,
                        'stage' => $lead->stage,
                    ];
                }
            }
        }

        // Get dynamic stages
        $stages = Lead::getDynamicStages();

        // Stage colors
        $stageColors = [
            'new' => '#3b82f6',
            'contacted' => '#f97316',
            'qualified' => '#8b5cf6',
            'proposal' => '#6366f1',
            'negotiation' => '#f59e0b',
            'won' => '#22c55e',
            'lost' => '#ef4444',
        ];

        return response()->json([
            'leads' => $result,
            'leadsByName' => $resultByName,
            'stages' => $stages,
            'stageColors' => $stageColors,
        ]);
    }

    /**
     * Build validation rules based on column visibility settings.
     * Hidden (unchecked) columns become nullable instead of required.
     */
    private function getValidationRules(): array
    {
        $vis = Setting::getValue('column_visibility', 'leads', []);

        $r = function (string $col, string $default) use ($vis) {
            return (isset($vis[$col]) && $vis[$col] === false) ? 'nullable' : $default;
        };

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:15',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'source' => $r('source', 'required') . '|string',
            'stage' => $r('stage', 'required') . '|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ];
    }

    public function store(Request $request)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate($this->getValidationRules());

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();

        // Non-global users: auto-assign to self
        if (!can('leads.global') && !auth()->user()->isAdmin()) {
            $validated['assigned_to_user_id'] = auth()->id();
        }

        $lead = Lead::create($validated);

        // Sync products if provided
        if ($request->has('product_ids')) {
            $productData = [];
            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = ($request->product_quantities[$index] ?? 1) ?: 1;
                    $disc = ($request->product_discounts[$index] ?? 0) * 100;
                    $price = isset($request->product_prices[$index])
                        ? $request->product_prices[$index] * 100
                        : ($product->mrp ?: $product->sale_price);
                    $productData[$productId] = [
                        'quantity' => $qty,
                        'price' => $price,
                        'discount' => $disc,
                    ];
                }
            }
            $lead->products()->sync($productData);
        }

        // Send assignment notification
        if (!empty($lead->assigned_to_user_id) && $lead->assigned_to_user_id != auth()->id()) {
            $assignedUser = \App\Models\User::find($lead->assigned_to_user_id);
            if ($assignedUser) {
                $assignedUser->notify(new \App\Notifications\AssignedNotification(
                    'lead',
                    $lead->id,
                    $lead->name,
                    auth()->user()->name
                ));
            }
        }

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead created successfully');
    }

    public function edit($id)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::with(['products', 'quotes', 'followups.user'])->findOrFail($id);
        $data = $lead->toArray();
        $data['quote_id'] = $lead->quotes->first()?->id ?? null;
        return response()->json($data);
    }

    public function show($id)
    {
        if (!can('leads.read')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::with(['products', 'quotes', 'assignedTo', 'createdBy', 'followups.user'])->findOrFail($id);

        if (!can('leads.global') && $lead->created_by_user_id != auth()->id() && $lead->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only view your own leads.');
        }

        return response()->json($lead);
    }

    public function update(Request $request, $id)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::findOrFail($id);

        // Non-global users can only update their own leads
        if (!can('leads.global') && $lead->created_by_user_id != auth()->id() && $lead->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only edit your own leads.');
        }

        $validated = $request->validate($this->getValidationRules());

        // Non-global users: keep assigned to self
        if (!can('leads.global') && !auth()->user()->isAdmin()) {
            $validated['assigned_to_user_id'] = auth()->id();
        }

        $lead->update($validated);

        // Sync products if provided
        if ($request->has('product_ids')) {
            $productData = [];
            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = ($request->product_quantities[$index] ?? 1) ?: 1;
                    $disc = ($request->product_discounts[$index] ?? 0) * 100;
                    $price = isset($request->product_prices[$index])
                        ? $request->product_prices[$index] * 100
                        : ($product->mrp ?: $product->sale_price);
                    $description = $request->product_descriptions[$index] ?? null;
                    $productData[$productId] = [
                        'quantity' => $qty,
                        'price' => $price,
                        'discount' => $disc,
                        'description' => $description,
                    ];
                }
            }
            $lead->products()->sync($productData);
        } else {
            $lead->products()->detach();
        }

        // Sync lead products to associated quote (if exists)
        $lead->load('products');
        $quote = Quote::where('lead_id', $lead->id)->first();
        if ($quote) {
            // Delete all existing quote items
            $quote->items()->delete();

            // Re-create QuoteItems from updated lead products
            $subtotal = 0;
            foreach ($lead->products as $index => $product) {
                $qty = $product->pivot->quantity ?? 1;
                $price = $product->pivot->price ?? ($product->mrp ?: $product->sale_price);
                $discount = $product->pivot->discount ?? 0;
                $unitPrice = $price - $discount;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'description' => $product->description ?? '',
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'gst_percent' => 0,
                    'gst_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => $index,
                ]);
            }

            // Recalculate quote totals
            $quote->subtotal = $subtotal;
            $quote->grand_total = $subtotal - $quote->discount + ($quote->gst_total ?: 0);
            $quote->save();
        }

        // Send assignment notification if assignee changed
        if (!empty($lead->assigned_to_user_id) && $lead->wasChanged('assigned_to_user_id')) {
            $assignedUser = \App\Models\User::find($lead->assigned_to_user_id);
            if ($assignedUser) {
                $assignedUser->notify(new \App\Notifications\AssignedNotification(
                    'lead',
                    $lead->id,
                    $lead->name,
                    auth()->user()->name
                ));
            }
        }

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead updated successfully');
    }

    public function updateStage(Request $request, $id)
    {
        if (!can('leads.write')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = Lead::findOrFail($id);
        $dynamicStages = Lead::getDynamicStages();

        $validated = $request->validate([
            'stage' => 'required|string|in:' . implode(',', $dynamicStages),
        ]);

        $lead->update(['stage' => $validated['stage']]);

        return response()->json(['success' => true, 'stage' => $lead->stage]);
    }

    public function convertToQuote($id)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::with('products')->findOrFail($id);

        // Check if quote already exists for this lead
        $existingQuote = Quote::where('lead_id', $lead->id)->first();
        if ($existingQuote) {
            return response()->json([
                'message' => 'Quote already exists for this lead.',
                'quote_id' => $existingQuote->id,
            ], 409);
        }

        $company = auth()->user()->company;
        $quoteNumber = Quote::generateQuoteNumber($company);

        // Calculate totals from lead products
        $subtotal = 0;
        foreach ($lead->products as $product) {
            $qty = $product->pivot->quantity ?? 1;
            $price = $product->pivot->price ?? ($product->mrp ?: $product->sale_price);
            $discount = $product->pivot->discount ?? 0;
            $unitPrice = $price - $discount;
            $subtotal += $unitPrice * $qty;
        }

        $quote = Quote::create([
            'company_id' => auth()->user()->company_id,
            'lead_id' => $lead->id,
            'client_id' => $lead->client ? $lead->client->id : null,
            'created_by_user_id' => auth()->id(),
            'assigned_to_user_id' => $lead->assigned_to_user_id ?? auth()->id(),
            'quote_no' => $quoteNumber,
            'date' => now()->toDateString(),
            'valid_till' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'discount' => 0,
            'gst_total' => 0,
            'grand_total' => $subtotal,
            'status' => 'draft',
            'notes' => $lead->notes,
        ]);

        // Copy lead products as QuoteItems
        foreach ($lead->products as $index => $product) {
            $qty = $product->pivot->quantity ?? 1;
            $price = $product->pivot->price ?? ($product->mrp ?: $product->sale_price);
            $discount = $product->pivot->discount ?? 0;
            $unitPrice = $price - $discount;
            $lineTotal = $unitPrice * $qty;

            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'description' => $product->description ?? '',
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'gst_percent' => 0,
                'gst_amount' => 0,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ]);
        }

        return response()->json([
            'message' => 'Quote created successfully from lead.',
            'quote_id' => $quote->id,
            'redirect' => route('admin.quotes.index'),
        ]);
    }

    public function convertToClient($id)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::findOrFail($id);

        // If lead already has a client, re-convert: add new product tasks to existing project
        if ($lead->client()->exists()) {
            $existingClient = $lead->client;

            // Link any unlinked quotes from this lead to the existing client and mark as accepted
            \App\Models\Quote::where('lead_id', $lead->id)->whereNull('client_id')->update(['client_id' => $existingClient->id, 'status' => 'accepted']);

            // Add new tasks to existing project (or create project if somehow missing)
            $newTasksCount = $this->createProjectsAndTasks($lead, $existingClient);

            $message = $newTasksCount > 0
                ? "New products added to existing project ({$newTasksCount} new tasks created)."
                : 'No new products to add. All products already exist in the project.';

            return response()->json([
                'message' => $message,
                'client_id' => $existingClient->id,
                'new_tasks' => $newTasksCount,
            ]);
        }

        // Check if a client with the same email or phone already exists
        $existingClient = Client::where(function ($query) use ($lead) {
            $query->where('phone', $lead->phone);
            if (!empty($lead->email)) {
                $query->orWhere('email', $lead->email);
            }
        })->first();

        if ($existingClient) {
            // Merge: Associate this lead with the existing client (if lead_id is empty, link it)
            if (empty($existingClient->lead_id)) {
                $existingClient->update(['lead_id' => $lead->id]);
            }
            $lead->update(['stage' => 'won']);

            // Link all quotes from this lead to the existing client and mark as accepted
            \App\Models\Quote::where('lead_id', $lead->id)->whereNull('client_id')->update(['client_id' => $existingClient->id, 'status' => 'accepted']);

            // Auto-create projects and tasks from quotes
            $newTasksCount = $this->createProjectsAndTasks($lead, $existingClient);

            return response()->json([
                'message' => 'An existing client was found. Lead has been successfully merged with the client.',
                'client_id' => $existingClient->id,
                'new_tasks' => $newTasksCount,
            ]);
        }

        $client = Client::create([
            'company_id' => $lead->company_id ?? auth()->user()->company_id,
            'lead_id' => $lead->id,
            'created_by_user_id' => auth()->id(),
            'assigned_to_user_id' => $lead->assigned_to_user_id ?? auth()->id(),
            'type' => 'business',
            'business_name' => $lead->name,
            'contact_name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'billing_address' => [
                'city' => $lead->city,
                'state' => $lead->state,
            ],
            'shipping_address' => [
                'city' => $lead->city,
                'state' => $lead->state,
            ],
            'status' => 'active',
            'notes' => $lead->notes,
        ]);

        $lead->update(['stage' => 'won']);

        // Link all quotes from this lead to the new client and mark as accepted
        \App\Models\Quote::where('lead_id', $lead->id)->whereNull('client_id')->update(['client_id' => $client->id, 'status' => 'accepted']);

        // Auto-create projects and tasks from quotes
        $newTasksCount = $this->createProjectsAndTasks($lead, $client);

        return response()->json([
            'message' => 'Lead converted to client successfully.',
            'client_id' => $client->id,
            'new_tasks' => $newTasksCount,
        ]);
    }

    /**
     * Auto-create ONE project per lead and tasks from all quote items.
     * If a product has a linked ServiceTemplate, create tasks from the template.
     * If no template, create a single task (backward compatible).
     * If a project already exists for this lead+client, only add NEW tasks (no duplicates).
     * Returns the count of newly created tasks.
     */
    private function createProjectsAndTasks(Lead $lead, Client $client): int
    {
        $quotes = Quote::where('lead_id', $lead->id)->with('items')->get();

        if ($quotes->isEmpty()) {
            return 0;
        }

        // Calculate total budget from all quotes
        $totalBudget = $quotes->sum('grand_total');

        // Check if a project already exists for this lead
        $project = Project::where('lead_id', $lead->id)->where('client_id', $client->id)->first();

        if ($project) {
            // Update the budget with the new total
            $project->update(['budget' => $totalBudget]);
        } else {
            // Create ONE project for this lead
            $project = Project::create([
                'company_id' => $client->company_id ?? auth()->user()->company_id,
                'client_id' => $client->id,
                'quote_id' => $quotes->first()->id,
                'lead_id' => $lead->id,
                'created_by_user_id' => auth()->id(),
                'assigned_to_user_id' => $lead->assigned_to_user_id ?? auth()->id(),
                'name' => $client->display_name . ' - Project',
                'status' => 'pending',
                'start_date' => now()->toDateString(),
                'budget' => $totalBudget,
            ]);
        }

        // Get existing task titles in this project to avoid duplicates
        $existingTaskTitles = Task::where('project_id', $project->id)
            ->pluck('title')
            ->map(fn($t) => strtolower(trim($t)))
            ->toArray();

        // Get existing purchase product IDs to avoid duplicating purchases
        $existingPurchaseProductIds = \App\Models\Purchase::where('project_id', $project->id)
            ->pluck('product_id')
            ->toArray();

        $newTasksCount = 0;
        $sortOrder = Task::where('project_id', $project->id)->max('sort_order') ?? 0;

        // Auto-create tasks from ALL quote items (products) under this ONE project
        foreach ($quotes as $quote) {
            foreach ($quote->items as $item) {
                // Check if product has a linked ServiceTemplate
                $template = null;
                if ($item->product_id) {
                    $template = ServiceTemplate::where('product_id', $item->product_id)
                        ->where('is_active', true)
                        ->first();
                }

                $taskTitle = $item->product_name;

                // Auto-create purchase if product has is_purchase_enabled
                if ($item->product_id && !in_array($item->product_id, $existingPurchaseProductIds)) {
                    $product = clone \App\Models\Product::find($item->product_id);
                    if ($product && $product->is_purchase_enabled) {
                        $company = clone \App\Models\Company::find($client->company_id ?? auth()->user()->company_id);
                        // Use custom purchase_amount if set, otherwise fall back to line item total
                        // purchase_amount is in paise, total_amount column is also in paise
                        $purchaseTotalPaise = ($item->purchase_amount > 0)
                            ? $item->purchase_amount
                            : ($item->unit_price * max(1, $item->qty));
                        \App\Models\Purchase::create([
                            'company_id' => $company->id,
                            'client_id' => $client->id,
                            'project_id' => $project->id,
                            'product_id' => $product->id,
                            'purchase_no' => \App\Models\Purchase::generatePurchaseNumber($company),
                            'date' => now()->toDateString(),
                            'total_amount' => $purchaseTotalPaise,
                            'paid_amount' => 0,
                            'status' => 'draft',
                            'notes' => 'Auto-generated during quote conversion from product/service: ' . $product->name,
                        ]);
                        $existingPurchaseProductIds[] = $product->id;
                    }
                }

                // Skip if this project already has a task for this product
                if (in_array(strtolower(trim($taskTitle)), $existingTaskTitles)) {
                    continue;
                }

                $sortOrder++;
                $task = Task::create([
                    'company_id' => $client->company_id ?? auth()->user()->company_id,
                    'project_id' => $project->id,
                    'assigned_to_user_id' => $lead->assigned_to_user_id ?? auth()->id(),
                    'created_by_user_id' => auth()->id(),
                    'entity_type' => 'project',
                    'entity_id' => $project->id,
                    'title' => $taskTitle,
                    'description' => ($item->description ? $item->description . ' | ' : '') . 'Qty: ' . $item->qty,
                    'priority' => 'medium',
                    'status' => 'todo',
                    'sort_order' => $sortOrder,
                ]);

                $newTasksCount++;
                $existingTaskTitles[] = strtolower(trim($taskTitle));

                // Auto-create micro tasks if template exists
                if ($template && !empty($template->getTaskSteps())) {
                    foreach ($template->getTaskSteps() as $stepIndex => $step) {
                        \App\Models\MicroTask::create([
                            'task_id' => $task->id,
                            'role_id' => $step['role_id'] ?? null,
                            'title' => $step['title'],
                            'status' => 'todo',
                            'sort_order' => $step['order'] ?? ($stepIndex + 1),
                        ]);
                    }
                }
            }
        }

        return $newTasksCount;
    }

    public function destroy($id)
    {
        if (!can('leads.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::findOrFail($id);

        // Non-global users can only delete their own leads
        if (!can('leads.global') && $lead->created_by_user_id != auth()->id() && $lead->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only delete your own leads.');
        }

        $lead->delete();

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead deleted successfully');
    }

    public function storeFollowup(Request $request, $id)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'next_follow_up_date' => 'nullable|date',
        ]);

        $followup = LeadFollowup::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
        ]);

        // Update the lead's next_follow_up_at
        $lead->update(['next_follow_up_at' => $validated['next_follow_up_date'] ?? null]);

        $followup->load('user');

        return response()->json([
            'success' => true,
            'followup' => $followup,
        ]);
    }

    public function updateFollowup(Request $request, $id, $followupId)
    {
        if (!can('leads.write')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = Lead::findOrFail($id);
        $followup = LeadFollowup::where('lead_id', $id)->findOrFail($followupId);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'next_follow_up_date' => 'nullable|date',
        ]);

        $followup->update([
            'message' => $validated['message'],
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? $followup->next_follow_up_date,
        ]);

        // Sync the lead's next_follow_up_at
        $lead->update(['next_follow_up_at' => $validated['next_follow_up_date'] ?? null]);

        $followup->load('user');

        return response()->json([
            'success' => true,
            'followup' => $followup,
        ]);
    }

    public function destroyFollowup($id, $followupId)
    {
        if (!can('leads.write')) {
            abort(403, 'Unauthorized action.');
        }

        $followup = LeadFollowup::where('lead_id', $id)->findOrFail($followupId);
        $followup->delete();

        return response()->json(['success' => true]);
    }
}
