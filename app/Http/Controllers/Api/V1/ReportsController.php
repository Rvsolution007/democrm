<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Dashboard stats.
     */
    public function dashboard(): JsonResponse
    {
        $companyId = $this->companyId();
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7)->startOfDay();

        // Leads stats
        $newLeadsToday = Lead::forCompany($companyId)
            ->whereDate('created_at', $today)
            ->count();

        $newLeads7Days = Lead::forCompany($companyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $openLeads = Lead::forCompany($companyId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->count();

        $overdueFollowUps = Lead::forCompany($companyId)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->whereNotIn('stage', ['won', 'lost'])
            ->count();

        // Quote stats
        $quotesSent = Quote::forCompany($companyId)
            ->where('status', 'sent')
            ->count();

        $quotesAccepted = Quote::forCompany($companyId)
            ->where('status', 'accepted')
            ->count();

        $revenue = Quote::forCompany($companyId)
            ->where('status', 'accepted')
            ->sum('grand_total') / 100; // Convert paise to rupees

        // Tasks stats
        $overdueTasks = Task::forCompany($companyId)
            ->overdue()
            ->count();

        $pendingTasks = Task::forCompany($companyId)
            ->pending()
            ->count();

        // Leads by stage
        $leadsByStage = Lead::forCompany($companyId)
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->get()
            ->pluck('count', 'stage')
            ->toArray();

        // Fill missing stages
        foreach (Lead::STAGES as $stage) {
            if (!isset($leadsByStage[$stage])) {
                $leadsByStage[$stage] = 0;
            }
        }

        return response()->json([
            'data' => [
                'new_leads_today' => $newLeadsToday,
                'new_leads_7_days' => $newLeads7Days,
                'open_leads' => $openLeads,
                'overdue_follow_ups' => $overdueFollowUps,
                'quotes_sent' => $quotesSent,
                'quotes_accepted' => $quotesAccepted,
                'revenue' => $revenue,
                'overdue_tasks' => $overdueTasks,
                'pending_tasks' => $pendingTasks,
                'leads_by_stage' => $leadsByStage,
            ],
        ]);
    }

    /**
     * Leads report.
     */
    public function leads(Request $request): JsonResponse
    {
        $companyId = $this->companyId();
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        // Leads by source
        $bySource = Lead::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get();

        // Leads by stage
        $byStage = Lead::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->get();

        // Conversion rate
        $totalLeads = Lead::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->count();

        $wonLeads = Lead::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('stage', 'won')
            ->count();

        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 2) : 0;

        // Daily trend
        $dailyTrend = Lead::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'period' => ['from' => $fromDate, 'to' => $toDate],
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $conversionRate,
                'by_source' => $bySource,
                'by_stage' => $byStage,
                'daily_trend' => $dailyTrend,
            ],
        ]);
    }

    /**
     * Quotes/Revenue report.
     */
    public function quotes(Request $request): JsonResponse
    {
        $companyId = $this->companyId();
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        // Quotes by status
        $byStatus = Quote::forCompany($companyId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->select('status', DB::raw('count(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn($item) => [
                'status' => $item->status,
                'count' => $item->count,
                'total' => $item->total / 100,
            ]);

        // Total revenue (accepted quotes)
        $revenue = Quote::forCompany($companyId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('status', 'accepted')
            ->sum('grand_total') / 100;

        // Monthly revenue trend
        $monthlyRevenue = Quote::forCompany($companyId)
            ->where('status', 'accepted')
            ->whereYear('date', now()->year)
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'month' => $item->month,
                'total' => $item->total / 100,
            ]);

        return response()->json([
            'data' => [
                'period' => ['from' => $fromDate, 'to' => $toDate],
                'revenue' => $revenue,
                'by_status' => $byStatus,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }

    /**
     * Activity report.
     */
    public function activities(Request $request): JsonResponse
    {
        $companyId = $this->companyId();
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        // Activities by type
        $byType = Activity::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Activities by user
        $byUser = Activity::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->join('users', 'activities.created_by_user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(*) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Daily activity count
        $dailyCount = Activity::forCompany($companyId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'period' => ['from' => $fromDate, 'to' => $toDate],
                'total' => Activity::forCompany($companyId)
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->count(),
                'by_type' => $byType,
                'by_user' => $byUser,
                'daily_count' => $dailyCount,
            ],
        ]);
    }
}
