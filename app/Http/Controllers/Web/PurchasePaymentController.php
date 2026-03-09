<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PurchasePayment;
use App\Models\Setting;
use Illuminate\Http\Request;

class PurchasePaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!can('projects.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = PurchasePayment::with(['purchase.vendor', 'purchase.client']);

        // Date Range Filters
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Search Filter (Vendor Name, Client Name, or Purchase Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('purchase', function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('contact_name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    });
            });
        }

        // Payment Type Filter
        if ($request->filled('payment_type')) {
            $query->where('payment_method', $request->payment_type);
        }

        // Summary: total paid
        $totalPaid = (clone $query)->sum('amount') / 100;
        $payments = $query->latest('payment_date')->paginate(20)->withQueryString();

        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.purchase-payments.partials.payments_table_body', compact('payments'))->render(),
                'pagination' => (string) $payments->links(),
                'total_paid' => number_format($totalPaid, 2),
                'total_count' => $payments->total()
            ]);
        }

        return view('admin.purchase-payments.index', compact('payments', 'totalPaid', 'paymentTypes'));
    }

    private function updatePurchaseBalance($purchaseId)
    {
        $purchase = \App\Models\Purchase::find($purchaseId);
        if (!$purchase)
            return;

        $totalPaid = $purchase->payments()->sum('amount');
        $purchase->paid_amount = $totalPaid;

        if ($purchase->paid_amount >= $purchase->total_amount) {
            $purchase->status = 'completed';
        } elseif ($purchase->paid_amount > 0 && $purchase->paid_amount < $purchase->total_amount) {
            $purchase->status = 'active';
        } elseif ($purchase->paid_amount == 0 && $purchase->status === 'completed') {
            $purchase->status = 'active'; // Or draft, but active is safer
        }

        $purchase->save();
    }

    public function update(Request $request, $id)
    {
        if (!can('projects.write')) {
            abort(403, 'Unauthorized action.');
        }

        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => ['required', \Illuminate\Validation\Rule::in($paymentTypes)],
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = PurchasePayment::findOrFail($id);
        $amountPaise = (int) ($validated['amount'] * 100);

        $payment->update([
            'amount' => $amountPaise,
            'payment_method' => $validated['payment_type'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->updatePurchaseBalance($payment->purchase_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
            ]);
        }

        return redirect()->route('admin.purchase-payments.index')->with('success', 'Payment updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!can('projects.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $payment = PurchasePayment::findOrFail($id);
        $purchaseId = $payment->purchase_id;
        $payment->delete();

        $this->updatePurchaseBalance($purchaseId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ]);
        }

        return back()->with('success', 'Payment deleted successfully');
    }
}
