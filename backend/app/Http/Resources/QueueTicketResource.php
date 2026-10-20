<?php

namespace App\Http\Resources;

use App\Enums\QueueTicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $waiting = $this->status === QueueTicketStatus::Waiting;
        $ahead = $waiting ? $this->peopleAhead() : null;

        return [
            'id' => $this->id,
            'code' => $this->code(),
            'ticket_number' => $this->ticket_number,
            'party_size' => $this->party_size,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'position' => $waiting ? $ahead + 1 : null,
            'estimated_wait_minutes' => $waiting
                ? $ahead * (int) config('mietemu.queue.minutes_per_ticket')
                : null,
            'branch' => $this->whenLoaded('branch', fn() => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'called_at' => $this->called_at?->toIso8601String(),
            'seated_at' => $this->seated_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
