<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiningTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'status' => $this->status->value,
            'current' => $this->whenLoaded('activeSession', function () {
                $session = $this->activeSession;

                if (! $session) {
                    return null;
                }

                return [
                    'ticket_code' => $session->ticket?->code(),
                    'customer_name' => $session->ticket?->user?->name ?? $session->ticket?->guest_name ?? 'Tamu',
                    'party_size' => $session->party_size,
                    'seated_at' => $session->seated_at->toIso8601String(),
                ];
            }),
        ];
    }
}
