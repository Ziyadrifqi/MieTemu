<?php

namespace App\Services\Queue;

use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Exceptions\QueueRuleException;
use App\Models\Branch;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueueTicketService
{
    public function issue(
        Branch $branch,
        ?User $user,
        int $partySize,
        QueueTicketSource $source = QueueTicketSource::Virtual,
        ?string $guestName = null,
        ?User $actor = null,
    ): QueueTicket {
        $branch->loadMissing('business');
        $now = now($branch->timezone);

        $this->assertBranchCanQueue($branch, $now);

        $sessionId = $this->todaySession($branch, $now)->id;

        return DB::transaction(function () use (
            $branch,
            $user,
            $partySize,
            $source,
            $guestName,
            $actor,
            $sessionId
        ) {
            // Kunci sesi agar nomor tiket tidak duplikat saat request bersamaan.
            $session = QueueSession::query()
                ->lockForUpdate()
                ->findOrFail($sessionId);

            if ($session->status !== 'open') {
                throw new QueueRuleException(
                    'Antrean hari ini sudah ditutup.'
                );
            }

            if (
                $user !== null
                && $session->tickets()
                ->where('user_id', $user->id)
                ->whereIn('status', QueueTicketStatus::activeValues())
                ->exists()
            ) {
                throw new QueueRuleException(
                    'Anda masih memiliki tiket antrean yang aktif di cabang ini.',
                    409
                );
            }

            $number = $session->last_ticket_number + 1;

            $session->forceFill([
                'last_ticket_number' => $number,
            ])->save();

            $ticket = $session->tickets()->create([
                'branch_id' => $branch->id,
                'user_id' => $user?->id,
                'guest_name' => $guestName,
                'source' => $source,
                'ticket_number' => $number,
                'party_size' => $partySize,
                'status' => QueueTicketStatus::Waiting,
            ]);

            $ticket->statusHistories()->create([
                'from_status' => null,
                'to_status' => QueueTicketStatus::Waiting,
                'changed_by' => ($actor ?? $user)?->id,
                'note' => $source === QueueTicketSource::WalkIn
                    ? 'Tiket walk-in dibuat staf'
                    : 'Tiket dibuat',
            ]);

            return $ticket;
        });
    }

    /**
     * Ubah status tiket sesuai aturan transisi dan catat riwayatnya.
     */
    public function transition(
        QueueTicket $ticket,
        QueueTicketStatus $to,
        ?User $actor = null,
        ?string $note = null,
    ): QueueTicket {
        return DB::transaction(function () use ($ticket, $to, $actor, $note) {
            $locked = QueueTicket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            if (! $locked->status->canTransitionTo($to)) {
                throw new QueueRuleException(
                    "Status tiket tidak bisa diubah dari {$locked->status->value} ke {$to->value}.",
                    409
                );
            }

            $from = $locked->status;
            $locked->status = $to;

            $column = match ($to) {
                QueueTicketStatus::Called => 'called_at',
                QueueTicketStatus::Seated => 'seated_at',
                QueueTicketStatus::Completed => 'completed_at',
                QueueTicketStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            if ($column !== null) {
                $locked->{$column} = now();
            }

            $locked->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'note' => $note,
            ]);

            return $locked;
        });
    }

    /**
     * Panggil tiket Waiting dengan nomor terkecil di sesi hari ini.
     */
    public function callNext(
        Branch $branch,
        ?User $actor = null
    ): QueueTicket {
        $date = now($branch->timezone)->toDateString();

        $sessionId = QueueSession::query()
            ->where('branch_id', $branch->id)
            ->where('session_date', $date)
            ->value('id');

        if ($sessionId === null) {
            throw new QueueRuleException(
                'Belum ada antrean hari ini.',
                404
            );
        }

        return DB::transaction(function () use ($sessionId, $branch, $actor) {
            $ticket = QueueTicket::query()
                ->where('queue_session_id', $sessionId)
                ->where('branch_id', $branch->id)
                ->where('status', QueueTicketStatus::Waiting->value)
                ->orderBy('ticket_number')
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new QueueRuleException(
                    'Tidak ada antrean yang sedang menunggu.',
                    404
                );
            }

            return $this->transition(
                $ticket,
                QueueTicketStatus::Called,
                $actor,
                'Dipanggil staf'
            );
        });
    }

    private function todaySession(
        Branch $branch,
        Carbon $now
    ): QueueSession {
        $date = $now->toDateString();

        return QueueSession::query()
            ->where('branch_id', $branch->id)
            ->where('session_date', $date)
            ->first()
            ?? QueueSession::createOrFirst(
                [
                    'branch_id' => $branch->id,
                    'session_date' => $date,
                ],
                [
                    'status' => 'open',
                    'opened_at' => $now,
                ]
            );
    }

    private function assertBranchCanQueue(
        Branch $branch,
        Carbon $now
    ): void {
        if (! $branch->is_active || $branch->business->status !== 'active') {
            throw new QueueRuleException('Cabang tidak tersedia.', 404);
        }

        if (! $branch->accepts_queue) {
            throw new QueueRuleException(
                'Cabang ini sedang tidak menerima antrean.'
            );
        }

        $hour = $branch->hours()
            ->where('day_of_week', $now->dayOfWeekIso)
            ->first();

        $time = $now->format('H:i:s');

        if (
            ! $hour
            || $hour->is_closed
            || ! $hour->open_time
            || ! $hour->close_time
            || $time < $hour->open_time
            || $time >= $hour->close_time
        ) {
            throw new QueueRuleException(
                'Cabang sedang di luar jam operasional.'
            );
        }
    }
}
