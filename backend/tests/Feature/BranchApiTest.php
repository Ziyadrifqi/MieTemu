<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(string $status = 'active'): Business
    {
        return Business::create(['name' => 'Resto A', 'slug' => 'resto-a-' . uniqid(), 'status' => $status]);
    }

    public function test_lists_only_active_branches(): void
    {
        $business = $this->makeBusiness();
        Branch::create(['business_id' => $business->id, 'name' => 'Cabang 1']);
        Branch::create(['business_id' => $business->id, 'name' => 'Cabang Tutup', 'is_active' => false]);

        $this->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cabang 1');
    }

    public function test_shows_branch_with_hours(): void
    {
        $business = $this->makeBusiness();
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Cabang 1']);
        BusinessHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => 1,
            'open_time' => '10:00:00',
            'close_time' => '21:00:00',
        ]);

        $this->getJson("/api/v1/branches/{$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.hours.0.open_time', '10:00')
            ->assertJsonPath('data.hours.0.close_time', '21:00');
    }

    public function test_inactive_branch_returns_404(): void
    {
        $business = $this->makeBusiness();
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Cabang Tutup',
            'is_active' => false,
        ]);

        $this->getJson("/api/v1/branches/{$branch->id}")->assertNotFound();
    }

    public function test_branch_of_suspended_business_is_hidden(): void
    {
        $business = $this->makeBusiness('suspended');
        Branch::create(['business_id' => $business->id, 'name' => 'Cabang 1']);

        $this->getJson('/api/v1/branches')->assertOk()->assertJsonCount(0, 'data');
    }
}
