<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Enums\StaffRole;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessMember;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\Queue\QueueTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiningTableApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Branch $branchA;
    private Branch $branchB;
    private User $admin;
    private User $host;
    private QueueTicketService $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'Asia/Jakarta'));

        $this->business = Business::create(['name' => 'Resto A', 'slug' => 'resto-a', 'status' => 'active']);
        $this->branchA = $this->makeBranch('Cabang A');
        $this->branchB = $this->makeBranch('Cabang B');

        $this->admin = $this->staff(StaffRole::Admin);
        $this->host = $this->staff(StaffRole::Host, $this->branchA);

        $this->queue = app(QueueTicketService::class);
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

    private function staff(StaffRole $role, ?Branch $branch = null): User
    {
        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'branch_id' => $branch?->id,
            'role' => $role,
        ]);

        return $user;
    }

    private function table(Branch $branch, string $name, int $capacity): DiningTable
    {
        return DiningTable::create(['branch_id' => $branch->id, 'name' => $name, 'capacity' => $capacity]);
    }

    private function calledTicket(Branch $branch, int $size = 2): QueueTicket
    {
        $ticket = $this->queue->issue($branch, User::factory()->create(), $size);

        return $this->queue->transition($ticket, QueueTicketStatus::Called);
    }

    private function seatUrl(QueueTicket $ticket): string
    {
        return "/api/v1/staff/queue-tickets/{$ticket->id}/seat";
    }

    public function test_host_lists_tables_of_own_branch_only(): void
    {
        $this->table($this->branchA, 'T1', 2);
        $this->table($this->branchA, 'T2', 4);
        $this->table($this->branchB, 'X1', 2);
        Sanctum::actingAs($this->host);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/tables")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'T1')
            ->assertJsonPath('summary.available', 2);

        $this->getJson("/api/v1/staff/branches/{$this->branchB->id}/tables")->assertForbidden();
    }

    public function test_kitchen_staff_cannot_list_tables(): void
    {
        $kitchen = $this->staff(StaffRole::Kitchen, $this->branchA);
        Sanctum::actingAs($kitchen);

        $this->getJson("/api/v1/staff/branches/{$this->branchA->id}/tables")->assertForbidden();
    }

    public function test_only_admin_can_create_tables(): void
    {
        $url = "/api/v1/staff/branches/{$this->branchA->id}/tables";

        Sanctum::actingAs($this->host);
        $this->postJson($url, ['name' => 'T9', 'capacity' => 4])->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->postJson($url, ['name' => 'T9', 'capacity' => 4])
            ->assertCreated()
            ->assertJsonPath('data.name', 'T9')
            ->assertJsonPath('data.status', 'available');

        // Nama ganda dan kapasitas tidak valid ditolak
        $this->postJson($url, ['name' => 'T9', 'capacity' => 4])->assertStatus(422);
        $this->postJson($url, ['name' => 'T10', 'capacity' => 0])->assertStatus(422);
    }

    public function test_seat_complete_and_clean_cycle(): void
    {
        $table = $this->table($this->branchA, 'T1', 4);
        $ticket = $this->calledTicket($this->branchA, 3);
        Sanctum::actingAs($this->host);

        $this->postJson($this->seatUrl($ticket), ['dining_table_id' => $table->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'seated')
            ->assertJsonPath('data.table_name', 'T1');

        $this->assertSame(TableStatus::Occupied, $table->fresh()->status);
        $this->assertSame(1, DiningSession::whereNull('ended_at')->count());

        // Meja terisi tidak bisa dipakai tiket lain
        $second = $this->calledTicket($this->branchA, 2);
        $this->postJson($this->seatUrl($second), ['dining_table_id' => $table->id])->assertStatus(409);

        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/complete")->assertOk();
        $this->assertSame(TableStatus::NeedsCleaning, $table->fresh()->status);
        $this->assertSame(0, DiningSession::whereNull('ended_at')->count());

        $this->postJson("/api/v1/staff/tables/{$table->id}/clean")
            ->assertOk()
            ->assertJsonPath('data.status', 'available');
    }

    public function test_seat_is_rejected_for_invalid_tables(): void
    {
        $small = $this->table($this->branchA, 'T1', 2);
        $other = $this->table($this->branchB, 'X1', 8);
        $ticket = $this->calledTicket($this->branchA, 3);
        Sanctum::actingAs($this->host);

        // kapasitas kurang
        $this->postJson($this->seatUrl($ticket), ['dining_table_id' => $small->id])->assertStatus(422);
        // meja cabang lain
        $this->postJson($this->seatUrl($ticket), ['dining_table_id' => $other->id])->assertStatus(422);
        // tanpa meja
        $this->postJson($this->seatUrl($ticket), [])->assertStatus(422);
        // meja tidak ada
        $this->postJson($this->seatUrl($ticket), ['dining_table_id' => 99999])->assertStatus(404);

        $this->assertSame(QueueTicketStatus::Called, $ticket->fresh()->status);
    }

    public function test_clean_only_works_for_tables_that_need_cleaning(): void
    {
        $table = $this->table($this->branchA, 'T1', 4);
        Sanctum::actingAs($this->host);

        $this->postJson("/api/v1/staff/tables/{$table->id}/clean")->assertStatus(409);
    }

    public function test_deactivate_and_reactivate_table(): void
    {
        $table = $this->table($this->branchA, 'T1', 4);
        $ticket = $this->calledTicket($this->branchA, 2);

        Sanctum::actingAs($this->host);
        $this->postJson($this->seatUrl($ticket), ['dining_table_id' => $table->id])->assertOk();

        Sanctum::actingAs($this->admin);
        $url = "/api/v1/staff/tables/{$table->id}";

        // meja terisi tidak bisa dinonaktifkan
        $this->patchJson($url, ['is_active' => false])->assertStatus(409);

        $this->postJson("/api/v1/staff/queue-tickets/{$ticket->id}/complete")->assertOk();
        $this->postJson("/api/v1/staff/tables/{$table->id}/clean")->assertOk();

        $this->patchJson($url, ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        // meja nonaktif tidak bisa dipakai
        $next = $this->calledTicket($this->branchA, 2);
        $this->postJson($this->seatUrl($next), ['dining_table_id' => $table->id])->assertStatus(409);

        $this->patchJson($url, ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'available');
    }

    public function test_host_cannot_edit_tables(): void
    {
        $table = $this->table($this->branchA, 'T1', 4);
        Sanctum::actingAs($this->host);

        $this->patchJson("/api/v1/staff/tables/{$table->id}", ['capacity' => 6])->assertForbidden();
    }
}
