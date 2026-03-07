<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class FollowupsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('leads.read')) {
            abort(403, 'Unauthorized action.');
        }

        $filterDate = $request->input('date');

        // Get leads that have a follow-up date set, ordered by nearest follow-up
        // Load only the latest followup per lead
        $query = Lead::with([
            'assignedTo',
            'products',
            'followups' => function ($q) {
                $q->with('user')->latest()->limit(1);
            }
        ])
            ->whereNotNull('next_follow_up_at');

        // Global permission filter
        if (!can('leads.global')) {
            $query->where(function ($q) {
                $q->where('assigned_to_user_id', auth()->id())
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Date filter: show followups for a specific date
        if ($filterDate) {
            $query->whereDate('next_follow_up_at', $filterDate);
            $followups = $query->orderBy('next_follow_up_at', 'asc')->get();

            // When filtering by date, all results go into one group
            $filteredFollowups = $followups;
            $todayFollowups = collect();
            $overdueFollowups = collect();
            $upcomingFollowups = collect();
        } else {
            $followups = $query->orderBy('next_follow_up_at', 'asc')->get();

            $today = now()->toDateString();
            $filteredFollowups = collect();
            $todayFollowups = $followups->filter(fn($l) => optional($l->next_follow_up_at)->toDateString() == $today);
            $overdueFollowups = $followups->filter(fn($l) => optional($l->next_follow_up_at)->toDateString() < $today);
            $upcomingFollowups = $followups->filter(fn($l) => optional($l->next_follow_up_at)->toDateString() > $today);
        }

        return view('admin.followups.index', compact(
            'todayFollowups',
            'overdueFollowups',
            'upcomingFollowups',
            'filteredFollowups',
            'filterDate'
        ));
    }
}
