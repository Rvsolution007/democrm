<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuotePayment;
use App\Models\Quote;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = QuotePayment::with(['quote.client', 'quote.lead', 'user']);

        // Month Filter (Legacy)
        if ($request->filled('month')) {
            $monthYear = $request->month;
            $query->whereYear('payment_date', substr($monthYear, 0, 4))
                ->whereMonth('payment_date', substr($monthYear, 5, 2));
        }

        // Date Range Filters
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Search Filter (Client Name, Lead Name, or Quote Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('quote', function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('contact_name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        // User Filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Payment Type Filter
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Summary: total received
        $totalReceived = (clone $query)->sum('amount') / 100;

        $payments = $query->latest('payment_date')->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.payments.partials.payments_table_body', compact('payments'))->render(),
                'pagination' => (string) $payments->links(),
                'total_received' => number_format($totalReceived, 2),
                'total_count' => $payments->total()
            ]);
        }

        $users = (can('quotes.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('quotes')->orderBy('name')->get()
            : collect();

        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        return view('admin.payments.index', compact('payments', 'totalReceived', 'users', 'paymentTypes'));
    }

    public function store(Request $request)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        $validated = $request->validate([
            'quote_id' => 'required|exists:quotes,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => ['required', Rule::in($paymentTypes)],
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $quote = Quote::findOrFail($validated['quote_id']);

        // Convert rupees to paise
        $amountPaise = (int) ($validated['amount'] * 100);

        QuotePayment::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'amount' => $amountPaise,
            'payment_type' => $validated['payment_type'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            $paidTotal = $quote->paid_amount_in_rupees;
            $dueTotal = $quote->due_amount_in_rupees;
            return response()->json([
                'message' => 'Payment recorded successfully',
                'paid_amount' => $paidTotal,
                'due_amount' => $dueTotal,
            ]);
        }

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment recorded successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $payment = QuotePayment::findOrFail($id);
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => ['required', Rule::in($paymentTypes)],
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $amountPaise = (int) ($validated['amount'] * 100);

        $payment->update([
            'amount' => $amountPaise,
            'payment_type' => $validated['payment_type'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
            ]);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!can('quotes.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $payment = QuotePayment::findOrFail($id);
        $payment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ]);
        }

        return back()->with('success', 'Payment deleted successfully');
    }
}
