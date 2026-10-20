<?php

namespace Tests\Feature;

use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessMember;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\Queue\QueueTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffQueueApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Branch $branchA;
    private Branch $branchB;
    private User $host;
    private QueueTicketService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'Asia/Jakarta'));

        $this->business = Business::create(['name' => 'Resto A', 'slug' => 'resto-a', 'status' => 'active']);
        $this->branchA = $this->makeBranch('Cabang A');
        $this->branchB = $this->makeBranch('Cabang B');

        $this->host = User::factory()->create();
        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->host->id,
            'branch_id' => $this->branchA->id,
            'role' => StaffRole::Host,
        ]);

        $this->service = app(QueueTicketService::class);
    }

    private function makeBranch(string $name): Branch
    {
        $branch = Branch::create(['business_id' => $this->business->id, 'name' => $name])->refresh();

        for ($day = 1; $day <= 7; $day++) {
            BusinessHour::create([
                'branch_id' => $branch->id,
                'day_of_week' => $day,
                'open_time' => '10:00:00',
                'close_time' => '21:00:00',
            ]);
        }

        return $branch;
    }

    private function customerTicket(Branch $branch): QueueTicket
    {
        return $this->service->issue($branch, User::factory()->create(), 2);
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue")->assertUnauthorized();
    }

    public function test_host_sees_active_queue_with_summary(): void
    {
        $first = $this->customerTicket($this->branchA);
        $this->customerTicket($this->branchA);
        $this->service->transition($first, QueueTicketStatus::Called);

        Sanctum::actingAs($this->host);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('summary.waiting', 1)
            ->assertJsonPath('summary.called', 1)
            ->assertJsonPath('session_date', '2026-10-19');
    }

    public function test_queue_is_empty_when_no_session_today(): void
    {
        Sanctum::actingAs($this->host);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('summary.waiting', 0);
    }

    public function test_host_cannot_manage_other_branch(): void
    {
        $ticket = $this->customerTicket($this->branchB);
        Sanctum::actingAs($this->host);

        $this->getJson("/api/v1/staff/branches/{$this->branchB->id}/queue")->assertForbidden();
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/call")->assertForbidden();
        $this->postJson("/api/v1/staff/branches/{$this->branchB->id}/queue/call-next")->assertForbidden();
    }

    public function test_kitchen_staff_cannot_manage_queue(): void
    {
        $kitchen = User::factory()->create();
        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $kitchen->id,
            'branch_id' => $this->branchA->id,
            'role' => StaffRole::Kitchen,
        ]);
        Sanctum::actingAs($kitchen);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue")->assertForbidden();
    }

    public function test_customer_without_membership_cannot_manage_queue(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue")->assertForbidden();
    }

    public function test_host_can_create_walk_in_ticket(): void
    {
        Sanctum::actingAs($this->host);

        $this->postJson("/api/v1/staff/branches/{$this->branchA->id}/queue-tickets", [
            'party_size' => 3,
            'guest_name' => 'Pak Budi',
        ])
            ->assertCreated()
            ->assertJsonPath('data.source', 'walk_in')
            ->assertJsonPath('data.customer_name', 'Pak Budi')
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.ticket_number', 1);

        $ticket = QueueTicket::firstOrFail();
        $this->assertSame(QueueTicketSource::WalkIn, $ticket->source);
        $this->assertNull($ticket->user_id);
        $this->assertSame($this->host->id, $ticket->statusHistories()->first()->changed_by);
    }

    public function test_call_next_picks_lowest_waiting_number_and_skips_cancelled(): void
    {
        $t1 = $this->customerTicket($this->branchA);
        $t2 = $this->customerTicket($this->branchA);
        $t3 = $this->customerTicket($this->branchA);
        $this->service->transition($t1, QueueTicketStatus::Cancelled);

        Sanctum::actingAs($this->host);

        $this->postJson("/api/v1/staff/branches/{$this->branchA->id}/queue/call-next")
            ->assertOk()
            ->assertJsonPath('data.id', $t2->id)
            ->assertJsonPath('data.status', 'called');

        $this->postJson("/api/v1/staff/branches/{$this->branchA->id}/queue/call-next")
            ->assertOk()
            ->assertJsonPath('data.id', $t3->id);

        $this->postJson("/api/v1/staff/branches/{$this->branchA->id}/queue/call-next")
            ->assertNotFound();
    }

    public function test_full_flow_records_history(): void
    {
        $ticket = $this->customerTicket($this->branchA);
        Sanctum::actingAs($this->host);

        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/call")->assertOk()->assertJsonPath('data.status', 'called');
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/seat")->assertOk()->assertJsonPath('data.status', 'seated');
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/complete")->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertSame(4, $ticket->statusHistories()->count());
    }

    public function test_no_show_requires_called_status(): void
    {
        $ticket = $this->customerTicket($this->branchA);
        Sanctum::actingAs($this->host);

        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/no-show")->assertStatus(409);

        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/call")->assertOk();
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/no-show")
            ->assertOk()
            ->assertJsonPath('data.status', 'no_show');
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $ticket = $this->customerTicket($this->branchA);
        Sanctum::actingAs($this->host);

        // waiting tidak bisa langsung duduk atau selesai
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/seat")->assertStatus(409);
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/complete")->assertStatus(409);

        // tiket yang sudah batal tidak bisa dipanggil lagi
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/cancel")->assertOk();
        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/call")->assertStatus(409);
    }

    public function test_status_filter_is_validated(): void
    {
        Sanctum::actingAs($this->host);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/queue?status=ngawur")->assertStatus(422);
    }
}
