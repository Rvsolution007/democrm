<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuotePayment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Activity;
use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    /**
     * Main reports page with all tab data.
     */
    public function index(Request $request)
    {
        if (!can('reports.read')) {
            abort(403, 'Unauthorized action.');
        }

        $now = now();
        $currentYear = $now->year;
        // Financial year: April to March
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $fyStart = "$fyStartYear-04-01";
        $fyEnd = ($fyStartYear + 1) . "-03-31";

        // ===================== OVERVIEW TAB =====================
        $overview = [
            'total_leads' => Lead::count(),
            'total_clients' => Client::count(),
            'total_quotes' => Quote::count(),
            'total_revenue' => Quote::where('status', 'accepted')->sum('grand_total') / 100,
            'total_payments' => QuotePayment::sum('amount') / 100,
            'open_projects' => Project::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'pending_tasks' => Task::where('status', '!=', 'done')->count(),
            'conversion_rate' => $this->getConversionRate(),
        ];

        // Monthly Revenue Trend (last 12 months)
        $monthlyRevenue = [];
        $monthLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabels[] = $date->format('M Y');
            $monthlyRevenue[] = Quote::where('status', 'accepted')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('grand_total') / 100;
        }

        // Lead Funnel (by stage)
        $leadStages = Lead::getDynamicStages();
        $leadsByStage = Lead::selectRaw('stage, count(*) as count')
            ->groupBy('stage')->pluck('count', 'stage')->toArray();

        // ===================== LEADS TAB =====================
        $totalLeads = Lead::count();
        $wonLeads = Lead::where('stage', 'won')->count();
        $lostLeads = Lead::where('stage', 'lost')->count();
        $openLeads = Lead::whereNotIn('stage', ['won', 'lost'])->count();
        $leadsConversion = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0;

        // Leads by Source
        $leadSources = Lead::selectRaw('source, count(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')->pluck('count', 'source')->toArray();

        // Daily lead trend (last 30 days)
        $leadDailyTrend = Lead::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')->orderBy('date')->pluck('count', 'date')->toArray();

        // Lead aging (avg days in pipeline for open leads)
        $leadAging = Lead::whereNotIn('stage', ['won', 'lost'])
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days');
        $leadAging = round($leadAging ?? 0, 1);

        $leads = compact('totalLeads', 'wonLeads', 'lostLeads', 'openLeads', 'leadsConversion', 'leadSources', 'leadDailyTrend', 'leadAging');

        // ===================== QUOTES & REVENUE TAB =====================
        $quotesTotal = Quote::count();
        $quotesByStatus = Quote::selectRaw('status, count(*) as count, SUM(grand_total) as total')
            ->groupBy('status')->get()->keyBy('status');

        // Monthly Revenue Trend (current FY)
        $fyMonthlyRevenue = [];
        $fyMonthLabels = [];
        for ($m = 4; $m <= 15; $m++) {
            $month = $m <= 12 ? $m : $m - 12;
            $year = $m <= 12 ? $fyStartYear : $fyStartYear + 1;
            $date = \Carbon\Carbon::createFromDate($year, $month, 1);
            $fyMonthLabels[] = $date->format('M Y');
            $fyMonthlyRevenue[] = Quote::where('status', 'accepted')
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->sum('grand_total') / 100;
        }

        // Top 10 Quotes by value
        $topQuotes = Quote::with(['client', 'lead'])
            ->orderByDesc('grand_total')
            ->take(10)
            ->get();

        $quotes = compact('quotesTotal', 'quotesByStatus', 'fyMonthlyRevenue', 'fyMonthLabels', 'topQuotes');

        // ===================== PAYMENTS TAB =====================
        $paymentsTotal = QuotePayment::sum('amount') / 100;
        $paymentsByType = QuotePayment::selectRaw('payment_type, count(*) as count, SUM(amount) as total')
            ->groupBy('payment_type')->get()->keyBy('payment_type');

        // Monthly collection trend (last 12 months)
        $monthlyPayments = [];
        $paymentMonthLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $paymentMonthLabels[] = $date->format('M Y');
            $monthlyPayments[] = QuotePayment::whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount') / 100;
        }

        // Recent payments
        $recentPayments = QuotePayment::with(['quote.client', 'quote.lead', 'user'])
            ->latest('payment_date')
            ->take(15)
            ->get();

        $payments = compact('paymentsTotal', 'paymentsByType', 'monthlyPayments', 'paymentMonthLabels', 'recentPayments');

        // ===================== PROJECTS TAB =====================
        $projectsTotal = Project::count();
        $projectsByStatus = Project::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
        $completedProjects = $projectsByStatus['completed'] ?? 0;
        $projectCompletionRate = $projectsTotal > 0 ? round(($completedProjects / $projectsTotal) * 100, 1) : 0;
        $totalBudget = Project::sum('budget') / 100;

        // Overdue projects
        $overdueProjects = Project::with(['client', 'assignedUsers'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->take(10)
            ->get();

        $projects = compact('projectsTotal', 'projectsByStatus', 'projectCompletionRate', 'totalBudget', 'overdueProjects');

        // ===================== TASKS TAB =====================
        $tasksTotal = Task::count();
        $tasksDone = Task::where('status', 'done')->count();
        $tasksOverdue = Task::overdue()->count();
        $tasksPending = Task::pending()->count();
        $tasksByStatus = Task::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
        $tasksByPriority = Task::selectRaw('priority, count(*) as count')
            ->groupBy('priority')->pluck('count', 'priority')->toArray();

        // Overdue tasks
        $overdueTasks = Task::with(['assignedUsers', 'project'])
            ->overdue()
            ->orderBy('due_at')
            ->take(10)
            ->get();

        $tasks = compact('tasksTotal', 'tasksDone', 'tasksOverdue', 'tasksPending', 'tasksByStatus', 'tasksByPriority', 'overdueTasks');

        // ===================== TEAM PERFORMANCE TAB =====================
        $teamUsers = User::where('status', 'active')
            ->where('id', '!=', 1) // Exclude super admin
            ->orderBy('name')
            ->get();

        $teamData = [];
        foreach ($teamUsers as $user) {
            $teamData[] = [
                'id' => $user->id,
                'name' => $user->name,
                'leads_assigned' => Lead::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->count(),
                'leads_won' => Lead::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->where('stage', 'won')->count(),
                'tasks_assigned' => Task::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->count(),
                'tasks_completed' => Task::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->where('status', 'done')->count(),
                'activities_logged' => Activity::where('created_by_user_id', $user->id)->count(),
                'quotes_created' => Quote::where('created_by_user_id', $user->id)->count(),
                'revenue_generated' => Quote::where('created_by_user_id', $user->id)->where('status', 'accepted')->sum('grand_total') / 100,
            ];
        }

        $team = compact('teamData');

        // ===================== PRODUCTS TAB =====================
        $productsTotal = Product::count();
        $productsActive = Product::where('status', 'active')->count();

        // Sum inventory value
        $inventoryValue = Product::where('status', 'active')
            ->where('stock_qty', '>', 0)
            ->selectRaw('SUM((stock_qty * sale_price) / 100) as value')
            ->value('value');
        $inventoryValue = $inventoryValue ?: 0;

        $lowStockProducts = Product::whereColumn('stock_qty', '<=', 'min_stock_qty')
            ->where('status', 'active')
            ->count();

        // Top 10 selling products (based on accepted quotes)
        $topProducts = DB::table('quote_items')
            ->join('quotes', 'quote_items.quote_id', '=', 'quotes.id')
            ->where('quotes.status', 'accepted')
            ->whereNotNull('quote_items.product_id')
            ->select('quote_items.product_id', 'quote_items.product_name', 
                DB::raw('SUM(quote_items.qty) as qty_sold'), 
                DB::raw('SUM(quote_items.line_total) / 100 as revenue'))
            ->groupBy('quote_items.product_id', 'quote_items.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $products = compact('productsTotal', 'productsActive', 'inventoryValue', 'lowStockProducts', 'topProducts');

        // ===================== VENDORS TAB =====================
        $vendorsTotal = Vendor::count();
        $vendorsActive = Vendor::where('status', 'active')->count();

        $purchasesTotal = Purchase::sum('total_amount') / 100;
        $purchasesPaid = Purchase::sum('paid_amount') / 100;
        $purchasesDue = $purchasesTotal - $purchasesPaid;

        // Top 10 Vendors by purchase volume
        $topVendors = Vendor::withSum('purchases', 'total_amount')
            ->withSum('purchases', 'paid_amount')
            ->withCount('purchases')
            ->orderByDesc('purchases_sum_total_amount')
            ->limit(10)
            ->get();

        $vendors = compact('vendorsTotal', 'vendorsActive', 'purchasesTotal', 'purchasesPaid', 'purchasesDue', 'topVendors');

        return view('admin.reports.index', compact(
            'overview',
            'monthlyRevenue',
            'monthLabels',
            'leadsByStage',
            'leadStages',
            'leads',
            'quotes',
            'payments',
            'projects',
            'tasks',
            'team',
            'products',
            'vendors'
        ));
    }

    /**
     * Helper: Lead conversion rate.
     */
    private function getConversionRate(): float
    {
        $total = Lead::count();
        $won = Lead::where('stage', 'won')->count();
        return $total > 0 ? round(($won / $total) * 100, 1) : 0;
    }

    // ===================== EXCEL EXPORT METHODS =====================

    /**
     * Export Leads as CSV.
     */
    public function exportLeads(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $leads = Lead::with(['assignedUsers', 'createdBy'])->latest()->get();

        return $this->streamCsv('leads_report.csv', [
            'SR No',
            'Name',
            'Phone',
            'Email',
            'City',
            'State',
            'Source',
            'Stage',
            'Expected Value (₹)',
            'Assigned To',
            'Created By',
            'Next Follow-up',
            'Created At'
        ], $leads->map(function ($lead, $i) {
            return [
                $i + 1,
                $lead->name,
                $lead->phone,
                $lead->email,
                $lead->city,
                $lead->state,
                ucfirst($lead->source ?? '-'),
                ucfirst($lead->stage ?? '-'),
                $lead->expected_value ? number_format($lead->expected_value / 100, 2) : '0.00',
                $lead->assignedUsers->isNotEmpty() ? $lead->assignedUsers->pluck('name')->implode(', ') : 'Unassigned',
                $lead->createdBy->name ?? '-',
                $lead->next_follow_up_at ? $lead->next_follow_up_at->format('d/m/Y') : '-',
                $lead->created_at->format('d/m/Y H:i'),
            ];
        })->toArray());
    }

    /**
     * Export Quotes as CSV.
     */
    public function exportQuotes(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $quotes = Quote::with(['client', 'lead', 'createdBy'])->latest('date')->get();

        return $this->streamCsv('quotes_report.csv', [
            'SR No',
            'Quote No',
            'Client/Lead',
            'Date',
            'Subtotal (₹)',
            'Discount (₹)',
            'Tax (₹)',
            'Grand Total (₹)',
            'Status',
            'Created By',
            'Created At'
        ], $quotes->map(function ($q, $i) {
            $clientName = $q->client
                ? ($q->client->business_name ?: $q->client->contact_name)
                : ($q->lead->name ?? '-');
            return [
                $i + 1,
                $q->quote_no,
                $clientName,
                $q->date ? $q->date->format('d/m/Y') : '-',
                number_format($q->subtotal / 100, 2),
                number_format(($q->discount ?? 0) / 100, 2),
                number_format(($q->gst_total ?: $q->tax_amount ?: 0) / 100, 2),
                number_format($q->grand_total / 100, 2),
                ucfirst($q->status),
                $q->createdBy->name ?? '-',
                $q->created_at->format('d/m/Y H:i'),
            ];
        })->toArray());
    }

    /**
     * Export Payments as CSV.
     */
    public function exportPayments(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $payments = QuotePayment::with(['quote.client', 'quote.lead', 'user'])->latest('payment_date')->get();

        return $this->streamCsv('payments_report.csv', [
            'SR No',
            'Quote No',
            'Client/Lead',
            'Amount (₹)',
            'Payment Type',
            'Payment Date',
            'Recorded By',
            'Notes'
        ], $payments->map(function ($p, $i) {
            $clientName = '-';
            if ($p->quote) {
                $clientName = $p->quote->client
                    ? ($p->quote->client->business_name ?: $p->quote->client->contact_name)
                    : ($p->quote->lead->name ?? '-');
            }
            return [
                $i + 1,
                $p->quote->quote_no ?? '-',
                $clientName,
                number_format($p->amount / 100, 2),
                ucfirst(str_replace('_', ' ', $p->payment_type ?? '-')),
                $p->payment_date ? $p->payment_date->format('d/m/Y') : '-',
                $p->user->name ?? '-',
                $p->notes ?? '',
            ];
        })->toArray());
    }

    /**
     * Export Projects as CSV.
     */
    public function exportProjects(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $projects = Project::with(['client', 'assignedUsers', 'createdBy'])->latest()->get();

        return $this->streamCsv('projects_report.csv', [
            'SR No',
            'Name',
            'Client',
            'Status',
            'Budget (₹)',
            'Start Date',
            'Due Date',
            'Assigned To',
            'Tasks Total',
            'Tasks Done',
            'Progress %'
        ], $projects->map(function ($p, $i) {
            return [
                $i + 1,
                $p->name,
                $p->client ? ($p->client->business_name ?: $p->client->contact_name) : '-',
                ucfirst(str_replace('_', ' ', $p->status)),
                number_format(($p->budget ?? 0) / 100, 2),
                $p->start_date ? $p->start_date->format('d/m/Y') : '-',
                $p->due_date ? $p->due_date->format('d/m/Y') : '-',
                $p->assignedUsers->isNotEmpty() ? $p->assignedUsers->pluck('name')->implode(', ') : 'Unassigned',
                $p->total_tasks_count,
                $p->completed_tasks_count,
                $p->progress_percent . '%',
            ];
        })->toArray());
    }

    /**
     * Export Tasks as CSV.
     */
    public function exportTasks(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $tasks = Task::with(['assignedUsers', 'project'])->latest()->get();

        return $this->streamCsv('tasks_report.csv', [
            'SR No',
            'Title',
            'Project',
            'Status',
            'Priority',
            'Due Date',
            'Assigned To',
            'Completed At',
            'Created At'
        ], $tasks->map(function ($t, $i) {
            return [
                $i + 1,
                $t->title,
                $t->project->name ?? '-',
                ucfirst($t->status),
                ucfirst($t->priority),
                $t->due_at ? $t->due_at->format('d/m/Y') : '-',
                $t->assignedUsers->isNotEmpty() ? $t->assignedUsers->pluck('name')->implode(', ') : 'Unassigned',
                $t->completed_at ? $t->completed_at->format('d/m/Y H:i') : '-',
                $t->created_at->format('d/m/Y H:i'),
            ];
        })->toArray());
    }

    /**
     * Export Team Performance as CSV.
     */
    public function exportTeam(Request $request): StreamedResponse
    {
        if (!can('reports.read'))
            abort(403);

        $users = User::where('status', 'active')->where('id', '!=', 1)->orderBy('name')->get();

        $rows = $users->map(function ($user, $i) {
            return [
                $i + 1,
                $user->name,
                $user->email,
                Lead::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->count(),
                Lead::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->where('stage', 'won')->count(),
                Task::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->count(),
                Task::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))->where('status', 'done')->count(),
                Activity::where('created_by_user_id', $user->id)->count(),
                Quote::where('created_by_user_id', $user->id)->count(),
                number_format(Quote::where('created_by_user_id', $user->id)->where('status', 'accepted')->sum('grand_total') / 100, 2),
            ];
        })->toArray();

        return $this->streamCsv('team_performance_report.csv', [
            'SR No',
            'Name',
            'Email',
            'Leads Assigned',
            'Leads Won',
            'Tasks Assigned',
            'Tasks Completed',
            'Activities Logged',
            'Quotes Created',
            'Revenue Generated (₹)'
        ], $rows);
    }

    /**
     * Helper: Stream a CSV response.
     */
    private function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
