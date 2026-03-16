<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MicroTask;
use App\Models\Task;
use Illuminate\Http\Request;

class MicroTasksController extends Controller
{
    public function index(Request $request)
    {
        if (!can('tasks.read')) {
            abort(403, 'Unauthorized action.');
        }

        // Base query for MicroTasks with parent Task assigned user
        $query = MicroTask::with(['task.assignedTo', 'task.project.client', 'task.project.lead', 'task.clientEntity', 'task.leadEntity', 'role']);

        // Global permission filter
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            $query->whereHas('task', function ($q) {
                $q->where('assigned_to_user_id', auth()->id())
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Role-based visibility filter for micro tasks index (Kanban)
        if (!auth()->user()->isAdmin()) {
            $query->where('role_id', auth()->user()->role_id);
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->whereHas('task', function ($q) use ($request) {
                $q->where('priority', $request->priority);
            });
        }

        // Assigned To filter
        if ($request->filled('assigned_to')) {
            $query->whereHas('task', function ($q) use ($request) {
                $q->where('assigned_to_user_id', $request->assigned_to);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('task', function ($t) use ($search) {
                        $t->where('title', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%")
                            ->orWhereHas('clientEntity', function ($cq) use ($search) {
                                $cq->where('business_name', 'like', "%{$search}%")
                                    ->orWhere('contact_name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orWhereHas('leadEntity', function ($lq) use ($search) {
                                $lq->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orWhereHas('project', function ($pq) use ($search) {
                                $pq->where('name', 'like', "%{$search}%")
                                    ->orWhereHas('client', function ($cq) use ($search) {
                                        $cq->where('business_name', 'like', "%{$search}%")
                                            ->orWhere('contact_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('lead', function ($lq) use ($search) {
                                        $lq->where('name', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date Range (Follow-up Date)
        if ($request->filled('date_from')) {
            $query->whereDate('follow_up_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('follow_up_date', '<=', $request->date_to);
        }

        $microTasks = $query->latest()->get();

        // AJAX request — return JSON for live filtering/search
        if ($request->ajax()) {
            $data = $microTasks->map(function ($mt) {
                $task = $mt->task;
                $clientName = null;
                $projectName = $task?->project?->name ?? null;

                if ($task) {
                    if ($task->entity_type === 'client' && $task->clientEntity) {
                        $clientName = $task->clientEntity->business_name ?: $task->clientEntity->contact_name;
                    } elseif ($task->entity_type === 'lead' && $task->leadEntity) {
                        $clientName = $task->leadEntity->name . ' (Lead)';
                    } elseif ($task->project_id && $task->project) {
                        if ($task->project->client) {
                            $clientName = $task->project->client->business_name ?: $task->project->client->contact_name;
                        } elseif ($task->project->lead) {
                            $clientName = $task->project->lead->name . ' (Lead)';
                        }
                    }
                }

                return [
                    'id' => $mt->id,
                    'title' => $mt->title,
                    'status' => $mt->status,
                    'follow_up_date' => $mt->follow_up_date ? $mt->follow_up_date->format('d M Y') : null,
                    'task_title' => $task->title ?? 'Unknown Task',
                    'project_name' => $projectName,
                    'client_name' => $clientName,
                    'priority' => $task->priority ?? 'medium',
                    'assigned_to' => $task->assignedTo->name ?? 'Unassigned',
                    'assigned_initials' => isset($task->assignedTo) ? strtoupper(substr($task->assignedTo->name, 0, 2)) : '?',
                ];
            });

            return response()->json([
                'micro_tasks' => $data,
                'total' => $microTasks->count(),
            ]);
        }

        // Users list: global sees all users, non-global sees only self
        if (can('tasks.global') || auth()->user()->isAdmin()) {
            $users = \App\Models\User::where('status', 'active')->withModulePermission('tasks')->orderBy('name')->get();
        } else {
            $users = collect([auth()->user()]);
        }

        // We use the same standard columns: todo, doing, done
        $statuses = ['todo', 'doing', 'done'];

        return view('admin.micro-tasks.index', compact('microTasks', 'users', 'statuses'));
    }

    public function updateStatus(Request $request, $id)
    {
        if (!can('tasks.write')) {
            abort(403, 'Unauthorized action.');
        }

        $microTask = MicroTask::findOrFail($id);

        // Role-based permission: Normal users can ONLY edit micro tasks explicitly assigned to their role
        if (!auth()->user()->isAdmin() && ($microTask->role_id !== auth()->user()->role_id || is_null($microTask->role_id))) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to change this micro task.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:todo,doing,done',
        ]);

        $oldStatus = $microTask->status;
        $microTask->update(['status' => $validated['status']]);

        // Log activity in parent Task
        if ($oldStatus !== $validated['status'] && $microTask->task) {
            \App\Models\TaskActivity::create([
                'task_id' => $microTask->task_id,
                'user_id' => auth()->id(),
                'type' => 'status_change',
                'message' => "Micro Task '{$microTask->title}' status changed to " . ucfirst($validated['status']),
                'old_value' => $oldStatus,
                'new_value' => $validated['status'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Micro Task status updated']);
    }

    public function show($id)
    {
        if (!can('tasks.read')) {
            abort(403, 'Unauthorized action.');
        }

        $microTask = MicroTask::with(['task.assignedTo'])->findOrFail($id);

        // Role-based visibility: Normal users can only view matching roles or unassigned
        if (!auth()->user()->isAdmin() && $microTask->role_id !== null && $microTask->role_id != auth()->user()->role_id) {
            abort(403, 'You do not have permission to view this micro task.');
        }

        // Optional permission check for non-global users
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            if ($microTask->task->assigned_to_user_id !== auth()->id() && $microTask->task->created_by_user_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        return response()->json([
            'id' => $microTask->id,
            'title' => $microTask->title,
            'status' => $microTask->status,
            'follow_up_date' => $microTask->follow_up_date ? $microTask->follow_up_date->format('Y-m-d\TH:i') : null,
            'task_title' => $microTask->task->title ?? 'Unknown Task'
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!can('tasks.write')) {
            abort(403, 'Unauthorized action.');
        }

        $microTask = MicroTask::findOrFail($id);

        // Role-based permission: Normal users can ONLY edit micro tasks explicitly assigned to their role
        if (!auth()->user()->isAdmin() && ($microTask->role_id !== auth()->user()->role_id || is_null($microTask->role_id))) {
            abort(403, 'You do not have permission to edit this micro task.');
        }

        // Optional permission check for non-global users
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            if ($microTask->task->assigned_to_user_id !== auth()->id() && $microTask->task->created_by_user_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:todo,doing,done',
            'follow_up_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $oldStatus = $microTask->status;
        $microTask->update($validated);

        // Log status change activity
        if ($oldStatus !== $validated['status'] && $microTask->task) {
            \App\Models\TaskActivity::create([
                'task_id' => $microTask->task_id,
                'user_id' => auth()->id(),
                'type' => 'status_change',
                'message' => "Micro Task '{$microTask->title}' status changed to " . ucfirst($validated['status']),
                'old_value' => $oldStatus,
                'new_value' => $validated['status'],
            ]);
        }

        // Log note activity
        if (!empty($validated['note']) && $microTask->task) {
            \App\Models\TaskActivity::create([
                'task_id' => $microTask->task_id,
                'user_id' => auth()->id(),
                'type' => 'note',
                'message' => "Note on Micro Task '{$microTask->title}': " . $validated['note'],
            ]);
        }

        return redirect()->back()->with('success', 'Micro Task updated successfully.');
    }

    public function destroy($id)
    {
        if (!can('tasks.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $microTask = MicroTask::findOrFail($id);

        // Role-based permission: Normal users can ONLY delete micro tasks explicitly assigned to their role
        if (!auth()->user()->isAdmin() && ($microTask->role_id !== auth()->user()->role_id || is_null($microTask->role_id))) {
            abort(403, 'You do not have permission to delete this micro task.');
        }

        // Optional permission check for non-global users
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            if ($microTask->task->assigned_to_user_id !== auth()->id() && $microTask->task->created_by_user_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $microTask->delete();

        return redirect()->back()->with('success', 'Micro Task deleted successfully.');
    }
}
