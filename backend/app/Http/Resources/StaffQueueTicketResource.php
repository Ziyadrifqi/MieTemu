<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffQueueTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code(),
            'ticket_number' => $this->ticket_number,
            'party_size' => $this->party_size,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'customer_name' => $this->user?->name ?? $this->guest_name ?? 'Tamu',
            'table_name' => $this->relationLoaded('diningSession') ? $this->diningSession?->table?->name : null,
            'called_at' => $this->called_at?->toIso8601String(),
            'seated_at' => $this->seated_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
