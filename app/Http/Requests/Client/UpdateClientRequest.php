<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:business,individual',
            'business_name' => 'nullable|string|max:255',
            'contact_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|size:10',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ];
    }
}
