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
use App\Models\Purchase;
use Illuminate\Http\Request;

class QuotesController extends Controller
{
    public function index(Request $request)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Quote::with(['client', 'lead', 'assignedUsers', 'items']);

        // Global permission filter
        if (!can('quotes.global')) {
            $query->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    });
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('business_name', 'like', "%{$search}%")
                           ->orWhere('contact_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // User Filter
        if ($request->filled('assigned_to_user_id')) {
            $query->whereHas('assignedUsers', function($q) use($request) {
                $q->where('user_id', $request->assigned_to_user_id);
            });
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

        // Quotes tab: all non-accepted quotes (regular quotes)
        $leadTotalAmount = $query->where('status', '!=', 'accepted')->sum('grand_total') / 100;

        $leadDueQuery = clone $query;
        $leadDueAmount = $leadDueQuery->where('status', '!=', 'accepted')->sum('grand_total') / 100;

        $leadQuery = clone $query;
        $leadQuotes = $leadQuery->where('status', '!=', 'accepted')->latest()->paginate(20, ['*'], 'lead_page')->withQueryString();
        $clients = Client::all();
        $products = Product::all();

        $leadsQuery = Lead::query();
        if (!can('leads.global')) {
            $leadsQuery->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    });
            });
        }
        $leads = $leadsQuery->orderBy('name')->get();

        $users = (can('quotes.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('quotes')->orderBy('name')->get()
            : collect();

        $companyId = auth()->check() ? auth()->user()->company_id : 1;
        
        // Fetch company specific taxes
        $companyTaxes = Setting::getValue('quotes', 'taxes', [], $companyId);
        
        // Fetch global taxes (company 1) as fallback/addition
        $globalTaxes = $companyId !== 1 ? Setting::getValue('quotes', 'taxes', [], 1) : [];
        
        // Merge and ensure uniqueness by name and rate
        $allTaxes = array_merge($globalTaxes, $companyTaxes);
        
        $quoteTaxes = collect($allTaxes)->unique(function ($item) {
            return ($item['name'] ?? '') . '_' . ($item['rate'] ?? 0);
        })->values()->toArray();
        $paymentTypes = Setting::getValue('payments', 'types', ['cash', 'online', 'cheque', 'upi', 'bank_transfer']);

        return view('admin.quotes.index', compact('leadQuotes', 'clients', 'products', 'leads', 'users', 'quoteTaxes', 'paymentTypes', 'leadTotalAmount', 'leadDueAmount'));
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
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
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

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        Quote::create([
            'company_id' => auth()->user()->company_id,
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'created_by_user_id' => auth()->id(),
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

        $assignedUsers = [];
        if (!can('quotes.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $quote->assignedUsers()->sync($assignedUsers);

        // Create QuoteItems from selected products
        if ($request->has('product_ids')) {
            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);

                    // Respect user provided price or fallback to product price
                    if (isset($request->product_prices[$index])) {
                        $unitPrice = (float) $request->product_prices[$index];
                    } else {
                        $unitPrice = ($product->mrp ?: $product->sale_price) / 100;
                    }

                    $ratePaise = (int) ($unitPrice * 100);
                    $discountPaise = (int) ($discountPerUnit * 100);
                    $finalUnitPricePaise = $ratePaise; // Just save original rate in unit_price or ignore it, but we calculate flat discount. Wait, unit_price isn't used for subtotal if we change calculateTotals but let's keep unit_price = rate.

                    $desc = $request->product_descriptions[$index] ?? $product->description ?? '';

                    $purchaseAmountPaise = (int) round(((float) ($request->product_purchase_amounts[$index] ?? 0)) * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $desc,
                        'qty' => $qty,
                        'rate' => $ratePaise,
                        'discount' => $discountPaise,
                        'purchase_amount' => $purchaseAmountPaise,
                        'unit_price' => $finalUnitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();
        }

        // Sync products back to Lead if quote has a lead and is not accepted
        if ($quote->lead_id && $quote->status !== 'accepted') {
            $lead = \App\Models\Lead::find($quote->lead_id);
            if ($lead) {
                $productData = [];
                $quote->load('items');
                foreach ($quote->items as $item) {
                    $productData[$item->product_id] = [
                        'quantity' => $item->qty,
                        'price' => $item->rate ?? $item->unit_price,
                        'discount' => $item->discount ?? 0,
                        'description' => $item->description,
                    ];
                }
                $lead->products()->sync($productData);
            }
        }

        // Auto-project/purchase creation is handled during explicit quote conversion

        if ($request->ajax() || $request->wantsJson()) {
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

        $quote = Quote::with(['items', 'lead', 'assignedUsers'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own quotes.');
        }

        return response()->json($quote);
    }

    public function show($id)
    {
        if (!can('quotes.read')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::with(['items', 'lead', 'client', 'assignedUsers', 'createdBy'])->findOrFail($id);

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
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

        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
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
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own quotes.');
        }

        $validated = $request->validate([
            'client_type' => 'required|in:client,lead',
            'client_id' => 'required_if:client_type,client|nullable|exists:clients,id',
            'lead_id' => 'required_if:client_type,lead|nullable|exists:leads,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
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

        $clientId = $validated['client_type'] === 'client' ? $validated['client_id'] : null;
        $leadId = $validated['client_type'] === 'lead' ? $validated['lead_id'] : null;

        // Capture original product IDs to check if they changed
        $originalProductIds = $quote->items->pluck('product_id')->toArray();
        $productsChanged = false;

        $quote->update([
            'client_id' => $clientId,
            'lead_id' => $leadId,
            'date' => $validated['quote_date'],
            'valid_till' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gst_total' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $assignedUsers = [];
        if (!can('quotes.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $quote->assignedUsers()->sync($assignedUsers);

        // Sync products: Delete old items and recreate new ones
        if ($request->has('product_ids')) {
            // Check if product IDs differ from original
            $newProductIds = array_map('intval', $request->product_ids);
            sort($originalProductIds);
            sort($newProductIds);
            if ($originalProductIds !== $newProductIds) {
                $productsChanged = true;
            }

            $quote->items()->delete();

            foreach ($request->product_ids as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $qty = (int) (($request->product_quantities[$index] ?? 1) ?: 1);
                    $discountPerUnit = (float) ($request->product_discounts[$index] ?? 0);

                    // Respect user provided price or fallback to product price
                    if (isset($request->product_prices[$index])) {
                        $unitPrice = (float) $request->product_prices[$index];
                    } else {
                        $unitPrice = ($product->mrp ?: $product->sale_price) / 100;
                    }

                    $ratePaise = (int) ($unitPrice * 100);
                    $discountPaise = (int) ($discountPerUnit * 100);
                    $finalUnitPricePaise = $ratePaise;

                    $desc = $request->product_descriptions[$index] ?? $product->description ?? '';

                    $purchaseAmountPaise = (int) round(((float) ($request->product_purchase_amounts[$index] ?? 0)) * 100);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'description' => $desc,
                        'qty' => $qty,
                        'rate' => $ratePaise,
                        'discount' => $discountPaise,
                        'purchase_amount' => $purchaseAmountPaise,
                        'unit_price' => $finalUnitPricePaise,
                        'gst_percent' => 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            $quote->refresh();
        } elseif ($request->has('clear_products')) {
            $quote->items()->delete();
        }

        // If products changed and the quote was previously accepted, revert it to draft
        if ($productsChanged && $quote->status === 'accepted') {
            $quote->update(['status' => 'draft']);
        }

        // Sync products back to Lead if quote has a lead and is not accepted
        if ($quote->lead_id && $quote->status !== 'accepted') {
            $lead = \App\Models\Lead::find($quote->lead_id);
            if ($lead) {
                $productData = [];
                $quote->load('items');
                foreach ($quote->items as $item) {
                    $productData[$item->product_id] = [
                        'quantity' => $item->qty,
                        'price' => $item->rate ?? $item->unit_price,
                        'discount' => $item->discount ?? 0,
                        'description' => $item->description,
                    ];
                }
                $lead->products()->sync($productData);
            }
        }

        // Auto-project/purchase creation is handled during explicit quote conversion

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Quote updated successfully', 'redirect' => route('admin.quotes.index')]);
        }

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote updated successfully');
    }

    /**
     * Convert a quote to accepted status, auto-create project and purchases.
     */
    public function convert(Request $request, $id)
    {
        if (!can('quotes.write')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        if ($quote->status === 'accepted') {
            return response()->json(['message' => 'Quote is already converted.'], 409);
        }

        // Generate invoice number (I-YY-YY-NNNNNN)
        $company = auth()->user()->company;
        $invoiceNumber = Quote::generateInvoiceNumber($company);

        // Mark as accepted and assign invoice number, updating the date to today
        $quote->update([
            'status' => 'accepted',
            'quote_no' => $invoiceNumber,
            'date' => now()->toDateString(),
        ]);

        // Auto-create purchases for items with purchase-enabled products
        if ($quote->client_id) {
            $this->autoCreatePurchases($quote);
        }

        $response = [
            'message' => 'Quote converted to Invoice successfully. Invoice No: ' . $invoiceNumber,
            'redirect' => route('admin.invoices.index'),
        ];

        return response()->json($response);
    }

    /**
     * Auto-create Purchases for a client quote.
     * This runs when a quote is converted to an invoice.
     */
    private function autoCreatePurchases(Quote $quote): void
    {
        $quote->load('items');

        $client = Client::find($quote->client_id);
        if (!$client) {
            return;
        }

        $companyId = $client->company_id ?? auth()->user()->company_id;
        $company = Company::find($companyId);

        // Get existing purchase product IDs for this quote to avoid duplicates
        $existingPurchaseProductIds = Purchase::where('quote_id', $quote->id)
            ->pluck('product_id')
            ->toArray();

        foreach ($quote->items as $item) {
            if ($item->product_id && !in_array($item->product_id, $existingPurchaseProductIds)) {
                $product = Product::find($item->product_id);
                if ($product && $product->is_purchase_enabled) {
                    $purchaseTotalPaise = ($item->purchase_amount > 0)
                        ? $item->purchase_amount
                        : $item->unit_price * max(1, $item->qty);
                    Purchase::create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'purchase_no' => Purchase::generatePurchaseNumber($company),
                        'date' => now()->toDateString(),
                        'total_amount' => $purchaseTotalPaise,
                        'paid_amount' => 0,
                        'status' => 'draft',
                        'notes' => 'Auto-generated from invoice ' . $quote->quote_no . ' for product: ' . $product->name,
                    ]);
                    $existingPurchaseProductIds[] = $product->id;
                }
            }
        }
    }

    public function destroy($id)
    {
        if (!can('quotes.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $quote = Quote::findOrFail($id);

        // Non-global users can only delete their own quotes
        if (!can('quotes.global') && $quote->created_by_user_id != auth()->id() && !$quote->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only delete your own quotes.');
        }

        $quote->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Quote deleted successfully'
            ]);
        }

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted successfully');
    }
}
