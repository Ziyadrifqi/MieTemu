<?php

namespace App\Models;

use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueTicket extends Model
{
    protected $fillable = [
        'queue_session_id',
        'branch_id',
        'user_id',
        'guest_name',
        'source',
        'ticket_number',
        'party_size',
        'status',
        'called_at',
        'seated_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => QueueTicketSource::class,
            'status' => QueueTicketStatus::class,
            'ticket_number' => 'integer',
            'party_size' => 'integer',
            'called_at' => 'datetime',
            'seated_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QueueSession::class, 'queue_session_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(QueueTicketStatusHistory::class);
    }

    public function code(): string
    {
        return sprintf('A-%03d', $this->ticket_number);
    }

    /** Jumlah tiket waiting di depan tiket ini (posisi dihitung saat dibaca, bukan untuk membuat nomor). */
    public function peopleAhead(): int
    {
        return static::query()
            ->where('queue_session_id', $this->queue_session_id)
            ->where('status', QueueTicketStatus::Waiting->value)
            ->where('ticket_number', '<', $this->ticket_number)
            ->count();
    }

    public function diningSession(): HasOne
    {
        return $this->hasOne(DiningSession::class);
    }
}
