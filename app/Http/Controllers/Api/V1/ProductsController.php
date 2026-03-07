<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * List all products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::forCompany($this->companyId())
            ->with('category');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Low stock filter
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_qty', '<=', 'min_stock_qty');
        }

        $sortBy = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        $query->orderBy($sortBy, $order);

        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json($this->paginated($products, ProductResource::class));
    }

    /**
     * Search products (for autocomplete).
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $products = Product::forCompany($this->companyId())
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'sku', 'name', 'sale_price', 'gst_percent', 'unit', 'hsn_code']);

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Get single product.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::forCompany($this->companyId())
            ->with('category')
            ->findOrFail($id);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Create new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'company_id' => $this->companyId(),
            'category_id' => $request->category_id,
            'sku' => strtoupper($request->sku),
            'name' => $request->name,
            'description' => $request->description,
            'unit' => $request->unit ?? 'Pcs',
            'mrp' => ($request->mrp ?? 0) * 100,
            'sale_price' => ($request->sale_price ?? 0) * 100,
            'gst_percent' => $request->gst_percent ?? 18,
            'hsn_code' => $request->hsn_code,
            'stock_qty' => $request->stock_qty ?? 0,
            'min_stock_qty' => $request->min_stock_qty ?? 0,
            'image' => $imagePath,
            'specifications' => $request->specifications,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    /**
     * Update product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::forCompany($this->companyId())->findOrFail($id);

        $data = $request->only([
            'category_id',
            'name',
            'description',
            'unit',
            'hsn_code',
            'stock_qty',
            'min_stock_qty',
            'image',
            'specifications',
            'status'
        ]);

        if ($request->has('sku')) {
            $data['sku'] = strtoupper($request->sku);
        }
        if ($request->has('mrp')) {
            $data['mrp'] = $request->mrp * 100;
        }
        if ($request->has('sale_price')) {
            $data['sale_price'] = $request->sale_price * 100;
        }
        if ($request->has('gst_percent')) {
            $data['gst_percent'] = $request->gst_percent;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->has('image') && is_null($request->image)) {
            // Allow removing image by sending null
            $data['image'] = null;
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->fresh('category')),
        ]);
    }

    /**
     * Delete product.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::forCompany($this->companyId())->findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Update stock.
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:add,subtract,set',
        ]);

        $product = Product::forCompany($this->companyId())->findOrFail($id);

        match ($request->type) {
            'add' => $product->increment('stock_qty', $request->quantity),
            'subtract' => $product->decrement('stock_qty', min($request->quantity, $product->stock_qty)),
            'set' => $product->update(['stock_qty' => max(0, $request->quantity)]),
        };

        return response()->json([
            'message' => 'Stock updated successfully',
            'data' => new ProductResource($product->fresh()),
        ]);
    }
}
