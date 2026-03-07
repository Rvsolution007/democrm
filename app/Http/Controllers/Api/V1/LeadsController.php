<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    /**
     * List all leads.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lead::forCompany($this->companyId())
            ->with('assignedTo');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by stage
        if ($stage = $request->get('stage')) {
            $query->where('stage', $stage);
        }

        // Filter by source
        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        // Filter by assigned user
        if ($assignedTo = $request->get('assigned_to')) {
            $query->where('assigned_to_user_id', $assignedTo);
        }

        // Filter by date range
        if ($from = $request->get('from_date')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to_date')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Filter overdue follow-ups
        if ($request->boolean('overdue_only')) {
            $query->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())
                ->whereNotIn('stage', ['won', 'lost']);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);

        // Pagination
        if ($request->boolean('all')) {
            $leads = $query->get();
            return response()->json([
                'data' => LeadResource::collection($leads),
                'meta' => [
                    'total' => $leads->count(),
                    'per_page' => $leads->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                ]
            ]);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $leads = $query->paginate($perPage);

        return response()->json($this->paginated($leads, LeadResource::class));
    }

    /**
     * Get single lead.
     */
    public function show(int $id): JsonResponse
    {
        $lead = Lead::forCompany($this->companyId())
            ->with(['assignedTo', 'activities', 'tasks', 'quotes'])
            ->findOrFail($id);

        return response()->json([
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * Create new lead.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = Lead::create([
            'company_id' => $this->companyId(),
            'assigned_to_user_id' => $request->assigned_to_user_id,
            'source' => $request->source ?? 'other',
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'city' => $request->city,
            'state' => $request->state,
            'stage' => $request->stage ?? 'new',
            'expected_value' => ($request->expected_value ?? 0) * 100, // Convert to paise
            'next_follow_up_at' => $request->next_follow_up_at,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Lead created successfully',
            'data' => new LeadResource($lead->load('assignedTo')),
        ], 201);
    }

    /**
     * Update lead.
     */
    public function update(UpdateLeadRequest $request, int $id): JsonResponse
    {
        $lead = Lead::forCompany($this->companyId())->findOrFail($id);

        $data = $request->only([
            'name',
            'phone',
            'email',
            'city',
            'state',
            'stage',
            'source',
            'assigned_to_user_id',
            'next_follow_up_at',
            'notes'
        ]);

        if ($request->has('expected_value')) {
            $data['expected_value'] = $request->expected_value * 100;
        }

        $lead->update($data);

        return response()->json([
            'message' => 'Lead updated successfully',
            'data' => new LeadResource($lead->fresh('assignedTo')),
        ]);
    }

    /**
     * Delete lead.
     */
    public function destroy(int $id): JsonResponse
    {
        $lead = Lead::forCompany($this->companyId())->findOrFail($id);
        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully',
        ]);
    }

    /**
     * Assign lead to user.
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $lead = Lead::forCompany($this->companyId())->findOrFail($id);
        $lead->update(['assigned_to_user_id' => $request->user_id]);

        return response()->json([
            'message' => 'Lead assigned successfully',
            'data' => new LeadResource($lead->fresh('assignedTo')),
        ]);
    }

    /**
     * Set follow-up date.
     */
    public function setFollowUp(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'next_follow_up_at' => 'required|date|after:now',
        ]);

        $lead = Lead::forCompany($this->companyId())->findOrFail($id);
        $lead->update(['next_follow_up_at' => $request->next_follow_up_at]);

        return response()->json([
            'message' => 'Follow-up date updated successfully',
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * Update lead stage.
     */
    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stage' => 'required|in:' . implode(',', Lead::STAGES),
        ]);

        $lead = Lead::forCompany($this->companyId())->findOrFail($id);
        $lead->update(['stage' => $request->stage]);

        return response()->json([
            'message' => 'Lead stage updated successfully',
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * Convert lead to client.
     */
    public function convertToClient(Request $request, int $id): JsonResponse
    {
        $lead = Lead::forCompany($this->companyId())
            ->whereNotIn('stage', ['won', 'lost'])
            ->findOrFail($id);

        // Check if already converted
        if ($lead->client()->exists()) {
            return $this->error('This lead has already been converted to a client', 422);
        }

        $request->validate([
            'type' => 'sometimes|in:business,individual',
            'business_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|size:10',
        ]);

        // Create client from lead
        $client = Client::create([
            'company_id' => $this->companyId(),
            'lead_id' => $lead->id,
            'type' => $request->type ?? 'business',
            'business_name' => $request->business_name,
            'contact_name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'gstin' => $request->gstin,
            'pan' => $request->pan,
            'billing_address' => [
                'city' => $lead->city,
                'state' => $lead->state,
                'country' => 'India',
            ],
            'status' => 'active',
        ]);

        // Mark lead as won
        $lead->update(['stage' => 'won']);

        // Link quotes to new client
        Quote::where('lead_id', $lead->id)->whereNull('client_id')->update(['client_id' => $client->id]);

        // Auto-create ONE project and tasks from all quotes
        $quotes = Quote::where('lead_id', $lead->id)->with('items')->get();
        if ($quotes->isNotEmpty()) {
            $totalBudget = $quotes->sum('grand_total');

            $project = Project::create([
                'company_id' => $this->companyId(),
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

            foreach ($quotes as $quote) {
                foreach ($quote->items as $item) {
                    Task::create([
                        'company_id' => $this->companyId(),
                        'project_id' => $project->id,
                        'assigned_to_user_id' => $lead->assigned_to_user_id ?? auth()->id(),
                        'created_by_user_id' => auth()->id(),
                        'entity_type' => 'project',
                        'entity_id' => $project->id,
                        'title' => $item->product_name,
                        'description' => ($item->description ? $item->description . ' | ' : '') . 'Qty: ' . $item->qty,
                        'priority' => 'medium',
                        'status' => 'todo',
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Lead converted to client successfully',
            'data' => [
                'lead' => new LeadResource($lead->fresh()),
                'client' => $client,
            ],
        ], 201);
    }
}
