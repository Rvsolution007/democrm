<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index()
    {
        if (!can('products.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Product::with('category');

        // Global permission filter
        if (!can('products.global')) {
            $query->where('created_by_user_id', auth()->id());
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::all();
        $columnVisibility = Setting::getValue('column_visibility', 'products', []);
        return view('admin.products.index', compact('products', 'categories', 'columnVisibility'));
    }

    /**
     * Build validation rules based on column visibility settings.
     * Hidden (unchecked) columns become nullable instead of required.
     */
    private function getValidationRules(): array
    {
        $vis = Setting::getValue('column_visibility', 'products', []);

        // Helper: returns 'nullable' if column is hidden, otherwise the given rule
        $r = function (string $col, string $default) use ($vis) {
            return (isset($vis[$col]) && $vis[$col] === false) ? 'nullable' : $default;
        };

        return [
            'category_id' => $r('category', 'required') . '|exists:categories,id',
            'name' => 'required|string|max:255',  // always required (name column is locked)
            'sku' => $r('sku', 'required') . '|string|max:50',
            'description' => 'nullable|string',
            'unit' => $r('unit', 'required') . '|string',
            'mrp' => $r('mrp', 'required') . '|numeric|min:0',
            'sale_price' => $r('sale_price', 'required') . '|numeric|min:0',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
            'is_purchase_enabled' => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        if (!can('products.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate($this->getValidationRules());

        // Convert rupees to paise
        if (isset($validated['mrp'])) {
            $validated['mrp'] = $validated['mrp'] * 100;
        }
        if (isset($validated['sale_price'])) {
            $validated['sale_price'] = $validated['sale_price'] * 100;
        }
        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_purchase_enabled'] = $request->has('is_purchase_enabled');

        // Filter out empty micro tasks
        if (isset($validated['micro_tasks']) && is_array($validated['micro_tasks'])) {
            $validated['micro_tasks'] = array_values(array_filter($validated['micro_tasks'], function ($task) {
                return !empty(trim($task));
            }));
        }

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('products.write')) {
            abort(403, 'Unauthorized action.');
        }

        $product = Product::findOrFail($id);

        // Non-global users can only update their own products
        if (!can('products.global') && $product->created_by_user_id != auth()->id()) {
            abort(403, 'You can only edit your own products.');
        }

        $validated = $request->validate($this->getValidationRules());

        // Convert rupees to paise
        if (isset($validated['mrp'])) {
            $validated['mrp'] = $validated['mrp'] * 100;
        }
        if (isset($validated['sale_price'])) {
            $validated['sale_price'] = $validated['sale_price'] * 100;
        }

        $validated['is_purchase_enabled'] = $request->has('is_purchase_enabled');

        // Filter out empty micro tasks
        if (isset($validated['micro_tasks']) && is_array($validated['micro_tasks'])) {
            $validated['micro_tasks'] = array_values(array_filter($validated['micro_tasks'], function ($task) {
                return !empty(trim($task));
            }));
        } else {
            $validated['micro_tasks'] = null;
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        if (!can('products.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $product = Product::findOrFail($id);

        // Non-global users can only delete their own products
        if (!can('products.global') && $product->created_by_user_id != auth()->id()) {
            abort(403, 'You can only delete your own products.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully');
    }
}
