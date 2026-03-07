<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'description' => $this->description,
            'hsn_code' => $this->hsn_code,
            'qty' => $this->qty,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price / 100,
            'gst_percent' => $this->gst_percent,
            'gst_amount' => $this->gst_amount / 100,
            'line_total' => $this->line_total / 100,
        ];
    }
}
