<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    public function index()
    {
        if (!can('categories.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Category::withCount('products')->with([
            'children' => function ($q) {
                $q->withCount('products')->orderBy('sort_order');
            }
        ]);

        // Global permission filter
        if (!can('categories.global')) {
            $query->where('created_by_user_id', auth()->id());
        }

        // Get root categories (no parent) for hierarchical display
        $categories = $query->whereNull('parent_category_id')->orderBy('sort_order')->get();

        // Get all categories flat list for parent dropdown
        $allCategories = Category::orderBy('name')->get();

        $columnVisibility = Setting::getValue('column_visibility', 'categories', []);
        return view('admin.categories.index', compact('categories', 'allCategories', 'columnVisibility'));
    }

    /**
     * Build validation rules based on column visibility settings.
     * Hidden (unchecked) columns become nullable instead of required.
     */
    private function getValidationRules(): array
    {
        $vis = Setting::getValue('column_visibility', 'categories', []);

        $r = function (string $col, string $default) use ($vis) {
            return (isset($vis[$col]) && $vis[$col] === false) ? 'nullable' : $default;
        };

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_category_id' => 'nullable|exists:categories,id',
            'status' => $r('status', 'required') . '|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function store(Request $request)
    {
        if (!can('categories.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate($this->getValidationRules());

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('categories.write')) {
            abort(403, 'Unauthorized action.');
        }

        $category = Category::findOrFail($id);

        // Non-global users can only update their own categories
        if (!can('categories.global') && $category->created_by_user_id != auth()->id()) {
            abort(403, 'You can only edit your own categories.');
        }

        $validated = $request->validate($this->getValidationRules());

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy($id)
    {
        if (!can('categories.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = Category::findOrFail($id);

        // Non-global users can only delete their own categories
        if (!can('categories.global') && $category->created_by_user_id != auth()->id()) {
            abort(403, 'You can only delete your own categories.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully');
    }
}
