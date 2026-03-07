<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_no' => $this->quote_no,
            'client_id' => $this->client_id,
            'lead_id' => $this->lead_id,
            'date' => $this->date?->format('Y-m-d'),
            'valid_till' => $this->valid_till?->format('Y-m-d'),
            'subtotal' => $this->subtotal / 100,
            'discount' => $this->discount / 100,
            'gst_total' => $this->gst_total / 100,
            'grand_total' => $this->grand_total / 100,
            'status' => $this->status,
            'is_expired' => $this->isExpired(),
            'notes' => $this->notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'sent_at' => $this->sent_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_by_user_id' => $this->created_by_user_id,
            'created_at' => $this->created_at?->toISOString(),
            'client' => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id,
                'name' => $this->client->display_name,
                'phone' => $this->client->phone,
                'email' => $this->client->email,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'items' => $this->whenLoaded('items', fn() => QuoteItemResource::collection($this->items)),
        ];
    }
}
