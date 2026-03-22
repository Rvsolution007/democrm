<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorCustomField;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        if (!can('projects.global') && !can('quotes.global')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Vendor::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%");
        }

        $vendors = $query->latest()->paginate(20);
        return view('admin.vendors.index', compact('vendors'));
    }

    public function store(Request $request)
    {
        if (!can('projects.global') && !can('quotes.global')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['has_purchase_section'] = $request->has('has_purchase_section') ? true : false;
        Vendor::create($validated);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('projects.global') && !can('quotes.global')) {
            abort(403, 'Unauthorized action.');
        }

        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['has_purchase_section'] = $request->has('has_purchase_section') ? true : false;
        $vendor->update($validated);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully');
    }

    public function destroy($id)
    {
        if (!can('projects.global') && !can('quotes.global')) {
            abort(403, 'Unauthorized action.');
        }

        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully'
            ]);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully');
    }

    // ====== Custom Fields AJAX ======

    public function getCustomFields($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $fields = $vendor->customFields()->orderBy('sort_order')->get();
        return response()->json(['fields' => $fields]);
    }

    public function storeCustomField(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,select,date',
            'field_options' => 'nullable|string', // comma-separated for select
        ]);

        $options = null;
        if ($validated['field_type'] === 'select' && !empty($validated['field_options'])) {
            $options = array_map('trim', explode(',', $validated['field_options']));
        }

        $maxSort = VendorCustomField::where('vendor_id', $validated['vendor_id'])->max('sort_order') ?? 0;

        $field = VendorCustomField::create([
            'vendor_id' => $validated['vendor_id'],
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'field_options' => $options,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['success' => true, 'field' => $field]);
    }

    public function deleteCustomField($id)
    {
        $field = VendorCustomField::findOrFail($id);
        $field->values()->delete(); // Delete all associated values
        $field->delete();

        return response()->json(['success' => true]);
    }
}