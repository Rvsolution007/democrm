<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatflowStep;
use App\Models\CatalogueCustomColumn;
use Illuminate\Http\Request;

class ChatflowController extends Controller
{
    /**
     * Chatflow builder page
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $steps = ChatflowStep::where('company_id', $companyId)
            ->with('linkedColumn')
            ->orderBy('sort_order')
            ->get();

        // Get combo columns for linking to ask_combo steps
        $comboColumns = CatalogueCustomColumn::where('company_id', $companyId)
            ->where('is_combo', true)
            ->orderBy('sort_order')
            ->get();

        // Get ALL active catalogue columns for the new step types
        $allColumns = CatalogueCustomColumn::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Determine which column is the Unique column and which is the Base column
        $uniqueColumn = $allColumns->firstWhere('is_unique', true);
        $baseColumn = null;
        if ($uniqueColumn) {
            $baseColumn = $allColumns->where('sort_order', '<', $uniqueColumn->sort_order)->first();
        }

        return view('admin.chatflow.index', compact('steps', 'comboColumns', 'allColumns', 'uniqueColumn', 'baseColumn'));
    }

    /**
     * Store a new chatflow step (AJAX)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'step_type' => 'required|in:ask_category,ask_product,ask_base_column,ask_unique_column,ask_combo,ask_optional,ask_custom,send_summary',
            'linked_column_id' => 'nullable|exists:catalogue_custom_columns,id',
            'question_text' => 'nullable|string|max:500',
            'media_path' => 'nullable|string|max:500',
            'field_key' => 'nullable|string|max:100',
            'is_optional' => 'nullable',
            'max_retries' => 'nullable|integer|min:1|max:5',
        ]);

        $companyId = auth()->user()->company_id;

        // Validation: ask_combo must have linked_column_id
        if ($request->step_type === 'ask_combo' && !$request->linked_column_id) {
            return response()->json([
                'success' => false,
                'message' => 'Combo step requires a linked column.',
            ], 422);
        }

        // Validation: ask_optional must have field_key
        if ($request->step_type === 'ask_optional' && !$request->field_key) {
            return response()->json([
                'success' => false,
                'message' => 'Optional step requires a field key (e.g., "city", "name").',
            ], 422);
        }

        $maxOrder = ChatflowStep::where('company_id', $companyId)->max('sort_order') ?? 0;

        $step = ChatflowStep::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'step_type' => $request->step_type,
            'linked_column_id' => $request->linked_column_id,
            'question_text' => $request->question_text,
            'media_path' => $request->media_path,
            'field_key' => $request->field_key,
            'is_optional' => $request->boolean('is_optional'),
            'max_retries' => $request->max_retries ?? 2,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Step added successfully',
            'step' => $step->load('linkedColumn'),
        ]);
    }

    /**
     * Update a chatflow step (AJAX)
     */
    public function update(Request $request, $id)
    {
        $step = ChatflowStep::where('company_id', auth()->user()->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'step_type' => 'required|in:ask_category,ask_product,ask_base_column,ask_unique_column,ask_combo,ask_optional,ask_custom,send_summary',
            'linked_column_id' => 'nullable|exists:catalogue_custom_columns,id',
            'question_text' => 'nullable|string|max:500',
            'media_path' => 'nullable|string|max:500',
            'field_key' => 'nullable|string|max:100',
            'is_optional' => 'nullable',
            'max_retries' => 'nullable|integer|min:1|max:5',
        ]);

        $step->update([
            'name' => $request->name,
            'step_type' => $request->step_type,
            'linked_column_id' => $request->linked_column_id,
            'question_text' => $request->question_text,
            'media_path' => $request->media_path,
            'field_key' => $request->field_key,
            'is_optional' => $request->boolean('is_optional'),
            'max_retries' => $request->max_retries ?? 2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Step updated successfully',
            'step' => $step->fresh()->load('linkedColumn'),
        ]);
    }

    /**
     * Delete a chatflow step (AJAX)
     */
    public function destroy($id)
    {
        $step = ChatflowStep::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $step->delete();

        return response()->json([
            'success' => true,
            'message' => 'Step deleted successfully',
        ]);
    }

    /**
     * Reorder chatflow steps (AJAX)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($request->order as $index => $id) {
            ChatflowStep::where('company_id', auth()->user()->company_id)
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Flow order updated']);
    }
}
