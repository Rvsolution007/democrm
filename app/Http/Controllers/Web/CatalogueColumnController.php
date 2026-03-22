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
            'is_combo' => 'nullable',
        ]);

        $companyId = auth()->user()->company_id;
        $slug = Str::slug($request->name, '_');

        // Ensure unique slug
        $existingSlug = CatalogueCustomColumn::where('company_id', $companyId)
            ->where('slug', $slug)
            ->exists();
        if ($existingSlug) {
            return response()->json([
                'success' => false,
                'message' => "A column with slug '{$slug}' already exists.",
            ], 422);
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

        // If is_unique, unset any existing unique column
        if ($request->boolean('is_unique')) {
            CatalogueCustomColumn::where('company_id', $companyId)
                ->where('is_unique', true)
                ->update(['is_unique' => false]);
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
            'is_combo' => $request->boolean('is_combo'),
            'sort_order' => $maxOrder + 1,
        ]);

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
            'is_combo' => 'nullable',
        ]);

        // Parse options
        $options = null;
        if (in_array($request->type, ['select', 'multiselect']) && $request->options) {
            $options = array_map('trim', explode(',', $request->options));
            $options = array_filter($options);
            $options = array_values($options);
        }

        // If setting as unique, unset others
        if ($request->boolean('is_unique')) {
            CatalogueCustomColumn::where('company_id', auth()->user()->company_id)
                ->where('id', '!=', $id)
                ->where('is_unique', true)
                ->update(['is_unique' => false]);
        }

        $column->update([
            'name' => $request->name,
            'type' => $request->type,
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_unique' => $request->boolean('is_unique'),
            'is_combo' => $request->boolean('is_combo'),
        ]);

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
        $column->delete();

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
}
