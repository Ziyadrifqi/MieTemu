<?php

namespace App\Models;

use App\Enums\QueueTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicketStatusHistory extends Model
{
    protected $fillable = ['queue_ticket_id', 'from_status', 'to_status', 'changed_by', 'note'];

    protected function casts(): array
    {
        return [
            'from_status' => QueueTicketStatus::class,
            'to_status' => QueueTicketStatus::class,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
