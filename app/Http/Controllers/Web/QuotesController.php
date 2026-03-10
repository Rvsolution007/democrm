<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteItem;
use App\Models\Company;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;

class QuotesController extends Controller
{
    public function index(Request $request)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Quote::with(['client', 'lead', 'assignedTo']);

        // Global permission filter
        if (!can('quotes.global')) {
            $query->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhere('assigned_to_user_id', auth()->id());
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // User Filter
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->assigned_to_user_id);
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Start Date filter
        if ($request->filled('start_date')) {
            $query->whereDate('quote_date', '>=', $request->start_date);
        }

        // Due/Valid Until Date filter
        if ($request->filled('due_date')) {
            $query->whereDate('valid_until', '<=', $request->due_date);
        }

        // Leads summary queries (Quotes tab)
        $leadSummaryQuery = clone $query;
        $leadTotalAmount = $leadSummaryQuery->whereNull('client_id')->sum('grand_total') / 100;

        $leadDueQuery = clone $query;
        $leadDueAmount = $leadDueQuery->whereNull('client_id')->where('status', '!=', 'accepted')->sum('grand_total') / 100;

        // Clients summary queries (Converted Quotes tab)
        $clientSummaryQuery = clone $query;
        $clientTotalAmount = $clientSummaryQuery->whereNotNull('client_id')->sum('grand_total') / 100;

        $clientDueQuery = clone $query;
        $clientDueAmount = $clientDueQuery->whereNotNull('client_id')->where('status', '!=', 'accepted')->sum('grand_total') / 100;

        $leadQuery = clone $query;
        $clientQuery = clone $query;

        $leadQuotes = $leadQuery->whereNull('client_id')->latest()->paginate(20, ['*'], 'lead_page')->withQueryString();
        $clientQuotes = $clientQuery->whereNotNull('client_id')->with('payments')->latest()->paginate(20, ['*'], 'client_page')->withQueryString();
        $clients = Client::all();
        $products = Product::all();

        $leadsQuery = Lead::query();
        if (!can('leads.global')) {
            $leadsQuery->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhere('assigned_to_user_id', auth()->id());
            });
        }
        $leads = $leadsQuery->orderBy('name')->get();

        $users = (can('quotes.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('quotes')->where('id', '!=', 1)->where('id', '!=', auth()->id())->orderBy('name')->get()
            : collect();

        $quoteTaxes = Setting::getValue('quotes', 'taxes', []);
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        return view('admin.quotes.index', compact('leadQuotes', 'clientQuotes', 'clients', 'products', 'leads', 'users', 'quoteTaxes', 'paymentTypes', 'leadTotalAmount', 'leadDueAmount', 'clientTotalAmount', 'clientDueAmount'));
    }

    public function store(Request $request)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'client_type' => 'required|in:client,lead',
            'client_id' => 'required_if:client_type,client|nullable|exists:clients,id',
            'lead_id' => 'required_if:client_type,lead|nullable|exists:leads,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate quote number
        $company = auth()->user()->company;
        $quoteNumber = Quote::generateQuoteNumber($company);

        // Convert rupees to paise (model stores in paise)
        $subtotal = (int) (($validated['subtotal'] ?? 0) * 100);
        $discount = (int) (($validated['discount'] ?? 0) * 100);
        $taxAmount = (int) (($validated['tax_amount'] ?? 0) * 100);
        $grandTotal = $subtotal - $discount + $taxAmount;

        // Only global/admin users can assign quotes to others
        $assignedTo = auth()->id();
        if ((can('quotes.global') || auth()->user()->isAdmin()) && !empty($validated['assigned_to_user_id'])) {
            $assignedTo = $validated['assigned_to_user_id'];
        }

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        Quote::create([
            'company_id' => auth()->user()->company_id,
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'created_by_user_id' => auth()->id(),
            'assigned_to_user_id' => $assignedTo,
            'quote_no' => $quoteNumber,
            'date' => $validated['quote_date'],
            'valid_till' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gst_total' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        $quote = Quote::latest()->first();

        // Create QuoteItems from selected products
        if ($request->has('product_ids')) {
            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);
                    $unitPrice = (($product->mrp ?: $product->sale_price) / 100) - $discountPerUnit;
                    $unitPricePaise = (int) ($unitPrice * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $product->description ?? '',
                        'qty' => $qty,
                        'unit_price' => $unitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();
            $quote->recalculateTotals();
        }



        if ($request->wantsJson()) {
            return response()->json(['message' => 'Quote created successfully', 'redirect' => route('admin.quotes.index')]);
        }

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote created successfully');
    }

    public function edit($id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'assignedTo'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && $quote->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only edit your own quotes.');
        }

        return response()->json($quote);
    }

    public function show($id)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'client', 'assignedTo', 'createdBy'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && $quote->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only view your own quotes.');
        }

        return response()->json($quote);
    }

    public function download($id)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'client', 'company', 'createdBy'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && $quote->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only download your own quotes.');
        }

        return view('admin.quotes.download', compact('quote'));
    }

    public function update(Request $request, $id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        // Non-global users can only update their own quotes
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && $quote->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only edit your own quotes.');
        }

        $validated = $request->validate([
            'client_type' => 'required|in:client,lead',
            'client_id' => 'required_if:client_type,client|nullable|exists:clients,id',
            'lead_id' => 'required_if:client_type,lead|nullable|exists:leads,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Convert rupees to paise
        $subtotal = (int) (($validated['subtotal'] ?? 0) * 100);
        $discount = (int) (($validated['discount'] ?? 0) * 100);
        $taxAmount = (int) (($validated['tax_amount'] ?? 0) * 100);
        $grandTotal = $subtotal - $discount + $taxAmount;

        $assignedTo = $quote->assigned_to_user_id;
        if ((can('quotes.global') || auth()->user()->isAdmin()) && !empty($validated['assigned_to_user_id'])) {
            $assignedTo = $validated['assigned_to_user_id'];
        }

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        $quote->update([
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'assigned_to_user_id' => $assignedTo,
            'date' => $validated['quote_date'],
            'valid_till' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gst_total' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync products: Delete old items and recreate new ones
        if ($request->has('product_ids')) {
            $quote->items()->delete();

            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);
                    $unitPrice = (($product->mrp ?: $product->sale_price) / 100) - $discountPerUnit;
                    $unitPricePaise = (int) ($unitPrice * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $product->description ?? '',
                        'qty' => $qty,
                        'unit_price' => $unitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();
            $quote->recalculateTotals();
        } elseif ($request->has('clear_products')) {
            $quote->items()->delete();
            $quote->recalculateTotals();
        }



        if ($request->wantsJson()) {
            return response()->json(['message' => 'Quote updated successfully', 'redirect' => route('admin.quotes.index')]);
        }

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote updated successfully');
    }

    public function destroy($id)
    {
        if (!can('quotes.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        // Non-global users can only delete their own quotes
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && $quote->assigned_to_user_id != auth()->id()) {
            abort(403, 'You can only delete your own quotes.');
        }

        $quote->delete();

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted successfully');
    }
}
