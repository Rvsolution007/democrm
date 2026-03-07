<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseCustomFieldValue;
use App\Models\PurchasePayment;
use App\Models\Vendor;
use App\Models\VendorCustomField;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if (!can('projects.read')) {
            abort(403, 'Unauthorized action.');
        }

        // All purchases
        $allPurchases = Purchase::with(['vendor', 'client', 'project', 'product', 'customFieldValues.customField'])->latest()->get();

        // Summary amounts (All Purchases)
        $allTotalAmount = $allPurchases->sum('total_amount') / 100;
        $allPaidAmount = $allPurchases->sum('paid_amount') / 100;
        $allDueAmount = $allTotalAmount - $allPaidAmount;

        // Dynamic vendor sections: vendors with has_purchase_section = true
        $sectionVendors = Vendor::where('has_purchase_section', true)->with('customFields')->get();
        $vendorSections = [];
        foreach ($sectionVendors as $sv) {
            $vendorPurchases = Purchase::with(['vendor', 'client', 'project', 'product', 'customFieldValues.customField'])
                ->where('vendor_id', $sv->id)
                ->latest()->get();
            $vTotal = $vendorPurchases->sum('total_amount') / 100;
            $vPaid = $vendorPurchases->sum('paid_amount') / 100;
            $vendorSections[] = [
                'vendor' => $sv,
                'customFields' => $sv->customFields,
                'purchases' => $vendorPurchases,
                'total_amount' => $vTotal,
                'due_amount' => $vTotal - $vPaid,
            ];
        }

        $vendors = Vendor::where('status', 'active')->get();
        $clients = \App\Models\Client::where('status', 'active')->get();
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer'], 1);

        return view('admin.purchases.index', compact(
            'allPurchases',
            'allTotalAmount',
            'allDueAmount',
            'vendorSections',
            'vendors',
            'clients',
            'paymentTypes'
        ));
    }

    /**
     * AJAX search endpoint for purchases
     */
    public function search(Request $request)
    {
        if (!can('projects.read')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Purchase::with(['vendor', 'client', 'customFieldValues.customField']);

        // Vendor-specific tab filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('business_name', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $purchases = $query->latest()->get();

        // Build a custom_field_values_map for each purchase: { field_id: value }
        $purchases->each(function ($p) {
            $cfvMap = [];
            foreach ($p->customFieldValues as $cfv) {
                $cfvMap[$cfv->vendor_custom_field_id] = $cfv->value;
            }
            $p->cf_values = $cfvMap;
        });

        // Load custom fields if vendor_id is provided
        $customFields = [];
        if ($request->filled('vendor_id')) {
            $customFields = VendorCustomField::where('vendor_id', $request->vendor_id)->orderBy('sort_order')->get();
        }

        $totalAmount = $purchases->sum('total_amount') / 100;
        $paidAmount = $purchases->sum('paid_amount') / 100;
        $dueAmount = $totalAmount - $paidAmount;

        return response()->json([
            'purchases' => $purchases,
            'custom_fields' => $customFields,
            'count' => $purchases->count(),
            'total_amount' => $totalAmount,
            'due_amount' => $dueAmount,
        ]);
    }

    public function store(Request $request)
    {
        // For manually creating a purchase
        if (!can('projects.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,active,completed'
        ]);

        $company = \App\Models\Company::find(1);

        $purchase = Purchase::create([
            'company_id' => $company->id,
            'vendor_id' => $validated['vendor_id'],
            'client_id' => $request->client_id,
            'quote_id' => $request->quote_id,
            'purchase_no' => Purchase::generatePurchaseNumber($company),
            'date' => $validated['purchase_date'],
            'total_amount' => $validated['total_amount'] * 100,
            'paid_amount' => 0,
            'status' => $validated['status'],
            'notes' => $validated['notes']
        ]);

        // Save custom field values
        $this->saveCustomFieldValues($purchase, $request);

        $activeTab = 'vendor_' . $validated['vendor_id'];
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase created successfully')->with('active_tab', $activeTab);
    }

    public function update(Request $request, $id)
    {
        if (!can('projects.write')) {
            abort(403, 'Unauthorized action.');
        }

        $purchase = Purchase::findOrFail($id);

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,active,completed'
        ]);

        $purchase->update([
            'vendor_id' => $validated['vendor_id'],
            'client_id' => $request->client_id,
            'date' => $validated['purchase_date'],
            'total_amount' => $validated['total_amount'] * 100,
            'status' => $validated['status'],
            'notes' => $validated['notes']
        ]);

        // Save custom field values
        $this->saveCustomFieldValues($purchase, $request);

        $activeTab = 'vendor_' . $validated['vendor_id'];
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase updated successfully')->with('active_tab', $activeTab);
    }

    public function destroy($id)
    {
        if (!can('projects.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $purchase = Purchase::findOrFail($id);
        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase deleted successfully');
    }

    public function addPayment(Request $request, $id)
    {
        if (!can('projects.write')) {
            abort(403, 'Unauthorized action.');
        }

        $purchase = Purchase::findOrFail($id);
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer'], 1);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_type' => ['required', Rule::in($paymentTypes)],
            'reference_no' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $amountPaise = $validated['amount'] * 100;

        $payment = PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $amountPaise,
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_type'],
            'payment_type' => $validated['payment_type'], // Save to new column if created, or use method for now
            'reference_no' => $validated['reference_no'],
            'notes' => $validated['notes']
        ]);

        $purchase->increment('paid_amount', $amountPaise);

        if ($purchase->paid_amount >= $purchase->total_amount && $purchase->status !== 'completed') {
            $purchase->update(['status' => 'completed']);
        } elseif ($purchase->paid_amount > 0 && $purchase->status === 'draft') {
            $purchase->update(['status' => 'active']);
        }

        return redirect()->back()->with('success', 'Payment added successfully');
    }

    public function show($id)
    {
        if (!can('projects.read')) {
            abort(403, 'Unauthorized action.');
        }

        $purchase = Purchase::with(['vendor', 'client', 'project', 'product', 'payments', 'customFieldValues.customField'])->findOrFail($id);
        return response()->json($purchase);
    }

    /**
     * Save custom field values for a purchase
     */
    private function saveCustomFieldValues(Purchase $purchase, Request $request)
    {
        $customFieldData = $request->input('custom_fields', []);
        if (!is_array($customFieldData) || empty($customFieldData))
            return;

        foreach ($customFieldData as $fieldId => $value) {
            PurchaseCustomFieldValue::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'vendor_custom_field_id' => $fieldId,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    /**
     * AJAX: Get custom fields for a vendor (used by purchase modal)
     */
    public function getVendorCustomFields($vendorId)
    {
        $fields = VendorCustomField::where('vendor_id', $vendorId)->orderBy('sort_order')->get();
        return response()->json(['fields' => $fields]);
    }
}
