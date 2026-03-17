<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    /**
     * List all tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::forCompany($this->companyId())
            ->with(['assignedUsers', 'createdBy']);

        // Global permission filter
        if (!can('tasks.global')) {
            $query->where(function ($q) {
                $q->whereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    })
                    ->orWhere('created_by_user_id', auth()->id());
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by priority
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        // Filter by assigned user
        if ($assignedTo = $request->get('assigned_to')) {
            $query->whereHas('assignedUsers', fn($q) => $q->where('user_id', $assignedTo));
        }

        // Filter by entity
        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->get('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        // Filter overdue
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        // Filter pending only (todo + doing)
        if ($request->boolean('pending_only')) {
            $query->pending();
        }

        // Due date range
        if ($dueFrom = $request->get('due_from')) {
            $query->whereDate('due_at', '>=', $dueFrom);
        }
        if ($dueTo = $request->get('due_to')) {
            $query->whereDate('due_at', '<=', $dueTo);
        }

        $sortBy = $request->get('sort', 'due_at');
        $order = $request->get('order', 'asc');
        $query->orderBy($sortBy, $order);

        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        return response()->json($this->paginated($tasks, TaskResource::class));
    }

    /**
     * Get tasks for an entity.
     */
    public function forEntity(string $entityType, int $entityId): JsonResponse
    {
        $tasks = Task::forCompany($this->companyId())
            ->forEntity($entityType, $entityId)
            ->with(['assignedUsers', 'createdBy'])
            ->orderBy('due_at', 'asc')
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * Get single task.
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::forCompany($this->companyId())
            ->with(['assignedUsers', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Create new task.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entity_type' => 'nullable|in:' . implode(',', Task::ENTITY_TYPES),
            'entity_id' => 'nullable|integer',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
            'due_at' => 'nullable|date',
            'priority' => 'nullable|in:' . implode(',', Task::PRIORITIES),
        ]);

        $task = Task::create([
            'company_id' => $this->companyId(),
            'created_by_user_id' => auth()->id(),
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_at' => $request->due_at,
            'priority' => $request->priority ?? 'medium',
            'status' => 'todo',
        ]);

        $assignedUsers = [];
        if (!can('tasks.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', [auth()->id()]);
        }
        $task->assignedUsers()->sync($assignedUsers);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => new TaskResource($task->load(['assignedUsers', 'createdBy'])),
        ], 201);
    }

    /**
     * Update task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::forCompany($this->companyId())->findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
            'due_at' => 'nullable|date',
            'priority' => 'nullable|in:' . implode(',', Task::PRIORITIES),
            'status' => 'nullable|in:' . implode(',', Task::STATUSES),
        ]);

        $data = $request->only(['title', 'description', 'due_at', 'priority', 'status']);

        // Set completed_at if marking as done
        if ($request->status === 'done' && !$task->isDone()) {
            $data['completed_at'] = now();
        } elseif ($request->status && $request->status !== 'done') {
            $data['completed_at'] = null;
        }

        $task->update($data);

        if ($request->has('assigned_to_users')) {
            $assignedUsers = [];
            if (!can('tasks.global') && !auth()->user()->isAdmin()) {
                $assignedUsers = [auth()->id()];
            } else {
                $assignedUsers = $request->input('assigned_to_users', []);
            }
            $task->assignedUsers()->sync($assignedUsers);
        }

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task->fresh(['assignedUsers', 'createdBy'])),
        ]);
    }

    /**
     * Delete task.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::forCompany($this->companyId())->findOrFail($id);
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Mark task as done.
     */
    public function markDone(int $id): JsonResponse
    {
        $task = Task::forCompany($this->companyId())->findOrFail($id);
        $task->markAsDone();

        return response()->json([
            'message' => 'Task marked as done',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Update task status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', Task::STATUSES),
        ]);

        $task = Task::forCompany($this->companyId())->findOrFail($id);

        $task->status = $request->status;
        if ($request->status === 'done') {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }
        $task->save();

        return response()->json([
            'message' => 'Task status updated',
            'data' => new TaskResource($task),
        ]);
    }
}
