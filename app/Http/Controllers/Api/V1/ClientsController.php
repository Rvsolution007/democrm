<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    /**
     * List all clients.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::forCompany($this->companyId());

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);

        $perPage = min($request->get('per_page', 15), 100);
        $clients = $query->paginate($perPage);

        return response()->json($this->paginated($clients, ClientResource::class));
    }

    /**
     * Get single client.
     */
    public function show(int $id): JsonResponse
    {
        $client = Client::forCompany($this->companyId())
            ->with(['lead', 'quotes'])
            ->findOrFail($id);

        return response()->json([
            'data' => new ClientResource($client),
        ]);
    }

    /**
     * Create new client.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create([
            'company_id' => $this->companyId(),
            'lead_id' => $request->lead_id,
            'type' => $request->type ?? 'business',
            'business_name' => $request->business_name,
            'contact_name' => $request->contact_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'gstin' => $request->gstin ? strtoupper($request->gstin) : null,
            'pan' => $request->pan ? strtoupper($request->pan) : null,
            'billing_address' => $request->billing_address,
            'shipping_address' => $request->shipping_address ?? $request->billing_address,
            'credit_limit' => ($request->credit_limit ?? 0) * 100,
            'payment_terms_days' => $request->payment_terms_days ?? 30,
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Client created successfully',
            'data' => new ClientResource($client),
        ], 201);
    }

    /**
     * Update client.
     */
    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        $client = Client::forCompany($this->companyId())->findOrFail($id);

        $data = $request->only([
            'type',
            'business_name',
            'contact_name',
            'phone',
            'email',
            'billing_address',
            'shipping_address',
            'payment_terms_days',
            'status',
            'notes'
        ]);

        if ($request->has('gstin')) {
            $data['gstin'] = $request->gstin ? strtoupper($request->gstin) : null;
        }
        if ($request->has('pan')) {
            $data['pan'] = $request->pan ? strtoupper($request->pan) : null;
        }
        if ($request->has('credit_limit')) {
            $data['credit_limit'] = $request->credit_limit * 100;
        }

        $client->update($data);

        return response()->json([
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client->fresh()),
        ]);
    }

    /**
     * Delete client.
     */
    public function destroy(int $id): JsonResponse
    {
        $client = Client::forCompany($this->companyId())->findOrFail($id);

        // Check for quotes
        if ($client->quotes()->exists()) {
            return $this->error('Cannot delete client with existing quotes', 422);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }
}
