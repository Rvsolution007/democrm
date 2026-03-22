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

        $query = Product::with(['category', 'customValues']);

        // Global permission filter
        if (!can('products.global')) {
            $query->where('created_by_user_id', auth()->id());
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::orderBy('name')->get();
        
        $customColumns = \App\Models\CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
            ->where('is_combo', false)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.products.index', compact('products', 'categories', 'customColumns'));
    }

    /**
     * Build validation rules based on dynamic Catalogue Custom Columns.
     */
    private function getValidationRules(?int $productId = null): array
    {
        $companyId = auth()->user()->company_id;
        $skuRule = 'required|string|max:50|unique:products,sku,' . ($productId ?? 'NULL') . ',id,company_id,' . $companyId;

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'sku' => $skuRule,
            'is_purchase_enabled' => 'nullable|boolean',
        ];
        
        $customColumns = \App\Models\CatalogueCustomColumn::where('company_id', $companyId)
            ->where('is_combo', false)
            ->where('is_active', true)
            ->get();
            
        foreach ($customColumns as $col) {
            $rule = [];
            if ($col->is_required) $rule[] = 'required';
            else $rule[] = 'nullable';

            if ($col->type === 'number') $rule[] = 'numeric';
            if ($col->type === 'boolean') $rule[] = 'boolean';

            if ($col->slug === 'name') $rule[] = 'max:255';
            if ($col->is_system) {
                if (in_array($col->slug, ['mrp', 'sale_price', 'gst_percent'])) {
                    $rule[] = 'min:0';
                }
                $rules[$col->slug] = implode('|', $rule);
            } else {
                $rules['custom_data.' . $col->id] = implode('|', $rule);
            }
        }

        return $rules;
    }
    
    /**
     * Inject default values for inactive system columns to satisfy DB constraints
     */
    private function injectSystemDefaults(array &$validated)
    {
        $companyId = auth()->user()->company_id;
        $systemColumns = \App\Models\CatalogueCustomColumn::where('company_id', $companyId)
            ->where('is_system', true)
            ->get();
            
        foreach ($systemColumns as $col) {
            if (!$col->is_active && !array_key_exists($col->slug, $validated)) {
                if ($col->type === 'number') {
                    $validated[$col->slug] = 0;
                } elseif ($col->slug === 'name') {
                    $validated[$col->slug] = 'Unnamed Product';
                } else {
                    $validated[$col->slug] = null;
                }
            }
        }
    }

    public function store(Request $request)
    {
        if (!can('products.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate($this->getValidationRules());
        
        $this->injectSystemDefaults($validated);

        // Convert rupees to paise
        if (isset($validated['mrp'])) $validated['mrp'] = $validated['mrp'] * 100;
        if (isset($validated['sale_price'])) $validated['sale_price'] = $validated['sale_price'] * 100;

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_purchase_enabled'] = $request->has('is_purchase_enabled');

        $product = Product::create($validated);
        
        // Save Custom Data
        if ($request->has('custom_data')) {
            foreach ($request->custom_data as $columnId => $value) {
                if ($value !== null && $value !== '') {
                    \App\Models\CatalogueCustomValue::create([
                        'product_id' => $product->id,
                        'column_id' => $columnId,
                        'value' => is_array($value) ? json_encode($value) : $value,
                    ]);
                }
            }
        }

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

        $validated = $request->validate($this->getValidationRules((int) $id));
        
        $this->injectSystemDefaults($validated);

        // Convert rupees to paise
        if (isset($validated['mrp'])) $validated['mrp'] = $validated['mrp'] * 100;
        if (isset($validated['sale_price'])) $validated['sale_price'] = $validated['sale_price'] * 100;

        $validated['is_purchase_enabled'] = $request->has('is_purchase_enabled');

        $product->update($validated);
        
        // Update Custom Data
        if ($request->has('custom_data')) {
            foreach ($request->custom_data as $columnId => $value) {
                if ($value === null || $value === '') {
                    \App\Models\CatalogueCustomValue::where('product_id', $product->id)
                        ->where('column_id', $columnId)
                        ->delete();
                } else {
                    \App\Models\CatalogueCustomValue::updateOrCreate(
                        ['product_id' => $product->id, 'column_id' => $columnId],
                        ['value' => is_array($value) ? json_encode($value) : $value]
                    );
                }
            }
        }

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
