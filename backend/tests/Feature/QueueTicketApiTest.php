<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueueTicketApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin, 19 Oktober 2026 pukul 12:00 WIB
        $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'Asia/Jakarta'));

        $business = Business::create(['name' => 'Resto A', 'slug' => 'resto-a', 'status' => 'active']);
        $this->branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);

        for ($day = 1; $day <= 7; $day++) {
            BusinessHour::create([
                'branch_id' => $this->branch->id,
                'day_of_week' => $day,
                'open_time' => '10:00:00',
                'close_time' => '21:00:00',
            ]);
        }
    }

    private function take(User $user, int $partySize = 2): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson("/api/v1/branches/{$this->branch->id}/queue-tickets", [
            'party_size' => $partySize,
        ]);
    }

    public function test_guest_cannot_take_ticket(): void
    {
        $this->postJson("/api/v1/branches/{$this->branch->id}/queue-tickets", ['party_size' => 2])
            ->assertUnauthorized();
    }

    public function test_user_can_take_ticket(): void
    {
        $this->take(User::factory()->create())
            ->assertCreated()
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.ticket_number', 1)
            ->assertJsonPath('data.code', 'A-001')
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.estimated_wait_minutes', 0)
            ->assertJsonPath('data.branch.name', 'Cabang A');
    }

    public function test_ticket_numbers_are_sequential_and_unique(): void
    {
        $numbers = [];

        for ($i = 0; $i < 5; $i++) {
            $numbers[] = $this->take(User::factory()->create())
                ->assertCreated()
                ->json('data.ticket_number');
        }

        $this->assertSame([1, 2, 3, 4, 5], $numbers);
        $this->assertSame(5, QueueTicket::count());
        $this->assertSame(5, QueueTicket::query()->distinct()->count('ticket_number'));
    }

    public function test_same_user_cannot_hold_two_active_tickets(): void
    {
        $user = User::factory()->create();

        $this->take($user)->assertCreated();
        $this->take($user)->assertStatus(409);

        $this->assertSame(1, QueueTicket::count());
    }

    public function test_ticket_is_rejected_outside_operating_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 22:00:00', 'Asia/Jakarta'));

        $this->take(User::factory()->create())->assertStatus(422);
        $this->assertSame(0, QueueTicket::count());
    }

    public function test_ticket_is_rejected_when_branch_does_not_accept_queue(): void
    {
        $this->branch->update(['accepts_queue' => false]);

        $this->take(User::factory()->create())->assertStatus(422);
    }

    public function test_ticket_is_rejected_on_closed_day(): void
    {
        BusinessHour::where('branch_id', $this->branch->id)
            ->where('day_of_week', 1)
            ->update(['is_closed' => true]);

        $this->take(User::factory()->create())->assertStatus(422);
    }

    public function test_party_size_is_validated(): void
    {
        $user = User::factory()->create();

        $this->take($user, 0)->assertStatus(422);
        $this->take($user, 21)->assertStatus(422);
    }

    public function test_owner_can_view_ticket_and_others_cannot(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $id = $this->take($owner)->json('data.id');

        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/queue-tickets/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        Sanctum::actingAs($other);
        $this->getJson("/api/v1/queue-tickets/{$id}")->assertForbidden();
    }

    public function test_position_and_estimated_wait_reflect_people_ahead(): void
    {
        $this->take(User::factory()->create())->assertCreated();

        $this->take(User::factory()->create())
            ->assertCreated()
            ->assertJsonPath('data.position', 2)
            ->assertJsonPath('data.estimated_wait_minutes', config('mietemu.queue.minutes_per_ticket'));
    }

    public function test_owner_can_cancel_ticket_and_history_is_recorded(): void
    {
        $user = User::factory()->create();
        $id = $this->take($user)->json('data.id');

        $this->postJson("/api/v1/queue-tickets/{$id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.position', null);

        $this->assertSame(2, QueueTicket::findOrFail($id)->statusHistories()->count());

        // Membatalkan dua kali ditolak
        $this->postJson("/api/v1/queue-tickets/{$id}/cancel")->assertStatus(409);

        // Setelah batal, pengguna boleh mengantre lagi
        $this->take($user)->assertCreated();
    }

    public function test_ticket_number_resets_on_new_day(): void
    {
        $this->take(User::factory()->create())->assertCreated();

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00', 'Asia/Jakarta'));

        $this->take(User::factory()->create())
            ->assertCreated()
            ->assertJsonPath('data.ticket_number', 1);

        $this->assertSame(2, QueueSession::count());
    }

    public function test_database_rejects_duplicate_ticket_numbers(): void
    {
        $session = QueueSession::create([
            'branch_id' => $this->branch->id,
            'session_date' => '2026-10-19',
            'status' => 'open',
        ]);

        $data = [
            'queue_session_id' => $session->id,
            'branch_id' => $this->branch->id,
            'ticket_number' => 1,
            'party_size' => 2,
            'status' => QueueTicketStatus::Waiting,
        ];

        QueueTicket::create($data);

        $this->expectException(QueryException::class);
        QueueTicket::create($data);
    }

    public function test_status_transitions_follow_rules(): void
    {
        $this->assertTrue(QueueTicketStatus::Waiting->canTransitionTo(QueueTicketStatus::Called));
        $this->assertFalse(QueueTicketStatus::Waiting->canTransitionTo(QueueTicketStatus::Seated));
        $this->assertFalse(QueueTicketStatus::Completed->canTransitionTo(QueueTicketStatus::Called));
        $this->assertFalse(QueueTicketStatus::NoShow->canTransitionTo(QueueTicketStatus::Waiting));
    }
}
