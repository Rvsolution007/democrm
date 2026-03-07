<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    /**
     * List all users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::forCompany($this->companyId())
            ->with('role');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by role
        if ($roleId = $request->get('role_id')) {
            $query->where('role_id', $roleId);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json($this->paginated($users, UserResource::class));
    }

    /**
     * Get single user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::forCompany($this->companyId())
            ->with('role')
            ->findOrFail($id);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Create new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'company_id' => $this->companyId(),
            'role_id' => $request->role_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user->load('role')),
        ], 201);
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::forCompany($this->companyId())->findOrFail($id);

        $data = $request->only(['name', 'email', 'phone', 'role_id', 'status']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh('role')),
        ]);
    }

    /**
     * Delete user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::forCompany($this->companyId())->findOrFail($id);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return $this->error('You cannot delete your own account', 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Activate user.
     */
    public function activate(int $id): JsonResponse
    {
        $user = User::forCompany($this->companyId())->findOrFail($id);
        $user->update(['status' => 'active']);

        return response()->json([
            'message' => 'User activated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(int $id): JsonResponse
    {
        $user = User::forCompany($this->companyId())->findOrFail($id);

        if ($user->id === auth()->id()) {
            return $this->error('You cannot deactivate your own account', 403);
        }

        $user->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'User deactivated successfully',
            'data' => new UserResource($user),
        ]);
    }
}
