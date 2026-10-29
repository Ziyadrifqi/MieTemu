<?php

namespace Tests\Feature\Super;

use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminWebTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_super_admin' => true])->save();

        return $user;
    }

    private function hoursPayload(array $override = []): array
    {
        $hours = [];
        for ($d = 1; $d <= 7; $d++) {
            $hours[$d] = ['open_time' => '09:00', 'close_time' => '22:00'];
        }

        return array_replace($hours, $override);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('super.businesses.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_super_pages(): void
    {
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);
        $admin = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $admin->id,
            'branch_id' => $branch->id,
            'role' => StaffRole::Admin,
        ]);

        $this->actingAs($admin)->get(route('super.businesses.index'))->assertForbidden();
    }

    public function test_super_admin_without_membership_can_log_in_to_the_web(): void
    {
        $user = $this->superAdmin();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJson(['redirect' => route('super.businesses.index')]);
    }

    public function test_super_admin_creates_business_with_unique_slug(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('super.businesses.store'), ['name' => 'Mie Temu'])->assertRedirect();
        $this->actingAs($super)->post(route('super.businesses.store'), ['name' => 'Mie Temu'])->assertRedirect();

        $this->assertDatabaseHas('businesses', ['slug' => 'mie-temu']);
        $this->assertDatabaseHas('businesses', ['slug' => 'mie-temu-2']);
    }

    public function test_creating_branch_seeds_seven_default_days(): void
    {
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->post(route('super.branches.store', $business), ['name' => 'Cabang Baru'])
            ->assertRedirect();

        $branch = Branch::where('name', 'Cabang Baru')->firstOrFail();
        $this->assertCount(7, $branch->hours);
        $this->assertTrue($branch->accepts_queue);
    }

    public function test_branch_hours_can_be_updated_and_a_day_closed(): void
    {
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);

        $payload = [
            'name' => 'Cabang A',
            'accepts_queue' => '0',
            'hours' => $this->hoursPayload([7 => ['open_time' => '', 'close_time' => '', 'is_closed' => '1']]),
        ];

        $this->actingAs($this->superAdmin())
            ->put(route('super.branches.update', $branch), $payload)
            ->assertRedirect(route('super.branches.edit', $branch));

        $branch->refresh()->load('hours');
        $this->assertFalse($branch->accepts_queue);
        $this->assertSame('09:00:00', $branch->hours->firstWhere('day_of_week', 1)->open_time);
        $this->assertTrue((bool) $branch->hours->firstWhere('day_of_week', 7)->is_closed);
    }

    public function test_close_time_must_be_after_open_time(): void
    {
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);

        $this->actingAs($this->superAdmin())
            ->from(route('super.branches.edit', $branch))
            ->put(route('super.branches.update', $branch), [
                'name' => 'Cabang A',
                'hours' => $this->hoursPayload([1 => ['open_time' => '20:00', 'close_time' => '10:00']]),
            ])
            ->assertSessionHasErrors('hours.1.close_time');
    }

    public function test_suspended_business_blocks_its_staff(): void
    {
        $super = $this->superAdmin();
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);
        $host = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $host->id,
            'branch_id' => $branch->id,
            'role' => StaffRole::Host,
        ]);

        Sanctum::actingAs($host);
        $this->getJson('/api/v1/staff/branches')->assertJsonCount(1, 'data');

        $this->actingAs($super)
            ->patch(route('super.businesses.status', $business), ['status' => 'suspended'])
            ->assertRedirect();

        Sanctum::actingAs($host);
        $this->getJson('/api/v1/staff/branches')->assertJsonCount(0, 'data');
    }

    public function test_deactivated_branch_is_hidden_from_staff_and_denied(): void
    {
        $business = Business::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang A']);
        $host = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $host->id,
            'branch_id' => $branch->id,
            'role' => StaffRole::Host,
        ]);

        $this->actingAs($this->superAdmin())
            ->patch(route('super.branches.status', $branch), ['active' => '0'])
            ->assertRedirect();

        Sanctum::actingAs($host);
        $this->getJson('/api/v1/staff/branches')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/staff/branches/{$branch->id}")->assertForbidden();
    }

    public function test_super_admin_command_creates_account(): void
    {
        $this->artisan('mietemu:super-admin', ['email' => 'boss@example.com'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-panjang-1')
            ->expectsQuestion('Ulangi password', 'rahasia-panjang-1')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'boss@example.com')->firstOrFail()->isSuperAdmin());
    }
}
