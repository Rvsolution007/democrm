<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MicroTask;
use Illuminate\Http\Request;

class TaskFollowupsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('tasks.read')) {
            abort(403, 'Unauthorized action.');
        }

        $filterDate = $request->input('date');

        $query = MicroTask::with(['task', 'task.project', 'task.project.client', 'task.project.lead', 'task.clientEntity', 'task.leadEntity', 'role'])
            ->whereNotNull('follow_up_date')
            ->where('status', '!=', 'done');

        // Global permission filter
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            $userRoleId = auth()->user()->role_id;
            $query->where(function ($q) use ($userRoleId) {
                $q->whereNull('role_id')
                    ->orWhere('role_id', $userRoleId);
            });
        }

        if ($filterDate) {
            $query->whereDate('follow_up_date', $filterDate);
            $filteredFollowups = $query->orderBy('follow_up_date', 'asc')->get();
            return view('admin.task-followups.index', compact('filteredFollowups', 'filterDate'));
        }

        $microTasks = $query->orderBy('follow_up_date', 'asc')->get();

        $today = now()->toDateString();

        $overdueFollowups = $microTasks->filter(fn($mt) => $mt->follow_up_date->toDateString() < $today);
        $todayFollowups = $microTasks->filter(fn($mt) => $mt->follow_up_date->toDateString() == $today);
        $upcomingFollowups = $microTasks->filter(fn($mt) => $mt->follow_up_date->toDateString() > $today);

        return view('admin.task-followups.index', compact(
            'todayFollowups',
            'overdueFollowups',
            'upcomingFollowups',
            'filterDate'
        ));
    }
}
