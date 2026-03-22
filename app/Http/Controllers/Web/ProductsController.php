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

        $query = Product::with(['category', 'customValues', 'variations']);

        // Global permission filter
        if (!can('products.global')) {
            $query->where('created_by_user_id', auth()->id());
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::orderBy('name')->get();
        
        $customColumns = \App\Models\CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
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

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'is_purchase_enabled' => 'nullable|boolean',
        ];
        
        $customColumns = \App\Models\CatalogueCustomColumn::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();
            
        foreach ($customColumns as $col) {
            $rule = [];
            if ($col->is_required) $rule[] = 'required';
            else $rule[] = 'nullable';

            if ($col->type === 'number') $rule[] = 'numeric';
            if ($col->type === 'boolean') $rule[] = 'boolean';

            if ($col->slug === 'name') $rule[] = 'max:255';
            
            if ($col->slug === 'sku') {
                $rule[] = 'max:50';
                $rule[] = "unique:products,sku," . ($productId ?? 'NULL') . ",id,company_id," . $companyId;
            }
            
            if ($col->is_system) {
                if (in_array($col->slug, ['mrp', 'sale_price', 'gst_percent'])) {
                    $rule[] = 'min:0';
                }
                $rules[$col->slug] = implode('|', $rule);
            } else {
                if ($col->is_unique) {
                    $rule[] = function ($attribute, $value, $fail) use ($col, $productId, $companyId) {
                        $query = \App\Models\CatalogueCustomValue::where('column_id', $col->id)->where('value', $value)
                            ->whereHas('product', function($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            });
                        if ($productId) {
                            $query->where('product_id', '!=', $productId);
                        }
                        if ($query->exists()) {
                            $fail("The {$col->name} has already been taken.");
                        }
                    };
                }
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
        // These DB columns must ALWAYS have values, regardless of DFM configuration
        $coreDefaults = [
            'name'        => $validated['name'] ?? 'Unnamed Product',
            'sku'         => $validated['sku'] ?? 'AUTO-' . strtoupper(uniqid()),
            'description' => $validated['description'] ?? null,
            'sale_price'  => $validated['sale_price'] ?? 0,
            'mrp'         => $validated['mrp'] ?? 0,
            'gst_percent'  => $validated['gst_percent'] ?? 0,
            'hsn_code'    => $validated['hsn_code'] ?? null,
            'unit'        => $validated['unit'] ?? null,
        ];
        
        foreach ($coreDefaults as $key => $default) {
            if (!array_key_exists($key, $validated)) {
                $validated[$key] = $default;
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
        
        // Save Combo Data (selected options per combo column)
        $this->saveComboData($request, $product);
        
        // Save Variations from the combo matrix
        $this->saveVariations($request, $product);

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
        
        // Update Combo Data
        $this->saveComboData($request, $product);
        
        // Update Variations
        $this->saveVariations($request, $product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully');
    }
    
    /**
     * Save combo selections as CatalogueCustomValues (JSON arrays)
     */
    private function saveComboData(Request $request, Product $product)
    {
        if (!$request->has('combo_data')) return;
        
        foreach ($request->combo_data as $columnId => $values) {
            $values = array_filter((array) $values);
            if (count($values) > 0) {
                \App\Models\CatalogueCustomValue::updateOrCreate(
                    ['product_id' => $product->id, 'column_id' => $columnId],
                    ['value' => json_encode(array_values($values))]
                );
                
                // Also save to product_combos for structured access
                \App\Models\ProductCombo::updateOrCreate(
                    ['product_id' => $product->id, 'column_id' => $columnId],
                    ['selected_values' => array_values($values)]
                );
            } else {
                \App\Models\CatalogueCustomValue::where('product_id', $product->id)
                    ->where('column_id', $columnId)->delete();
                \App\Models\ProductCombo::where('product_id', $product->id)
                    ->where('column_id', $columnId)->delete();
            }
        }
    }
    
    /**
     * Save variations from the combo matrix
     */
    private function saveVariations(Request $request, Product $product)
    {
        if (!$request->has('variations')) {
            return;
        }
        
        // Remove old variations
        $product->variations()->delete();
        
        foreach ($request->variations as $varData) {
            if (!isset($varData['combination']) || empty($varData['combination'])) continue;
            
            $combination = $varData['combination'];
            $key = \App\Models\ProductVariation::generateKey($combination);
            $price = isset($varData['price']) && $varData['price'] !== '' ? round($varData['price'] * 100) : 0;
            $discount = isset($varData['discount']) && $varData['discount'] !== '' ? $varData['discount'] : 0;
            
            \App\Models\ProductVariation::create([
                'product_id' => $product->id,
                'combination' => $combination,
                'combination_key' => $key,
                'price' => $price,
                'discount' => $discount,
            ]);
        }
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
