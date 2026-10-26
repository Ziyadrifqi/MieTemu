<?php

namespace App\Services\Dining;

use App\Enums\QueueTicketStatus;
use App\Enums\TableStatus;
use App\Exceptions\QueueRuleException;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\Queue\QueueTicketService;
use Illuminate\Support\Facades\DB;

class DiningService
{
    public function __construct(private readonly QueueTicketService $queue) {}

    /** Dudukkan tiket (harus berstatus called) di sebuah meja kosong. */
    public function seat(QueueTicket $ticket, DiningTable $table, ?User $actor = null): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $table, $actor) {
            // Baris meja dikunci: dua staf yang memilih meja yang sama tidak bisa sama-sama berhasil.
            $locked = DiningTable::query()->lockForUpdate()->findOrFail($table->id);

            if ((int) $locked->branch_id !== (int) $ticket->branch_id) {
                throw new QueueRuleException('Meja tidak berada di cabang yang sama dengan tiket.');
            }

            if ($locked->status !== TableStatus::Available) {
                throw new QueueRuleException("Meja {$locked->name} tidak tersedia saat ini.", 409);
            }

            if ($locked->capacity < $ticket->party_size) {
                throw new QueueRuleException(
                    "Kapasitas meja {$locked->name} ({$locked->capacity} kursi) kurang untuk {$ticket->party_size} orang."
                );
            }

            $seated = $this->queue->transition(
                $ticket,
                QueueTicketStatus::Seated,
                $actor,
                "Duduk di meja {$locked->name}"
            );

            DiningSession::create([
                'branch_id' => $locked->branch_id,
                'dining_table_id' => $locked->id,
                'queue_ticket_id' => $seated->id,
                'party_size' => $seated->party_size,
                'seated_at' => now(),
                'seated_by' => $actor?->id,
            ]);

            $locked->update(['status' => TableStatus::Occupied]);

            return $seated;
        });
    }

    /** Selesaikan kunjungan: tutup sesi makan dan tandai meja perlu dibersihkan. */
    public function complete(QueueTicket $ticket, ?User $actor = null): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $completed = $this->queue->transition(
                $ticket,
                QueueTicketStatus::Completed,
                $actor,
                'Kunjungan selesai'
            );

            $session = DiningSession::query()
                ->where('queue_ticket_id', $completed->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($session) {
                $session->update(['ended_at' => now()]);

                DiningTable::query()
                    ->whereKey($session->dining_table_id)
                    ->update(['status' => TableStatus::NeedsCleaning->value]);
            }

            return $completed;
        });
    }

    public function markClean(DiningTable $table): DiningTable
    {
        return DB::transaction(function () use ($table) {
            $locked = DiningTable::query()->lockForUpdate()->findOrFail($table->id);

            if ($locked->status !== TableStatus::NeedsCleaning) {
                throw new QueueRuleException('Hanya meja berstatus "perlu dibersihkan" yang bisa ditandai bersih.', 409);
            }

            $locked->update(['status' => TableStatus::Available]);

            return $locked;
        });
    }

    public function setActive(DiningTable $table, bool $active): DiningTable
    {
        return DB::transaction(function () use ($table, $active) {
            $locked = DiningTable::query()->lockForUpdate()->findOrFail($table->id);

            if ($active) {
                if ($locked->status === TableStatus::Inactive) {
                    $locked->update(['status' => TableStatus::Available]);
                }

                return $locked;
            }

            if (in_array($locked->status, [TableStatus::Occupied, TableStatus::Allocated], true)) {
                throw new QueueRuleException('Meja sedang digunakan dan tidak bisa dinonaktifkan.', 409);
            }

            $locked->update(['status' => TableStatus::Inactive]);

            return $locked;
        });
    }
}
