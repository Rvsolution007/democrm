<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotesController extends Controller
{
    /**
     * List all quotes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Quote::forCompany($this->companyId())
            ->with(['client', 'createdBy']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_no', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($q) => $q->where('business_name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%"));
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by client
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Date range
        if ($from = $request->get('from_date')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->get('to_date')) {
            $query->whereDate('date', '<=', $to);
        }

        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);

        $perPage = min($request->get('per_page', 15), 100);
        $quotes = $query->paginate($perPage);

        return response()->json($this->paginated($quotes, QuoteResource::class));
    }

    /**
     * Get single quote.
     */
    public function show(int $id): JsonResponse
    {
        $quote = Quote::forCompany($this->companyId())
            ->with(['client', 'lead', 'createdBy', 'items.product'])
            ->findOrFail($id);

        return response()->json([
            'data' => new QuoteResource($quote),
        ]);
    }

    /**
     * Create new quote.
     */
    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $company = Company::findOrFail($this->companyId());

        $quote = Quote::create([
            'company_id' => $this->companyId(),
            'client_id' => $request->client_id,
            'lead_id' => $request->lead_id,
            'created_by_user_id' => auth()->id(),
            'quote_no' => Quote::generateQuoteNumber($company),
            'date' => $request->date ?? now()->toDateString(),
            'valid_till' => $request->valid_till ?? now()->addDays(30)->toDateString(),
            'discount' => ($request->discount ?? 0) * 100,
            'status' => 'draft',
            'notes' => $request->notes,
            'terms_and_conditions' => $request->terms_and_conditions ?? $company->terms_and_conditions,
        ]);

        // Add items
        if ($request->has('items')) {
            foreach ($request->items as $index => $item) {
                $this->addQuoteItem($quote, $item, $index);
            }
            $quote->recalculateTotals();
        }

        return response()->json([
            'message' => 'Quote created successfully',
            'data' => new QuoteResource($quote->fresh(['client', 'items'])),
        ], 201);
    }

    /**
     * Update quote.
     */
    public function update(UpdateQuoteRequest $request, int $id): JsonResponse
    {
        $quote = Quote::forCompany($this->companyId())->findOrFail($id);

        if (!$quote->isDraft()) {
            return $this->error('Only draft quotes can be edited', 422);
        }

        $data = $request->only(['client_id', 'lead_id', 'date', 'valid_till', 'notes', 'terms_and_conditions']);

        if ($request->has('discount')) {
            $data['discount'] = $request->discount * 100;
        }

        $quote->update($data);

        // Update items if provided
        if ($request->has('items')) {
            $quote->items()->delete();
            foreach ($request->items as $index => $item) {
                $this->addQuoteItem($quote, $item, $index);
            }
            $quote->recalculateTotals();
        }

        return response()->json([
            'message' => 'Quote updated successfully',
            'data' => new QuoteResource($quote->fresh(['client', 'items'])),
        ]);
    }

    /**
     * Delete quote.
     */
    public function destroy(int $id): JsonResponse
    {
        $quote = Quote::forCompany($this->companyId())->findOrFail($id);

        if (!$quote->isDraft()) {
            return $this->error('Only draft quotes can be deleted', 422);
        }

        $quote->delete();

        return response()->json([
            'message' => 'Quote deleted successfully',
        ]);
    }

    /**
     * Add item to quote.
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $quote = Quote::forCompany($this->companyId())->findOrFail($id);

        if (!$quote->isDraft()) {
            return $this->error('Cannot add items to non-draft quotes', 422);
        }

        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'product_name' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'gst_percent' => 'required|integer|in:0,5,12,18,28',
        ]);

        $sortOrder = $quote->items()->count();
        $this->addQuoteItem($quote, $request->all(), $sortOrder);
        $quote->recalculateTotals();

        return response()->json([
            'message' => 'Item added successfully',
            'data' => new QuoteResource($quote->fresh(['items'])),
        ]);
    }

    /**
     * Remove item from quote.
     */
    public function removeItem(int $quoteId, int $itemId): JsonResponse
    {
        $quote = Quote::forCompany($this->companyId())->findOrFail($quoteId);

        if (!$quote->isDraft()) {
            return $this->error('Cannot remove items from non-draft quotes', 422);
        }

        $item = $quote->items()->findOrFail($itemId);
        $item->delete();
        $quote->recalculateTotals();

        return response()->json([
            'message' => 'Item removed successfully',
            'data' => new QuoteResource($quote->fresh(['items'])),
        ]);
    }

    /**
     * Update quote status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', Quote::STATUSES),
        ]);

        $quote = Quote::forCompany($this->companyId())->findOrFail($id);
        $newStatus = $request->status;

        // Validate status transitions
        $validTransitions = [
            'draft' => ['sent'],
            'sent' => ['accepted', 'rejected', 'expired'],
            'accepted' => [],
            'rejected' => [],
            'expired' => [],
        ];

        if (!in_array($newStatus, $validTransitions[$quote->status] ?? [])) {
            return $this->error("Invalid status transition from {$quote->status} to {$newStatus}", 422);
        }

        $quote->status = $newStatus;

        // Set timestamps
        match ($newStatus) {
            'sent' => $quote->sent_at = now(),
            'accepted' => $quote->accepted_at = now(),
            'rejected' => $quote->rejected_at = now(),
            default => null,
        };

        $quote->save();

        return response()->json([
            'message' => 'Quote status updated successfully',
            'data' => new QuoteResource($quote),
        ]);
    }

    /**
     * Get next quote number preview.
     */
    public function nextNumber(): JsonResponse
    {
        $company = Company::findOrFail($this->companyId());
        $nextNumber = Quote::generateQuoteNumber($company);

        return response()->json([
            'next_quote_no' => $nextNumber,
        ]);
    }

    /**
     * Helper to add quote item.
     */
    private function addQuoteItem(Quote $quote, array $item, int $sortOrder): QuoteItem
    {
        $product = null;
        if (!empty($item['product_id'])) {
            $product = Product::find($item['product_id']);
        }

        return QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $item['product_id'] ?? null,
            'product_name' => $item['product_name'] ?? $product?->name ?? 'Custom Item',
            'description' => $item['description'] ?? null,
            'hsn_code' => $item['hsn_code'] ?? $product?->hsn_code,
            'qty' => $item['qty'] ?? 1,
            'unit' => $item['unit'] ?? $product?->unit ?? 'Pcs',
            'unit_price' => ($item['unit_price'] ?? 0) * 100,
            'gst_percent' => $item['gst_percent'] ?? $product?->gst_percent ?? 18,
            'sort_order' => $sortOrder,
        ]);
    }
}
