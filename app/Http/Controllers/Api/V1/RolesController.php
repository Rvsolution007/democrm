<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RolesController extends Controller
{
    /**
     * List all roles.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::forCompany($this->companyId())
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Get single role.
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::forCompany($this->companyId())
            ->withCount('users')
            ->findOrFail($id);

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Get available permissions.
     */
    public function permissions(): JsonResponse
    {
        return response()->json([
            'data' => Role::PERMISSIONS,
        ]);
    }

    /**
     * Create new role.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', Role::PERMISSIONS),
        ]);

        $role = Role::create([
            'company_id' => $this->companyId(),
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'permissions' => $request->permissions,
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => new RoleResource($role),
        ], 201);
    }

    /**
     * Update role.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::forCompany($this->companyId())->findOrFail($id);

        if ($role->is_system) {
            return $this->error('System roles cannot be modified', 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|in:' . implode(',', Role::PERMISSIONS),
        ]);

        $data = $request->only(['name', 'description', 'permissions']);

        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $role->update($data);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Delete role.
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::forCompany($this->companyId())->findOrFail($id);

        if ($role->is_system) {
            return $this->error('System roles cannot be deleted', 403);
        }

        if ($role->users()->exists()) {
            return $this->error('Cannot delete role with assigned users', 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Set permissions for role.
     */
    public function setPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::forCompany($this->companyId())->findOrFail($id);

        if ($role->is_system) {
            return $this->error('System role permissions cannot be modified', 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', Role::PERMISSIONS),
        ]);

        $role->update(['permissions' => $request->permissions]);

        return response()->json([
            'message' => 'Role permissions updated successfully',
            'data' => new RoleResource($role),
        ]);
    }
}
