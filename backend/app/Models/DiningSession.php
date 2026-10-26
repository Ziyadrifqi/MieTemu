<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiningSession extends Model
{
    protected $fillable = [
        'branch_id',
        'dining_table_id',
        'queue_ticket_id',
        'party_size',
        'seated_at',
        'ended_at',
        'seated_by',
    ];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'seated_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }
}
