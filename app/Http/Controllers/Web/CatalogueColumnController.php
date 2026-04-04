<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogueCustomColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogueColumnController extends Controller
{
    /**
     * List all custom columns
     */
    public function index()
    {
        $columns = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.catalogue.custom-columns', compact('columns'));
    }

    /**
     * Store a new custom column (AJAX)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,select,multiselect,boolean',
            'options' => 'nullable|string', // Comma-separated for select/multiselect
            'is_required' => 'nullable',
            'is_unique' => 'nullable',
            'is_category' => 'nullable',
            'is_title' => 'nullable',
            'is_combo' => 'nullable',
            'show_on_list' => 'nullable|boolean',
        ]);

        $companyId = auth()->user()->company_id;
        $baseSlug = Str::slug($request->name, '_');
        $slug = $baseSlug;
        $counter = 1;

        // Ensure unique slug
        while (CatalogueCustomColumn::where('company_id', $companyId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}_{$counter}";
            $counter++;
        }

        // Check combo limit (max 5)
        if ($request->boolean('is_combo')) {
            $comboCount = CatalogueCustomColumn::where('company_id', $companyId)
                ->where('is_combo', true)
                ->count();
            if ($comboCount >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 5 combo columns allowed.',
                ], 422);
            }
        }

        // Parse options
        $options = null;
        if (in_array($request->type, ['select', 'multiselect']) && $request->options) {
            $options = array_map('trim', explode(',', $request->options));
            $options = array_filter($options);
            $options = array_values($options);
        }

        // If is_unique, block if another unique already exists
        if ($request->boolean('is_unique')) {
            $existing = CatalogueCustomColumn::where('company_id', $companyId)
                ->where('is_unique', true)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existing->name}' is already the Unique Identifier. Remove its unique flag first before assigning a new one.",
                ], 422);
            }
        }

        // If is_category, block if another category already exists
        if ($request->boolean('is_category')) {
            $existingCat = CatalogueCustomColumn::where('company_id', $companyId)
                ->where('is_category', true)
                ->first();
            if ($existingCat) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existingCat->name}' is already the Category Link. Remove its category flag first before assigning a new one.",
                ], 422);
            }
        }

        // If is_title, block if another title already exists
        if ($request->boolean('is_title')) {
            $existingTitle = CatalogueCustomColumn::where('company_id', $companyId)
                ->where('is_title', true)
                ->first();
            if ($existingTitle) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existingTitle->name}' is already the Title column. Remove its title flag first before assigning a new one.",
                ], 422);
            }
        }

        $maxOrder = CatalogueCustomColumn::where('company_id', $companyId)->max('sort_order') ?? 0;

        $column = CatalogueCustomColumn::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_unique' => $request->boolean('is_unique'),
            'is_category' => $request->boolean('is_category'),
            'is_title' => $request->boolean('is_title'),
            'is_combo' => $request->boolean('is_combo'),
            'show_on_list' => $request->boolean('show_on_list'),
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        // Clear Product Group Match cache
        \App\Services\AIChatbotService::clearProductGroupCache($companyId);

        return response()->json([
            'success' => true,
            'message' => 'Column created successfully',
            'column' => $column,
        ]);
    }

    /**
     * Update a custom column (AJAX)
     */
    public function update(Request $request, $id)
    {
        $column = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,select,multiselect,boolean',
            'options' => 'nullable|string',
            'is_required' => 'nullable',
            'is_unique' => 'nullable',
            'is_category' => 'nullable',
            'is_title' => 'nullable',
            'is_combo' => 'nullable',
            'show_on_list' => 'nullable|boolean',
        ]);

        // Parse options
        $options = null;
        if (in_array($request->type, ['select', 'multiselect']) && $request->options) {
            $options = array_map('trim', explode(',', $request->options));
            $options = array_filter($options);
            $options = array_values($options);
        }

        // If setting as unique, block if another unique already exists
        if ($request->boolean('is_unique')) {
            $existing = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
                ->where('id', '!=', $id)
                ->where('is_unique', true)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existing->name}' is already the Unique Identifier. Remove its unique flag first before assigning a new one.",
                ], 422);
            }
        }

        // If setting as category, block if another category already exists
        if ($request->boolean('is_category')) {
            $existingCat = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
                ->where('id', '!=', $id)
                ->where('is_category', true)
                ->first();
            if ($existingCat) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existingCat->name}' is already the Category Link. Remove its category flag first before assigning a new one.",
                ], 422);
            }
        }

        // If setting as title, block if another title already exists
        if ($request->boolean('is_title')) {
            $existingTitle = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
                ->where('id', '!=', $id)
                ->where('is_title', true)
                ->first();
            if ($existingTitle) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$existingTitle->name}' is already the Title column. Remove its title flag first before assigning a new one.",
                ], 422);
            }
        }

        // If it is a system column, restrict what can be updated
        if ($column->is_system) {
            $column->update([
                'name' => $request->name,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $column->is_active,
                'is_title' => $request->has('is_title') ? $request->boolean('is_title') : $column->is_title,
                'show_on_list' => $request->boolean('show_on_list'),
            ]);
            \App\Services\AIChatbotService::clearProductGroupCache(auth()->user()->company_id);
            return response()->json(['success' => true, 'message' => 'System field updated successfully', 'column' => $column]);
        }

        $column->update([
            'name' => $request->name,
            'type' => $request->type,
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_unique' => $request->boolean('is_unique'),
            'is_category' => $request->boolean('is_category'),
            'is_title' => $request->boolean('is_title'),
            'is_combo' => $request->boolean('is_combo'),
            'show_on_list' => $request->boolean('show_on_list'),
        ]);

        // Clear Product Group Match cache
        \App\Services\AIChatbotService::clearProductGroupCache(auth()->user()->company_id);

        return response()->json([
            'success' => true,
            'message' => 'Column updated successfully',
            'column' => $column->fresh(),
        ]);
    }

    /**
     * Delete a custom column (AJAX)
     */
    public function destroy($id)
    {
        $column = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)->findOrFail($id);
        
        if ($column->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System columns cannot be deleted. You can disable them instead.',
            ], 403);
        }
        
        $column->delete();

        // Clear Product Group Match cache
        \App\Services\AIChatbotService::clearProductGroupCache(auth()->user()->company_id);

        return response()->json([
            'success' => true,
            'message' => 'Column deleted successfully',
        ]);
    }

    /**
     * Reorder columns (AJAX)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($request->order as $index => $id) {
            CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }
    
    /**
     * Toggle active state (AJAX)
     */
    public function toggleActive($id, Request $request)
    {
        $column = CatalogueCustomColumn::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $column->update(['is_active' => $request->boolean('is_active')]);
        
        return response()->json([
            'success' => true,
            'message' => 'Column visibility updated successfully',
        ]);
    }
}
