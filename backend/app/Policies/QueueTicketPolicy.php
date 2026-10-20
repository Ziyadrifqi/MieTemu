<?php

namespace App\Policies;

use App\Models\QueueTicket;
use App\Models\User;

class QueueTicketPolicy
{
    /** Pengunjung hanya boleh melihat tiket miliknya sendiri. */
    public function view(User $user, QueueTicket $ticket): bool
    {
        return $ticket->user_id !== null && (int) $ticket->user_id === (int) $user->id;
    }

    public function cancel(User $user, QueueTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
