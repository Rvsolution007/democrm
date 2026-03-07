<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit' => $this->unit,
            'mrp' => $this->mrp / 100,
            'sale_price' => $this->sale_price / 100,
            'gst_percent' => $this->gst_percent,
            'hsn_code' => $this->hsn_code,
            'stock_qty' => $this->stock_qty,
            'min_stock_qty' => $this->min_stock_qty,
            'is_low_stock' => $this->isLowStock(),
            'image' => $this->image,
            'specifications' => $this->specifications,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
        ];
    }
}
