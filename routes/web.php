<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\LeadsController;
use App\Http\Controllers\Web\ClientsController;
use App\Http\Controllers\Web\QuotesController;
use App\Http\Controllers\Web\PaymentsController;
use App\Http\Controllers\Web\FollowupsController;
use App\Http\Controllers\Web\ProjectsController;
use App\Http\Controllers\Web\TaskFollowupsController;
use App\Http\Controllers\Web\MicroTasksController;
use App\Http\Controllers\Web\ProductsController;
use App\Http\Controllers\Web\CategoriesController;
use App\Http\Controllers\Web\UsersController;
use App\Http\Controllers\Web\RolesController;
use App\Http\Controllers\Web\ServiceTemplatesController;

use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirect root to admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// WhatsApp Webhook (public — no auth, Evolution API calls this)
Route::post('/webhook/whatsapp/incoming/{instanceName}', [App\Http\Controllers\Web\WhatsappWebhookController::class, 'handleIncoming']);

// Admin Panel Routes (protected by auth)
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', function () {
        $stats = [
            'total_leads' => \App\Models\Lead::count(),
            'total_clients' => \App\Models\Client::count(),
            'total_quotes' => \App\Models\Quote::count(),
            'total_products' => \App\Models\Product::count(),
            'new_leads_today' => \App\Models\Lead::whereDate('created_at', today())->count(),
            'new_leads_7days' => \App\Models\Lead::where('created_at', '>=', now()->subDays(7))->count(),
            'open_leads' => \App\Models\Lead::whereNotIn('stage', ['won', 'lost'])->count(),
            'quotes_sent' => \App\Models\Quote::where('status', '!=', 'draft')->count(),
            'quotes_accepted' => \App\Models\Quote::where('status', 'accepted')->count(),
            'revenue' => \App\Models\Quote::where('status', 'accepted')->sum('grand_total'),
            'overdue_followups' => \App\Models\Lead::whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())
                ->whereNotIn('stage', ['won', 'lost'])->count(),
        ];

        // Leads by stage for chart
        $leadsByStage = \App\Models\Lead::selectRaw('stage, count(*) as count')
            ->groupBy('stage')->pluck('count', 'stage')->toArray();

        // Recent tasks
        $tasks = \App\Models\Task::with('assignedTo')->latest()->take(5)->get();

        // Recent activities
        $activities = \App\Models\Activity::with('createdBy')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats',
            'leadsByStage',
            'tasks',
            'activities'
        ));
    })->name('dashboard');

    // Sales Dashboard
    Route::get('/sales-dashboard', function () {
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Current month stats
        $newLeads = \App\Models\Lead::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)->count();

        $wonLeads = \App\Models\Lead::where('stage', 'won')
            ->whereMonth('updated_at', $currentMonth)
            ->whereYear('updated_at', $currentYear)->count();

        $totalOrderValue = \App\Models\Quote::whereNotNull('client_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('grand_total') / 100;

        $totalPaymentReceived = \App\Models\QuotePayment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount') / 100;

        // Monthly order values for current year (Jan-Dec)
        $monthlyOrders = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyOrders[] = \App\Models\Quote::whereNotNull('client_id')
                ->whereMonth('created_at', $m)
                ->whereYear('created_at', $currentYear)
                ->sum('grand_total') / 100;
        }

        // Lead source data (all time counts + values)
        $leadSources = \App\Models\Lead::selectRaw('source, count(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return view('admin.sales-dashboard', compact(
            'newLeads',
            'wonLeads',
            'totalOrderValue',
            'totalPaymentReceived',
            'monthlyOrders',
            'leadSources',
            'currentYear'
        ));
    })->name('sales-dashboard');

    // Production Dashboard
    Route::get('/production-dashboard', function () {
        // Production Stats
        $totalProjects = \App\Models\Project::count();
        $completedProjects = \App\Models\Project::where('status', 'completed')->count();
        $projectsByStatus = \App\Models\Project::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        $totalTasks = \App\Models\Task::count();
        $completedTasks = \App\Models\Task::where('status', 'done')->count();
        $tasksByStatus = \App\Models\Task::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return view('admin.production-dashboard', compact(
            'totalProjects',
            'completedProjects',
            'projectsByStatus',
            'totalTasks',
            'completedTasks',
            'tasksByStatus'
        ));
    })->name('production-dashboard');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Web\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Http\Controllers\Web\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Web\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Web\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // WhatsApp Templates
    Route::resource('whatsapp-templates', App\Http\Controllers\Web\WhatsappTemplateController::class)->except(['create', 'show', 'edit']);

    // WhatsApp Extension
    Route::get('/whatsapp-extension', [App\Http\Controllers\Web\WhatsappConnectController::class, 'extension'])->name('whatsapp.extension');

    // WhatsApp Campaigns
    Route::get('whatsapp-campaigns', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'index'])->name('whatsapp-campaigns.index');
    Route::get('whatsapp-campaigns/create', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'create'])->name('whatsapp-campaigns.create');
    Route::post('whatsapp-campaigns', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'store'])->name('whatsapp-campaigns.store');
    Route::post('whatsapp-campaigns/preview', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'preview'])->name('whatsapp-campaigns.preview');
    Route::get('whatsapp-campaigns/{campaign}', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'show'])->name('whatsapp-campaigns.show');
    Route::delete('whatsapp-campaigns/{campaign}', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'destroy'])->name('whatsapp-campaigns.destroy');

    // WhatsApp Auto-Reply
    Route::get('whatsapp-auto-reply', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'index'])->name('whatsapp-auto-reply.index');
    Route::get('whatsapp-auto-reply/create', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'create'])->name('whatsapp-auto-reply.create');
    Route::post('whatsapp-auto-reply', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'store'])->name('whatsapp-auto-reply.store');
    Route::get('whatsapp-auto-reply/{id}/edit', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'edit'])->name('whatsapp-auto-reply.edit');
    Route::put('whatsapp-auto-reply/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'update'])->name('whatsapp-auto-reply.update');
    Route::delete('whatsapp-auto-reply/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'destroy'])->name('whatsapp-auto-reply.destroy');
    Route::post('whatsapp-auto-reply/{id}/toggle', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'toggle'])->name('whatsapp-auto-reply.toggle');
    Route::post('whatsapp-auto-reply/{id}/duplicate', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'duplicate'])->name('whatsapp-auto-reply.duplicate');
    Route::post('whatsapp-auto-reply/pause-all', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'pauseAll'])->name('whatsapp-auto-reply.pause-all');
    Route::get('whatsapp-auto-reply-analytics', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'analytics'])->name('whatsapp-auto-reply.analytics');
    Route::get('whatsapp-auto-reply-blacklist', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'blacklist'])->name('whatsapp-auto-reply.blacklist');
    Route::post('whatsapp-auto-reply-blacklist', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'addToBlacklist'])->name('whatsapp-auto-reply.blacklist.store');
    Route::delete('whatsapp-auto-reply-blacklist/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'removeFromBlacklist'])->name('whatsapp-auto-reply.blacklist.destroy');

    // Leads
    Route::get('/leads/whatsapp-lookup', [LeadsController::class, 'whatsappLookup'])->name('leads.whatsapp-lookup');
    Route::get('/leads', [LeadsController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadsController::class, 'store'])->name('leads.store');
    Route::get('/leads/{id}/edit', [LeadsController::class, 'edit'])->name('leads.edit');
    Route::get('/leads/{id}', [LeadsController::class, 'show'])->name('leads.show');
    Route::put('/leads/{id}', [LeadsController::class, 'update'])->name('leads.update');
    Route::patch('/leads/{id}/stage', [LeadsController::class, 'updateStage'])->name('leads.updateStage');
    Route::delete('/leads/{id}', [LeadsController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{id}/convert-to-quote', [LeadsController::class, 'convertToQuote'])->name('leads.convert-to-quote');
    Route::post('/leads/{id}/convert-to-client', [LeadsController::class, 'convertToClient'])->name('leads.convert-to-client');
    Route::post('/leads/{id}/followups', [LeadsController::class, 'storeFollowup'])->name('leads.followups.store');
    Route::patch('/leads/{id}/followups/{followupId}', [LeadsController::class, 'updateFollowup'])->name('leads.followups.update');
    Route::delete('/leads/{id}/followups/{followupId}', [LeadsController::class, 'destroyFollowup'])->name('leads.followups.destroy');

    // Clients
    Route::get('/clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [ClientsController::class, 'show'])->name('clients.show');
    Route::post('/clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::put('/clients/{id}', [ClientsController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{id}', [ClientsController::class, 'destroy'])->name('clients.destroy');

    // Quotes
    Route::get('/quotes', [QuotesController::class, 'index'])->name('quotes.index');
    Route::post('/quotes', [QuotesController::class, 'store'])->name('quotes.store');
    Route::get('/quotes/{id}/edit', [QuotesController::class, 'edit'])->name('quotes.edit');
    Route::get('/quotes/{id}', [QuotesController::class, 'show'])->name('quotes.show');
    Route::get('/quotes/{id}/download', [QuotesController::class, 'download'])->name('quotes.download');
    Route::put('/quotes/{id}', [QuotesController::class, 'update'])->name('quotes.update');
    Route::delete('/quotes/{id}', [QuotesController::class, 'destroy'])->name('quotes.destroy');
    Route::post('/quotes/{id}/convert', [QuotesController::class, 'convert'])->name('quotes.convert');

    // Invoices
    Route::get('/invoices', [\App\Http\Controllers\Web\InvoicesController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [\App\Http\Controllers\Web\InvoicesController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}/edit', [\App\Http\Controllers\Web\InvoicesController::class, 'edit'])->name('invoices.edit');
    Route::get('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{id}/download', [\App\Http\Controllers\Web\InvoicesController::class, 'download'])->name('invoices.download');
    Route::put('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'destroy'])->name('invoices.destroy');

    // Payments
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentsController::class, 'store'])->name('payments.store');
    Route::put('/payments/{id}', [PaymentsController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{id}', [PaymentsController::class, 'destroy'])->name('payments.destroy');

    // Follow-ups
    Route::get('/followups', [FollowupsController::class, 'index'])->name('followups.index');

    // Projects
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
    Route::get('/projects/{id}', [ProjectsController::class, 'show'])->name('projects.show');
    Route::put('/projects/{id}', [ProjectsController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{id}', [ProjectsController::class, 'destroy'])->name('projects.destroy');
    Route::put('/projects/{projectId}/tasks/{taskId}', [ProjectsController::class, 'updateTask'])->name('projects.tasks.update');
    Route::delete('/projects/{projectId}/tasks/{taskId}', [ProjectsController::class, 'destroyTask'])->name('projects.tasks.destroy');
    Route::post('/projects/{projectId}/tasks/{taskId}/activities', [ProjectsController::class, 'storeTaskActivity'])->name('projects.tasks.activities.store');

    // Task Micro Tasks
    Route::post('/projects/{projectId}/tasks/{taskId}/micro-tasks', [ProjectsController::class, 'storeMicroTask'])->name('projects.tasks.micro-tasks.store');
    Route::put('/projects/{projectId}/tasks/{taskId}/micro-tasks/{microTaskId}', [ProjectsController::class, 'updateMicroTask'])->name('projects.tasks.micro-tasks.update');
    Route::delete('/projects/{projectId}/tasks/{taskId}/micro-tasks/{microTaskId}', [ProjectsController::class, 'destroyMicroTask'])->name('projects.tasks.micro-tasks.destroy');

    // Task Follow-ups
    Route::get('/task-followups', [TaskFollowupsController::class, 'index'])->name('task-followups.index');

    // Micro Tasks Board
    Route::get('/micro-tasks', [MicroTasksController::class, 'index'])->name('micro-tasks.index');
    Route::get('/micro-tasks/{id}', [MicroTasksController::class, 'show'])->name('micro-tasks.show');
    Route::put('/micro-tasks/{id}', [MicroTasksController::class, 'update'])->name('micro-tasks.update');
    Route::patch('/micro-tasks/{id}/status', [MicroTasksController::class, 'updateStatus'])->name('micro-tasks.updateStatus');
    Route::delete('/micro-tasks/{id}', [MicroTasksController::class, 'destroy'])->name('micro-tasks.destroy');

    // Service Templates
    Route::get('/service-templates', [ServiceTemplatesController::class, 'index'])->name('service-templates.index');
    Route::post('/service-templates', [ServiceTemplatesController::class, 'store'])->name('service-templates.store');
    Route::put('/service-templates/{id}', [ServiceTemplatesController::class, 'update'])->name('service-templates.update');
    Route::delete('/service-templates/{id}', [ServiceTemplatesController::class, 'destroy'])->name('service-templates.destroy');

    // Products
    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductsController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductsController::class, 'destroy'])->name('products.destroy');

    // Categories
    Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

    // Vendors
    Route::get('/vendors', [App\Http\Controllers\Web\VendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [App\Http\Controllers\Web\VendorController::class, 'store'])->name('vendors.store');
    Route::put('/vendors/{id}', [App\Http\Controllers\Web\VendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{id}', [App\Http\Controllers\Web\VendorController::class, 'destroy'])->name('vendors.destroy');
    // Vendor Custom Fields (AJAX)
    Route::get('/vendors/{id}/custom-fields', [App\Http\Controllers\Web\VendorController::class, 'getCustomFields'])->name('vendors.custom-fields.index');
    Route::post('/vendors/custom-fields', [App\Http\Controllers\Web\VendorController::class, 'storeCustomField'])->name('vendors.custom-fields.store');
    Route::delete('/vendors/custom-fields/{id}', [App\Http\Controllers\Web\VendorController::class, 'deleteCustomField'])->name('vendors.custom-fields.destroy');

    // Purchases
    Route::get('/purchases', [App\Http\Controllers\Web\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/search', [App\Http\Controllers\Web\PurchaseController::class, 'search'])->name('purchases.search');
    Route::get('/purchases/vendor-fields/{vendorId}', [App\Http\Controllers\Web\PurchaseController::class, 'getVendorCustomFields'])->name('purchases.vendor-fields');
    Route::post('/purchases', [App\Http\Controllers\Web\PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'show'])->name('purchases.show');
    Route::put('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'destroy'])->name('purchases.destroy');
    Route::post('/purchases/{id}/payment', [App\Http\Controllers\Web\PurchaseController::class, 'addPayment'])->name('purchases.payment');

    // Purchase Payments
    Route::get('/purchase-payments', [App\Http\Controllers\Web\PurchasePaymentController::class, 'index'])->name('purchase-payments.index');
    Route::put('/purchase-payments/{id}', [App\Http\Controllers\Web\PurchasePaymentController::class, 'update'])->name('purchase-payments.update');
    Route::delete('/purchase-payments/{id}', [App\Http\Controllers\Web\PurchasePaymentController::class, 'destroy'])->name('purchase-payments.destroy');

    // Users
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');

    // Roles
    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::put('/roles/{id}', [RolesController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');

    Route::get('/tasks', function (\Illuminate\Http\Request $request) {
        if (!can('tasks.read'))
            abort(403, 'Unauthorized action.');

        $query = \App\Models\Task::with(['microTasks', 'activities.user', 'assignedTo', 'leadEntity', 'clientEntity', 'clientEntity.lead', 'project.client', 'project.client.lead', 'project.lead']);

        // Global permission filter
        if (!can('tasks.global')) {
            $query->where(function ($q) {
                $q->where('assigned_to_user_id', auth()->id())
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Search (title, client name, contact number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhereHas('clientEntity', function ($cq) use ($search) {
                        $cq->where('business_name', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leadEntity', function ($lq) use ($search) {
                        $lq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($cq) use ($search) {
                                $cq->where('business_name', 'like', "%{$search}%")
                                    ->orWhere('contact_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('lead', function ($lq) use ($search) {
                                $lq->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Start Date filter
        if ($request->filled('start_date')) {
            $query->whereDate('due_at', '>=', $request->start_date);
        }

        // Due Date filter
        if ($request->filled('due_date')) {
            $query->whereDate('due_at', '<=', $request->due_date);
        }

        // Assigned To filter
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to_user_id', $request->assigned_to);
        }

        // Get all tasks
        $tasks = $query->latest()->get();

        // AJAX request — return JSON for live search
        if ($request->ajax()) {
            $data = $tasks->map(function ($t) {
                // Build client name
                $clientName = null;
                if ($t->entity_type === 'client' && $t->clientEntity) {
                    $clientName = $t->clientEntity->business_name ?: $t->clientEntity->contact_name;
                } elseif ($t->entity_type === 'lead' && $t->leadEntity) {
                    $clientName = $t->leadEntity->name . ' (Lead)';
                } elseif ($t->project_id && $t->project) {
                    if ($t->project->client) {
                        $clientName = $t->project->client->business_name ?: $t->project->client->contact_name;
                    } elseif ($t->project->lead) {
                        $clientName = $t->project->lead->name . ' (Lead)';
                    }
                }
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'contact_number' => $t->contact_number,
                    'due_at' => $t->due_at ? $t->due_at->format('d M Y') : null,
                    'is_overdue' => $t->due_at ? $t->isOverdue() : false,
                    'assigned_to' => $t->assignedTo->name ?? 'Unassigned',
                    'assigned_initials' => isset($t->assignedTo) ? strtoupper(substr($t->assignedTo->name, 0, 2)) : '?',
                    'entity_type' => $t->entity_type,
                    'client_name' => $clientName,
                    'description' => $t->description,
                    'short_description' => $t->description ? \Str::limit(str_replace("\n", ' ', $t->description), 55) : null,
                    'micro_tasks' => $t->microTasks->map(function ($mt) {
                        return [
                            'id' => $mt->id,
                            'title' => $mt->title,
                            'status' => $mt->status,
                            'follow_up_date' => $mt->follow_up_date ? \Carbon\Carbon::parse($mt->follow_up_date)->format('Y-m-d') : null,
                            'sort_order' => $mt->sort_order,
                        ];
                    })->toArray(),
                    'activities' => $t->activities->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'type' => $a->type,
                            'message' => $a->message,
                            'created_at' => \Carbon\Carbon::parse($a->created_at)->toIso8601String(),
                            'user' => $a->user ? ['name' => $a->user->name] : null,
                        ];
                    })->toArray(),
                ];
            });
            return response()->json([
                'tasks' => $data,
                'total' => $tasks->count(),
            ]);
        }

        // Users list: global sees all users, non-global sees only self
        if (can('tasks.global') || auth()->user()->isAdmin()) {
            $users = \App\Models\User::where('status', 'active')->withModulePermission('tasks')->where('id', '!=', 1)->orderBy('name')->get();
        } else {
            $users = collect([auth()->user()]);
        }

        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();

        $clients = \App\Models\Client::select('id', 'business_name', 'contact_name')->orderBy('business_name')->get();

        $globalTaskUsers = \App\Models\User::where('status', 'active')
            ->whereHas('role', function($q) {
                $q->where('permissions', 'LIKE', '%"tasks.global"%')
                  ->orWhere('permissions', 'LIKE', '%"all"%')
                  ->orWhere('name', 'LIKE', '%admin%');
            })->orderBy('name')->get();

        return view('admin.tasks.index', compact('tasks', 'users', 'globalTaskUsers', 'clients', 'dynamicStatuses'));
    })->name('tasks.index');

    Route::post('/tasks', function (\Illuminate\Http\Request $request) {
        if (!can('tasks.write'))
            abort(403, 'Unauthorized action.');

        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
            'status' => 'nullable|in:' . implode(',', $dynamicStatuses),
            'priority' => 'required|in:low,medium,high',
            'due_at' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        $task = new \App\Models\Task($validated);
        $task->contact_number = $validated['contact_number'] ?? null;
        $task->status = $validated['status'] ?? 'todo';
        $task->company_id = auth()->user()->company_id ?? 1;
        $task->created_by_user_id = auth()->id();

        if (!empty($validated['assigned_to_user_id'])) {
            $task->assigned_to_user_id = $validated['assigned_to_user_id'];
        }

        if (!empty($validated['client_id'])) {
            $task->entity_type = 'client';
            $task->entity_id = $validated['client_id'];
        }

        $task->save();

        // Send assignment notification
        if (!empty($task->assigned_to_user_id) && $task->assigned_to_user_id != auth()->id()) {
            $assignedUser = \App\Models\User::find($task->assigned_to_user_id);
            if ($assignedUser) {
                $assignedUser->notify(new \App\Notifications\AssignedNotification(
                    'task',
                    $task->id,
                    $task->title,
                    auth()->user()->name
                ));
            }
        }

        return back()->with('success', 'Task created successfully');
    })->name('tasks.store');

    Route::patch('/tasks/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        if (!can('tasks.write'))
            abort(403, 'Unauthorized action.');

        $task = \App\Models\Task::findOrFail($id);
        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', $dynamicStatuses),
        ]);

        if ($validated['status'] === 'done' && $task->status !== 'done') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'done') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);
        return response()->json(['success' => true, 'message' => 'Task status updated']);
    })->name('tasks.updateStatus');

    Route::put('/tasks/{id}', function (\Illuminate\Http\Request $request, $id) {
        if (!can('tasks.write'))
            abort(403, 'Unauthorized action.');

        $task = \App\Models\Task::findOrFail($id);
        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'additional_description' => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
            'status' => 'required|in:' . implode(',', $dynamicStatuses),
            'priority' => 'required|in:low,medium,high',
            'due_at' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            if (!empty($validated['additional_description'])) {
                $validated['description'] = $task->description . "\n\n" . $validated['additional_description'];
            } else {
                $validated['description'] = $task->description;
            }
        } else {
            if (!empty($validated['additional_description'])) {
                $validated['description'] = ($validated['description'] ?? '') . "\n\n" . $validated['additional_description'];
            }
        }
        unset($validated['additional_description']);

        if ($validated['status'] === 'done' && $task->status !== 'done') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'done') {
            $validated['completed_at'] = null;
        }

        if (array_key_exists('client_id', $validated)) {
            if (!empty($validated['client_id'])) {
                $validated['entity_type'] = 'client';
                $validated['entity_id'] = $validated['client_id'];
            } else {
                if ($task->entity_type === 'client') {
                    $validated['entity_type'] = null;
                    $validated['entity_id'] = null;
                }
            }
            unset($validated['client_id']);
        }

        $task->update($validated);

        // Send assignment notification if assignee changed
        if (!empty($task->assigned_to_user_id) && $task->wasChanged('assigned_to_user_id') && $task->assigned_to_user_id != auth()->id()) {
            $assignedUser = \App\Models\User::find($task->assigned_to_user_id);
            if ($assignedUser) {
                $assignedUser->notify(new \App\Notifications\AssignedNotification(
                    'task',
                    $task->id,
                    $task->title,
                    auth()->user()->name
                ));
            }
        }

        return back()->with('success', 'Task updated successfully');
    })->name('tasks.update');

    Route::delete('/tasks/{id}', function ($id) {
        if (!can('tasks.delete'))
            abort(403, 'Unauthorized action.');

        $task = \App\Models\Task::findOrFail($id);
        $task->forceDelete();

        return back()->with('success', 'Task deleted successfully');
    })->name('tasks.destroy');

    Route::post('/tasks/{taskId}/activities', function (\Illuminate\Http\Request $request, $taskId) {
        if (!can('tasks.write'))
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        $task = \App\Models\Task::findOrFail($taskId);
        $validated = $request->validate([
            'type' => 'required|in:note,client_reply,revision',
            'message' => 'required|string|max:2000',
            'notified_user_id' => 'nullable|exists:users,id',
        ]);
        $activity = \App\Models\TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'message' => $validated['message'],
        ]);

        if (!empty($validated['notified_user_id'])) {
            $user = \App\Models\User::find($validated['notified_user_id']);
            if ($user && $user->id !== auth()->id()) {
                $user->notify(new \App\Notifications\TaskMentionNotification(
                    $task->id,
                    $task->title,
                    auth()->user()->name,
                    $validated['message']
                ));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Activity added successfully',
            'activity' => $activity->load('user')
        ]);
    })->name('tasks.activities.store');

    Route::post('/tasks/{taskId}/micro-tasks', function (\Illuminate\Http\Request $request, $taskId) {
        if (!can('tasks.write'))
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        $task = \App\Models\Task::findOrFail($taskId);
        $validated = $request->validate(['title' => 'required|string|max:255']);
        $maxOrder = \App\Models\MicroTask::where('task_id', $task->id)->max('sort_order') ?? 0;
        $microTask = \App\Models\MicroTask::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'status' => 'todo',
            'sort_order' => $maxOrder + 1,
        ]);
        return response()->json(['success' => true, 'micro_task' => $microTask]);
    })->name('tasks.micro-tasks.store');

    Route::put('/tasks/{taskId}/micro-tasks/{microTaskId}', function (\Illuminate\Http\Request $request, $taskId, $microTaskId) {
        if (!can('tasks.write'))
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        $microTask = \App\Models\MicroTask::where('task_id', $taskId)->findOrFail($microTaskId);
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:todo,doing,done',
            'follow_up_date' => 'nullable|date',
            'sort_order' => 'sometimes|integer',
        ]);
        $microTask->update($validated);
        return response()->json(['success' => true]);
    })->name('tasks.micro-tasks.update');

    Route::delete('/tasks/{taskId}/micro-tasks/{microTaskId}', function ($taskId, $microTaskId) {
        if (!can('tasks.delete'))
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        $microTask = \App\Models\MicroTask::where('task_id', $taskId)->findOrFail($microTaskId);
        $microTask->delete();
        return response()->json(['success' => true]);
    })->name('tasks.micro-tasks.destroy');

    // Activities
    Route::get('/activities', function (\Illuminate\Http\Request $request) {
        if (!can('activities.read'))
            abort(403, 'Unauthorized action.');

        // ─── Build Activity query ───
        $activityQuery = \App\Models\Activity::with('createdBy')
            ->select('id', 'company_id', 'created_by_user_id', 'entity_type', 'entity_id', 'type', 'subject', 'summary', 'created_at')
            ->selectRaw("'activity' as source");

        // ─── Build TaskActivity query ───
        $taskActivityQuery = \App\Models\TaskActivity::with(['user', 'task'])
            ->select('id', 'task_id', 'user_id', 'type', 'message', 'old_value', 'new_value', 'created_at');

        // ─── Apply filters to Activity ───
        if ($request->filled('search')) {
            $s = $request->search;
            $activityQuery->where(function ($q) use ($s) {
                $q->where('subject', 'like', "%{$s}%")
                    ->orWhere('summary', 'like', "%{$s}%");
            });
        }
        if ($request->filled('user_id')) {
            $activityQuery->where('created_by_user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $activityQuery->where('type', $request->type);
        }
        if ($request->filled('entity_type') && $request->entity_type !== 'task') {
            $activityQuery->where('entity_type', $request->entity_type);
        }
        if ($request->filled('date_from')) {
            $activityQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $activityQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // ─── Apply filters to TaskActivity ───
        if ($request->filled('search')) {
            $s = $request->search;
            $taskActivityQuery->where('message', 'like', "%{$s}%");
        }
        if ($request->filled('user_id')) {
            $taskActivityQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $taskActivityQuery->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $taskActivityQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $taskActivityQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // If entity_type is set and NOT task, skip task activities
        $skipTasks = $request->filled('entity_type') && $request->entity_type !== 'task';
        // If entity_type is 'task', skip regular activities
        $skipActivities = $request->filled('entity_type') && $request->entity_type === 'task';

        // ─── Fetch & merge ───
        $allActivities = collect();

        if (!$skipActivities) {
            $regularActivities = $activityQuery->latest()->take(200)->get()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'source' => 'activity',
                    'type' => $a->type,
                    'subject' => $a->subject,
                    'summary' => $a->summary ?? $a->subject,
                    'entity_type' => $a->entity_type,
                    'entity_id' => $a->entity_id,
                    'user_name' => $a->createdBy->name ?? 'System',
                    'user_initials' => strtoupper(substr($a->createdBy->name ?? 'S', 0, 2)),
                    'user_id' => $a->created_by_user_id,
                    'created_at' => $a->created_at->toISOString(),
                    'created_at_human' => $a->created_at->diffForHumans(),
                    'created_at_formatted' => $a->created_at->format('d M Y, h:i A'),
                ];
            });
            $allActivities = $allActivities->merge($regularActivities);
        }

        if (!$skipTasks) {
            $taskActivities = $taskActivityQuery->latest()->take(200)->get()->map(function ($ta) {
                $taskName = $ta->task->title ?? 'Task';
                $taskId = $ta->task_id;
                $projectId = $ta->task->project_id ?? null;
                return [
                    'id' => $ta->id,
                    'source' => 'task_activity',
                    'type' => $ta->type,
                    'subject' => $ta->getTypeLabel(),
                    'summary' => $ta->message ?? ($ta->getTypeLabel() . ' on ' . $taskName),
                    'entity_type' => 'task',
                    'entity_id' => $taskId,
                    'entity_name' => $taskName,
                    'project_id' => $projectId,
                    'old_value' => $ta->old_value,
                    'new_value' => $ta->new_value,
                    'user_name' => $ta->user->name ?? 'System',
                    'user_initials' => strtoupper(substr($ta->user->name ?? 'S', 0, 2)),
                    'user_id' => $ta->user_id,
                    'created_at' => $ta->created_at->toISOString(),
                    'created_at_human' => $ta->created_at->diffForHumans(),
                    'created_at_formatted' => $ta->created_at->format('d M Y, h:i A'),
                ];
            });
            $allActivities = $allActivities->merge($taskActivities);
        }

        // Sort merged by created_at desc
        $allActivities = $allActivities->sortByDesc('created_at')->values();

        // ─── Stats ───
        $totalActivities = \App\Models\Activity::count() + \App\Models\TaskActivity::count();
        $todayActivities = \App\Models\Activity::whereDate('created_at', today())->count()
            + \App\Models\TaskActivity::whereDate('created_at', today())->count();

        // Most active user
        $topUserActivity = \App\Models\Activity::selectRaw('created_by_user_id, count(*) as cnt')
            ->groupBy('created_by_user_id')
            ->orderByDesc('cnt')
            ->first();
        $topUserTaskAct = \App\Models\TaskActivity::selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->first();

        $mostActiveUser = 'N/A';
        $mostActiveCount = 0;
        if ($topUserActivity && $topUserActivity->cnt >= ($topUserTaskAct->cnt ?? 0)) {
            $u = \App\Models\User::find($topUserActivity->created_by_user_id);
            $mostActiveUser = $u->name ?? 'N/A';
            $mostActiveCount = $topUserActivity->cnt;
        } elseif ($topUserTaskAct) {
            $u = \App\Models\User::find($topUserTaskAct->user_id);
            $mostActiveUser = $u->name ?? 'N/A';
            $mostActiveCount = $topUserTaskAct->cnt;
        }

        // Top entity
        $topEntity = \App\Models\Activity::selectRaw('entity_type, count(*) as cnt')
            ->groupBy('entity_type')
            ->orderByDesc('cnt')
            ->first();
        $topEntityLabel = $topEntity ? ucfirst($topEntity->entity_type) . ' (' . $topEntity->cnt . ')' : 'N/A';

        // ─── Users for filter dropdown ───
        $users = \App\Models\User::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        // ─── AJAX response ───
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'activities' => $allActivities,
                'total' => $allActivities->count(),
                'stats' => [
                    'total' => $totalActivities,
                    'today' => $todayActivities,
                    'most_active_user' => $mostActiveUser,
                    'most_active_count' => $mostActiveCount,
                    'top_entity' => $topEntityLabel,
                ],
            ]);
        }

        return view('admin.activities.index', compact(
            'allActivities',
            'users',
            'totalActivities',
            'todayActivities',
            'mostActiveUser',
            'mostActiveCount',
            'topEntityLabel'
        ));
    })->name('activities.index');

    // Backups
    Route::get('/backups', [\App\Http\Controllers\Web\BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/run', [\App\Http\Controllers\Web\BackupController::class, 'runBackup'])->name('backups.run');
    Route::get('/backups/download/{fileName}', [\App\Http\Controllers\Web\BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/import', [\App\Http\Controllers\Web\BackupController::class, 'import'])->name('backups.import');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Web\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/leads', [\App\Http\Controllers\Web\ReportsController::class, 'exportLeads'])->name('reports.export.leads');
    Route::get('/reports/export/quotes', [\App\Http\Controllers\Web\ReportsController::class, 'exportQuotes'])->name('reports.export.quotes');
    Route::get('/reports/export/payments', [\App\Http\Controllers\Web\ReportsController::class, 'exportPayments'])->name('reports.export.payments');
    Route::get('/reports/export/projects', [\App\Http\Controllers\Web\ReportsController::class, 'exportProjects'])->name('reports.export.projects');
    Route::get('/reports/export/tasks', [\App\Http\Controllers\Web\ReportsController::class, 'exportTasks'])->name('reports.export.tasks');
    Route::get('/reports/export/team', [\App\Http\Controllers\Web\ReportsController::class, 'exportTeam'])->name('reports.export.team');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/column-visibility', [SettingsController::class, 'saveColumnVisibility'])->name('settings.column-visibility.save');
    Route::get('/settings/column-visibility/{module}', [SettingsController::class, 'getColumnVisibility'])->name('settings.column-visibility.get');
    Route::post('/settings/taxes', [SettingsController::class, 'saveTaxes'])->name('settings.taxes.save');
    Route::post('/settings/lead-stages', [SettingsController::class, 'saveLeadStages'])->name('settings.lead-stages.save');
    Route::post('/settings/lead-sources', [SettingsController::class, 'saveLeadSources'])->name('settings.lead-sources.save');
    Route::post('/settings/task-statuses', [SettingsController::class, 'saveTaskStatuses'])->name('settings.task-statuses.save');
    Route::post('/settings/payment-types', [SettingsController::class, 'savePaymentTypes'])->name('settings.payment-types.save');
    Route::post('/settings/whatsapp-api', [SettingsController::class, 'saveWhatsappApi'])->name('settings.whatsapp-api.save');

    // WhatsApp Connect
    Route::get('/whatsapp-connect', [App\Http\Controllers\Web\WhatsappConnectController::class, 'index'])->name('whatsapp-connect.index');
    Route::get('/whatsapp-connect/qr', [App\Http\Controllers\Web\WhatsappConnectController::class, 'getQrCode'])->name('whatsapp-connect.qr');
    Route::get('/whatsapp-connect/status', [App\Http\Controllers\Web\WhatsappConnectController::class, 'getStatus'])->name('whatsapp-connect.status');
    Route::post('/whatsapp-connect/disconnect', [App\Http\Controllers\Web\WhatsappConnectController::class, 'disconnect'])->name('whatsapp-connect.disconnect');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
