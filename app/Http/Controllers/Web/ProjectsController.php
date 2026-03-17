<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('projects.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Project::with(['client', 'assignedTo', 'tasks', 'lead', 'quote']);

        if (!can('projects.global')) {
            $query->where(function ($q) {
                $q->whereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    })
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Search (project name, client name, phone number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('contact_name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('phone', 'like', "%{$search}%");
                    });
                // Search by Project ID pattern like Project-26-001
                if (preg_match('/project-?(\d{2})?-?(\d+)?/i', $search, $matches)) {
                    if (!empty($matches[2])) {
                        $q->orWhere('id', (int)$matches[2]);
                    }
                }
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Assigned To filter
        if ($request->filled('assigned_to')) {
            $query->whereHas('assignedUsers', function ($q) use ($request) {
                $q->where('user_id', $request->assigned_to);
            });
        }

        // Start Date filter
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        // Due Date filter
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', '<=', $request->due_date);
        }

        $projects = $query->latest()->paginate(20)->withQueryString();

        // AJAX request — return JSON for live search
        if ($request->ajax()) {
            $data = $projects->map(function ($p) {
                $contactPhone = $p->lead->phone ?? $p->client->phone ?? null;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'project_id_code' => $p->project_id_code,
                    'description' => $p->description ? \Str::limit($p->description, 50) : null,
                    'client_name' => $p->client->display_name ?? '—',
                    'contact_phone' => $contactPhone,
                    'status' => $p->status,
                    'tasks_done' => $p->tasks->where('status', 'done')->count(),
                    'tasks_total' => $p->tasks->count(),
                    'start_date' => $p->start_date ? $p->start_date->format('d M Y') : null,
                    'due_date' => $p->due_date ? $p->due_date->format('d M Y') : null,
                    'assigned_to' => $p->assignedUsers->isNotEmpty() ? $p->assignedUsers->pluck('name')->implode(', ') : 'Unassigned',
                    'quote_id' => $p->quote_id,
                    'raw' => $p->toArray(),
                ];
            });
            return response()->json([
                'projects' => $data,
                'total' => $projects->total(),
                'showing' => $projects->count(),
            ]);
        }

        // Users list: global sees all, non-global sees only self
        if (can('projects.global') || auth()->user()->isAdmin()) {
            $users = User::where('status', 'active')->withModulePermission('projects')->orderBy('name')->get();
        } else {
            $users = collect([auth()->user()]);
        }

        return view('admin.projects.index', compact('projects', 'users'));
    }

    public function show($id)
    {
        if (!can('projects.read')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::with(['client', 'assignedUsers', 'createdBy', 'quote', 'lead', 'tasks.assignedUsers', 'tasks.activities.user'])->findOrFail($id);

        if (!can('projects.global') && $project->created_by_user_id != auth()->id() && !$project->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only view your own projects.');
        }

        $users = (can('projects.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('projects')->orderBy('name')->get()
            : collect();

        $globalTaskUsers = User::where('status', 'active')
            ->whereHas('role', function($q) {
                $q->where('permissions', 'LIKE', '%"tasks.global"%')
                  ->orWhere('permissions', 'LIKE', '%"all"%')
                  ->orWhere('name', 'LIKE', '%admin%');
            })->orderBy('name')->get();

        return view('admin.projects.show', compact('project', 'users', 'globalTaskUsers'));
    }

    public function update(Request $request, $id)
    {
        if (!can('projects.write')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::findOrFail($id);

        if (!can('projects.global') && $project->created_by_user_id != auth()->id() && !$project->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own projects.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
        ]);

        $project->update($validated);

        $assignedUsers = [];
        if (!can('projects.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $syncResult = $project->assignedUsers()->sync($assignedUsers);

        // Send assignment notification to new assignees
        if (!empty($syncResult['attached'])) {
            foreach ($syncResult['attached'] as $newUserId) {
                if ($newUserId != auth()->id()) {
                    $assignedUser = \App\Models\User::find($newUserId);
                    if ($assignedUser) {
                        $assignedUser->notify(new \App\Notifications\AssignedNotification(
                            'project',
                            $project->id,
                            $project->name,
                            auth()->user()->name
                        ));
                    }
                }
            }
        }

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Project updated successfully');
    }
    public function destroy($id)
    {
        if (!can('projects.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::findOrFail($id);

        if (!can('projects.global') && $project->created_by_user_id != auth()->id() && !$project->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only delete your own projects.');
        }

        // Delete associated tasks
        Task::where('project_id', $project->id)->delete();
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project deleted successfully');
    }

    // Task CRUD within project
    public function updateTask(Request $request, $projectId, $taskId)
    {
        if (!can('tasks.write')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);

        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'additional_description' => 'nullable|string',
            'status' => 'required|in:' . implode(',', $dynamicStatuses),
            'priority' => 'required|in:low,medium,high',
            'due_at' => 'nullable|date',
            'contact_number' => 'nullable|string',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
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

        $task->update($validated);

        $assignedUsers = [];
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $task->assignedUsers()->sync($assignedUsers);

        // Log status change activity
        if ($task->wasChanged('status')) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'type' => 'status_change',
                'message' => 'Status changed from ' . ucfirst(str_replace('_', ' ', $request->input('_old_status', 'unknown'))) . ' to ' . ucfirst(str_replace('_', ' ', $task->status)),
                'old_value' => $request->input('_old_status'),
                'new_value' => $task->status,
            ]);

            // Set started_at when task moves from todo to another status
            if ($request->input('_old_status') === 'todo' && $task->status !== 'todo' && !$task->started_at) {
                $task->update(['started_at' => now()]);
            }
        }

        // Log description update as note
        if (!empty($request->input('additional_description'))) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'type' => 'note',
                'message' => $request->input('additional_description'),
            ]);
        }

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Task updated successfully');
    }

    // Store task activity (manual note, client reply, revision)
    public function storeTaskActivity(Request $request, $projectId, $taskId)
    {
        if (!can('tasks.write')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);

        $validated = $request->validate([
            'type' => 'required|in:note,client_reply,revision',
            'message' => 'required|string|max:2000',
            'notified_user_id' => 'nullable|exists:users,id',
        ]);

        TaskActivity::create([
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

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Activity added successfully');
    }

    public function destroyTask($projectId, $taskId)
    {
        if (!can('tasks.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);
        $task->forceDelete();

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Task deleted successfully');
    }

    // Micro Tasks
    public function storeMicroTask(Request $request, $projectId, $taskId)
    {
        if (!can('tasks.write')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $maxOrder = \App\Models\MicroTask::where('task_id', $task->id)->max('sort_order') ?? 0;

        $microTask = \App\Models\MicroTask::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'status' => 'todo',
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'micro_task' => $microTask
        ]);
    }

    public function updateMicroTask(Request $request, $projectId, $taskId, $microTaskId)
    {
        if (!can('tasks.write')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);
        $microTask = \App\Models\MicroTask::where('task_id', $task->id)->findOrFail($microTaskId);

        $validated = $request->validate([
            'status' => 'nullable|in:todo,doing,done',
            'follow_up_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
        ]);

        $microTask->update($validated);

        return response()->json([
            'success' => true,
            'micro_task' => $microTask
        ]);
    }

    public function destroyMicroTask($projectId, $taskId, $microTaskId)
    {
        if (!can('tasks.delete')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $project = Project::findOrFail($projectId);
        $task = Task::where('project_id', $project->id)->findOrFail($taskId);
        $microTask = \App\Models\MicroTask::where('task_id', $task->id)->findOrFail($microTaskId);

        $microTask->delete();

        return response()->json(['success' => true]);
    }
}
