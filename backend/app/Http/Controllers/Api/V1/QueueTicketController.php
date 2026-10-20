<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QueueTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Queue\StoreQueueTicketRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\Branch;
use App\Models\QueueTicket;
use App\Services\Queue\QueueTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QueueTicketController extends Controller
{
    public function store(
        StoreQueueTicketRequest $request,
        Branch $branch,
        QueueTicketService $service,
    ): JsonResponse {
        $ticket = $service->issue($branch, $request->user(), $request->validated('party_size'));

        return (new QueueTicketResource($ticket->load('branch')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(QueueTicket $ticket): QueueTicketResource
    {
        Gate::authorize('view', $ticket);

        return new QueueTicketResource($ticket->load('branch'));
    }

    public function cancel(Request $request, QueueTicket $ticket, QueueTicketService $service): QueueTicketResource
    {
        Gate::authorize('cancel', $ticket);

        $ticket = $service->transition(
            $ticket,
            QueueTicketStatus::Cancelled,
            $request->user(),
            'Dibatalkan oleh pengunjung'
        );

        return new QueueTicketResource($ticket->load('branch'));
    }
}
