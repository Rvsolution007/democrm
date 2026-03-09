<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RolesController extends Controller
{
    public function index()
    {
        // Bypass permission check for admin users
        if (!can('roles.read')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::withCount('users')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles', 'products'));
    }

    public function store(Request $request)
    {
        // Bypass permission check for admin users
        if (!can('roles.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['permissions'] = $validated['permissions'] ?? [];

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully');
    }

    public function update(Request $request, $id)
    {
        // Bypass permission check for admin users
        if (!can('roles.write')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['permissions'] = $validated['permissions'] ?? [];

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully');
    }

    public function destroy($id)
    {
        // Bypass permission check for admin users
        if (!can('roles.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($id);

        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role with assigned users');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
