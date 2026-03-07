<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    /**
     * List all activities.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::forCompany($this->companyId())
            ->with('createdBy');

        // Filter by entity
        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->get('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by user
        if ($userId = $request->get('created_by')) {
            $query->where('created_by_user_id', $userId);
        }

        // Date range
        if ($from = $request->get('from_date')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to_date')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min($request->get('per_page', 15), 100);
        $activities = $query->paginate($perPage);

        return response()->json($this->paginated($activities, ActivityResource::class));
    }

    /**
     * Get activities for an entity.
     */
    public function forEntity(string $entityType, int $entityId): JsonResponse
    {
        $activities = Activity::forCompany($this->companyId())
            ->forEntity($entityType, $entityId)
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => ActivityResource::collection($activities),
        ]);
    }

    /**
     * Get single activity.
     */
    public function show(int $id): JsonResponse
    {
        $activity = Activity::forCompany($this->companyId())
            ->with('createdBy')
            ->findOrFail($id);

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Create new activity.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|in:' . implode(',', Activity::ENTITY_TYPES),
            'entity_id' => 'required|integer',
            'type' => 'required|in:' . implode(',', Activity::TYPES),
            'subject' => 'nullable|string|max:255',
            'summary' => 'required|string',
            'next_action_at' => 'nullable|date',
            'next_action_type' => 'nullable|string|max:100',
        ]);

        $activity = Activity::create([
            'company_id' => $this->companyId(),
            'created_by_user_id' => auth()->id(),
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'type' => $request->type,
            'subject' => $request->subject,
            'summary' => $request->summary,
            'next_action_at' => $request->next_action_at,
            'next_action_type' => $request->next_action_type,
        ]);

        return response()->json([
            'message' => 'Activity created successfully',
            'data' => new ActivityResource($activity->load('createdBy')),
        ], 201);
    }

    /**
     * Delete activity.
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::forCompany($this->companyId())->findOrFail($id);
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully',
        ]);
    }
}
