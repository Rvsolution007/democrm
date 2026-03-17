<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_provider' => $this->source_provider,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'state' => $this->state,
            'stage' => $this->stage,
            'expected_value' => $this->expected_value / 100, // Convert paise to rupees
            'next_follow_up_at' => $this->next_follow_up_at?->toISOString(),
            'notes' => $this->notes,
            'query_type' => $this->query_type,
            'query_message' => $this->query_message,
            'product_name' => $this->product_name,
            'assigned_to_users' => $this->whenLoaded('assignedUsers', fn() => $this->assignedUsers->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])),
            'has_overdue_follow_up' => $this->hasOverdueFollowUp(),
            'created_at' => $this->created_at?->toISOString(),
            'activities' => $this->whenLoaded('activities', fn() => ActivityResource::collection($this->activities)),
            'tasks' => $this->whenLoaded('tasks', fn() => TaskResource::collection($this->tasks)),
        ];
    }
}
