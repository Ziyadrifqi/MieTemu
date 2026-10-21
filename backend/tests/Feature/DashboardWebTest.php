<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWebTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Resto A', 'slug' => 'resto-a', 'status' => 'active']);
        $this->branchA = Branch::create(['business_id' => $this->business->id, 'name' => 'Cabang A']);
        $this->branchB = Branch::create(['business_id' => $this->business->id, 'name' => 'Cabang B']);
    }

    private function staff(StaffRole $role = StaffRole::Host, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'branch_id' => $branch?->id,
            'role' => $role,
        ]);

        return $user;
    }

    public function test_login_and_forgot_password_pages_are_displayed(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk Staf');
        $this->get('/forgot-password')->assertOk()->assertSee('Lupa Password');
    }

    public function test_staff_can_login_and_is_sent_to_dashboard(): void
    {
        $user = $this->staff(StaffRole::Host, $this->branchA);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->staff();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_user_without_staff_membership_cannot_login_to_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Akun ini tidak memiliki akses staf.');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->getJson('/staff-api/branches')->assertUnauthorized();
    }

    public function test_staff_can_open_dashboard(): void
    {
        $user = $this->staff(StaffRole::Host, $this->branchA);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Antrean')
            ->assertSee($user->name);
    }

    public function test_logged_in_non_staff_is_sent_back_to_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_session_can_use_staff_api_but_only_for_own_branch(): void
    {
        $host = $this->staff(StaffRole::Host, $this->branchA);

        $this->actingAs($host)->getJson('/staff-api/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cabang A');

        $this->actingAs($host)->getJson("/staff-api/branches/{$this->branchA->id}/queue")->assertOk();
        $this->actingAs($host)->getJson("/staff-api/branches/{$this->branchB->id}/queue")->assertForbidden();
    }

    public function test_logout_clears_session(): void
    {
        $user = $this->staff();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
