<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id' => 'nullable|exists:leads,id',
            'type' => 'nullable|in:business,individual',
            'business_name' => 'nullable|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|size:10',
            'billing_address' => 'nullable|array',
            'billing_address.line1' => 'nullable|string|max:255',
            'billing_address.line2' => 'nullable|string|max:255',
            'billing_address.city' => 'nullable|string|max:100',
            'billing_address.state' => 'nullable|string|max:100',
            'billing_address.pincode' => 'nullable|string|max:10',
            'billing_address.country' => 'nullable|string|max:100',
            'shipping_address' => 'nullable|array',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
        ];
    }
}
