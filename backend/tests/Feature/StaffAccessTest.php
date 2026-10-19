<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function business(string $slug = 'resto-a', string $status = 'active'): Business
    {
        return Business::create(['name' => strtoupper($slug), 'slug' => $slug, 'status' => $status]);
    }

    private function branch(Business $business, string $name): Branch
    {
        return Branch::create(['business_id' => $business->id, 'name' => $name]);
    }

    private function member(User $user, Business $business, StaffRole $role, ?Branch $branch = null): void
    {
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'branch_id' => $branch?->id,
            'role' => $role,
        ]);
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/v1/staff/branches')->assertUnauthorized();
    }

    public function test_user_without_membership_has_no_access(): void
    {
        $business = $this->business();
        $branch = $this->branch($business, 'Cabang A');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/staff/branches')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/staff/branches/{$branch->id}")->assertForbidden();
    }

    public function test_host_can_only_access_assigned_branch(): void
    {
        $business = $this->business();
        $a = $this->branch($business, 'Cabang A');
        $b = $this->branch($business, 'Cabang B');

        $host = User::factory()->create();
        $this->member($host, $business, StaffRole::Host, $a);
        Sanctum::actingAs($host);

        $this->getJson('/api/v1/staff/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cabang A')
            ->assertJsonPath('data.0.roles.0', 'host');

        $this->getJson("/api/v1/staff/branches/{$a->id}")
            ->assertOk()
            ->assertJsonPath('meta.roles.0', 'host');

        $this->getJson("/api/v1/staff/branches/{$b->id}")->assertForbidden();
    }

    public function test_admin_without_branch_restriction_accesses_all_branches_of_own_business_only(): void
    {
        $business1 = $this->business('resto-a');
        $business2 = $this->business('resto-b');
        $this->branch($business1, 'Cabang A');
        $this->branch($business1, 'Cabang B');
        $other = $this->branch($business2, 'Cabang C');

        $admin = User::factory()->create();
        $this->member($admin, $business1, StaffRole::Admin);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/staff/branches')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/v1/staff/branches/{$other->id}")->assertForbidden();
    }

    public function test_super_admin_has_no_automatic_access_to_tenant_data(): void
    {
        $business = $this->business();
        $branch = $this->branch($business, 'Cabang A');

        $superAdmin = User::factory()->create();
        $superAdmin->forceFill(['is_super_admin' => true])->save();
        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/staff/branches')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/staff/branches/{$branch->id}")->assertForbidden();
    }

    public function test_suspended_business_blocks_staff_access(): void
    {
        $business = $this->business('resto-a', 'suspended');
        $branch = $this->branch($business, 'Cabang A');

        $admin = User::factory()->create();
        $this->member($admin, $business, StaffRole::Admin);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/staff/branches')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/staff/branches/{$branch->id}")->assertForbidden();
    }

    public function test_policy_permissions_follow_roles(): void
    {
        $business = $this->business();
        $branch = $this->branch($business, 'Cabang A');

        $host = User::factory()->create();
        $kitchen = User::factory()->create();
        $admin = User::factory()->create();
        $this->member($host, $business, StaffRole::Host, $branch);
        $this->member($kitchen, $business, StaffRole::Kitchen, $branch);
        $this->member($admin, $business, StaffRole::Admin);

        $this->assertTrue($host->can('manageQueue', $branch));
        $this->assertFalse($host->can('manageKitchen', $branch));
        $this->assertFalse($host->can('manageBranch', $branch));

        $this->assertTrue($kitchen->can('manageKitchen', $branch));
        $this->assertFalse($kitchen->can('manageQueue', $branch));
        $this->assertFalse($kitchen->can('manageBranch', $branch));

        $this->assertTrue($admin->can('manageQueue', $branch));
        $this->assertTrue($admin->can('manageKitchen', $branch));
        $this->assertTrue($admin->can('manageBranch', $branch));
    }
}
