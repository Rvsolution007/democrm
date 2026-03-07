<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    /**
     * List all categories (flat or tree).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::forCompany($this->companyId());

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Tree structure (roots with children)
        if ($request->boolean('tree')) {
            $categories = $query->whereNull('parent_category_id')
                ->with('children')
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'data' => CategoryResource::collection($categories),
            ]);
        }

        // Flat list
        $sortBy = $request->get('sort', 'sort_order');
        $order = $request->get('order', 'asc');
        $query->orderBy($sortBy, $order);

        $perPage = min($request->get('per_page', 50), 100);
        $categories = $query->paginate($perPage);

        return response()->json($this->paginated($categories, CategoryResource));
    }

    /**
     * Get category tree.
     */
    public function tree(): JsonResponse
    {
        $categories = Category::forCompany($this->companyId())
            ->where('status', 'active')
            ->whereNull('parent_category_id')
            ->with(['children' => fn($q) => $q->where('status', 'active')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get single category.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::forCompany($this->companyId())
            ->with(['parent', 'children', 'products'])
            ->findOrFail($id);

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Create new category.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Validate parent belongs to same company
        if ($request->parent_category_id) {
            Category::forCompany($this->companyId())->findOrFail($request->parent_category_id);
        }

        $category = Category::create([
            'company_id' => $this->companyId(),
            'parent_category_id' => $request->parent_category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->image,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Update category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::forCompany($this->companyId())->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Prevent circular reference
        if ($request->parent_category_id == $id) {
            return $this->error('Category cannot be its own parent', 422);
        }

        $data = $request->only(['name', 'parent_category_id', 'description', 'image', 'sort_order', 'status']);

        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category->fresh(['parent', 'children'])),
        ]);
    }

    /**
     * Delete category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::forCompany($this->companyId())->findOrFail($id);

        // Check for children
        if ($category->children()->exists()) {
            return $this->error('Cannot delete category with sub-categories', 422);
        }

        // Check for products
        if ($category->products()->exists()) {
            return $this->error('Cannot delete category with products', 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
