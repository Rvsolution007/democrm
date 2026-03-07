<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServiceTemplate;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Http\Request;

class ServiceTemplatesController extends Controller
{
    public function index()
    {
        if (!can('settings.read')) {
            abort(403, 'Unauthorized action.');
        }

        $templates = ServiceTemplate::with('product')->latest()->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $roles = Role::whereJsonContains('permissions', 'tasks.read')
            ->orWhereJsonContains('permissions', 'tasks.write')
            ->orderBy('name')
            ->get();

        return view('admin.service-templates.index', compact('templates', 'products', 'roles'));
    }

    public function store(Request $request)
    {
        if (!can('settings.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.priority' => 'required|in:low,medium,high',
            'tasks.*.role_id' => 'nullable|exists:roles,id',
        ]);

        // Build tasks_json with order
        $tasksJson = [];
        foreach ($validated['tasks'] as $index => $task) {
            $tasksJson[] = [
                'title' => $task['title'],
                'priority' => $task['priority'],
                'order' => $index + 1,
                'role_id' => $task['role_id'] ?? null,
            ];
        }

        ServiceTemplate::create([
            'company_id' => 1,
            'product_id' => $validated['product_id'] ?? null,
            'name' => $validated['name'],
            'tasks_json' => $tasksJson,
            'is_active' => true,
        ]);

        return redirect()->route('admin.service-templates.index')
            ->with('success', 'Service template created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('settings.write')) {
            abort(403, 'Unauthorized action.');
        }

        $template = ServiceTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.priority' => 'required|in:low,medium,high',
            'tasks.*.role_id' => 'nullable|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Build tasks_json with order
        $tasksJson = [];
        foreach ($validated['tasks'] as $index => $task) {
            $tasksJson[] = [
                'title' => $task['title'],
                'priority' => $task['priority'],
                'order' => $index + 1,
                'role_id' => $task['role_id'] ?? null,
            ];
        }

        $template->update([
            'name' => $validated['name'],
            'product_id' => $validated['product_id'] ?? null,
            'tasks_json' => $tasksJson,
            'is_active' => $validated['is_active'] ?? $template->is_active,
        ]);

        return redirect()->route('admin.service-templates.index')
            ->with('success', 'Service template updated successfully');
    }

    public function destroy($id)
    {
        if (!can('settings.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $template = ServiceTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('admin.service-templates.index')
            ->with('success', 'Service template deleted successfully');
    }
}
