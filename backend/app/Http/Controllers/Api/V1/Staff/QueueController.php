<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Queue\StoreWalkInTicketRequest;
use App\Http\Resources\StaffQueueTicketResource;
use App\Models\Branch;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Services\Queue\QueueTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function __construct(private readonly QueueTicketService $service) {}

    /** Daftar antrean hari ini untuk satu cabang. */
    public function index(Request $request, Branch $branch): JsonResponse
    {
        Gate::authorize('manageQueue', $branch);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(QueueTicketStatus::class)],
        ]);

        $date = now($branch->timezone)->toDateString();

        $session = QueueSession::query()
            ->where('branch_id', $branch->id)
            ->where('session_date', $date)
            ->first();

        $counts = $session
            ? $session->tickets()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            : collect();

        $summary = [];
        foreach (QueueTicketStatus::cases() as $case) {
            $summary[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        $tickets = $session
            ? $session->tickets()
            ->with('user:id,name')
            ->when(
                $validated['status'] ?? null,
                fn($q, $status) => $q->where('status', $status),
                fn($q) => $q->whereIn('status', QueueTicketStatus::activeValues())
            )
            ->orderBy('ticket_number')
            ->paginate(50)
            : QueueTicket::query()->whereRaw('1 = 0')->paginate(50);

        return StaffQueueTicketResource::collection($tickets)
            ->additional(['summary' => $summary, 'session_date' => $date])
            ->response();
    }

    /** Tiket walk-in: pengunjung datang langsung, staf yang memasukkan. */
    public function storeWalkIn(StoreWalkInTicketRequest $request, Branch $branch): JsonResponse
    {
        Gate::authorize('manageQueue', $branch);

        $ticket = $this->service->issue(
            $branch,
            null,
            $request->validated('party_size'),
            QueueTicketSource::WalkIn,
            $request->validated('guest_name'),
            $request->user(),
        );

        return (new StaffQueueTicketResource($ticket))->response()->setStatusCode(201);
    }

    public function callNext(Request $request, Branch $branch): StaffQueueTicketResource
    {
        Gate::authorize('manageQueue', $branch);

        return new StaffQueueTicketResource($this->service->callNext($branch, $request->user()));
    }

    public function call(Request $request, QueueTicket $ticket): StaffQueueTicketResource
    {
        return $this->act($request, $ticket, QueueTicketStatus::Called, 'Dipanggil staf');
    }

    public function seat(Request $request, QueueTicket $ticket): StaffQueueTicketResource
    {
        return $this->act($request, $ticket, QueueTicketStatus::Seated, 'Pengunjung duduk');
    }

    public function noShow(Request $request, QueueTicket $ticket): StaffQueueTicketResource
    {
        return $this->act($request, $ticket, QueueTicketStatus::NoShow, 'Pengunjung tidak hadir');
    }

    public function cancel(Request $request, QueueTicket $ticket): StaffQueueTicketResource
    {
        return $this->act($request, $ticket, QueueTicketStatus::Cancelled, 'Dibatalkan oleh staf');
    }

    public function complete(Request $request, QueueTicket $ticket): StaffQueueTicketResource
    {
        return $this->act($request, $ticket, QueueTicketStatus::Completed, 'Kunjungan selesai');
    }

    private function act(Request $request, QueueTicket $ticket, QueueTicketStatus $to, string $note): StaffQueueTicketResource
    {
        $ticket->loadMissing('branch.business');
        Gate::authorize('manageQueue', $ticket->branch);

        $updated = $this->service->transition($ticket, $to, $request->user(), $note);

        return new StaffQueueTicketResource($updated->load('user:id,name'));
    }
}
