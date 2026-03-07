<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'due_at' => $this->due_at?->toISOString(),
            'priority' => $this->priority,
            'status' => $this->status,
            'is_overdue' => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'assigned_to' => $this->whenLoaded('assignedTo', fn() => [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
        ];
    }
}
