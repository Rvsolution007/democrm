<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Lead;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'source' => 'nullable|in:' . implode(',', \App\Models\Lead::getDynamicSources()),
            'stage' => 'nullable|in:' . implode(',', Lead::STAGES),
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'expected_value' => 'nullable|numeric|min:0',
            'next_follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
