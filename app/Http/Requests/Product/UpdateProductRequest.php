<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'nullable|string|max:20',
            'mrp' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'gst_percent' => 'nullable|integer|in:0,5,12,18,28',
            'hsn_code' => 'nullable|string|max:20',
            'stock_qty' => 'nullable|integer|min:0',
            'min_stock_qty' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
