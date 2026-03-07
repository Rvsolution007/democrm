<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'parent_category_id' => $this->parent_category_id,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'parent' => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),
        ];
    }
}
