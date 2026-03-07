<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'role_id' => $this->role_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'role' => $this->whenLoaded('role', fn() => new RoleResource($this->role)),
            'company' => $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
        ];
    }
}
