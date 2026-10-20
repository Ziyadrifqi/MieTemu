<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use App\Services\Queue\QueueTicketService;
use Illuminate\Console\Command;
use Throwable;

class DevQueueIssue extends Command
{
    protected $signature = 'dev:queue-issue {branch} {user}';

    protected $description = 'Dipakai internal oleh dev:queue-stress: ambil satu tiket untuk satu pengguna';

    protected $hidden = true;

    public function handle(QueueTicketService $service): int
    {
        try {
            $ticket = $service->issue(
                Branch::findOrFail($this->argument('branch')),
                User::findOrFail($this->argument('user')),
                2
            );

            $this->line('TICKET:' . $ticket->ticket_number);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->line('ERROR:' . class_basename($e) . ': ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
