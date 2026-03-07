<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'business_name' => $this->business_name,
            'contact_name' => $this->contact_name,
            'display_name' => $this->display_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'credit_limit' => $this->credit_limit / 100,
            'outstanding_amount' => $this->outstanding_amount / 100,
            'payment_terms_days' => $this->payment_terms_days,
            'status' => $this->status,
            'notes' => $this->notes,
            'lead_id' => $this->lead_id,
            'created_at' => $this->created_at?->toISOString(),
            'lead' => $this->whenLoaded('lead', fn() => new LeadResource($this->lead)),
        ];
    }
}
