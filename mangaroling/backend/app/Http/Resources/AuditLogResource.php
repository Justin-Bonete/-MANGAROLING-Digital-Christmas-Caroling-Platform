<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AuditLog */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'details' => $this->details,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user
                ? ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]
                : null),
        ];
    }
}
