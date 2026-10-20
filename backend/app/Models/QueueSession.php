<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueSession extends Model
{
    protected $fillable = [
        'branch_id',
        'session_date',
        'status',
        'last_ticket_number',
        'opened_at',
        'closed_at',
    ];

    // session_date sengaja tanpa cast, supaya selalu berupa teks "YYYY-MM-DD".
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
