<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'date' => 'nullable|date',
            'valid_till' => 'nullable|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required_without:items.*.product_id|string|max:255',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.gst_percent' => 'required|integer|in:0,5,12,18,28',
            'items.*.hsn_code' => 'nullable|string|max:20',
            'items.*.unit' => 'nullable|string|max:20',
        ];
    }
}
