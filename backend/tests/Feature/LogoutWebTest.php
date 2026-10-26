<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_ends_session_and_redirects_to_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('status');

        $this->assertGuest();
    }

    public function test_idle_logout_shows_explanation(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout', ['reason' => 'idle'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }
}
