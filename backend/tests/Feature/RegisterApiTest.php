<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    public function test_visitor_can_register_and_use_token(): void
    {
        $response = $this->postJson(
            '/api/v1/auth/register',
            $this->payload()
        )
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('user.email', 'budi@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'is_super_admin' => false,
        ]);

        $this->withHeader(
            'Authorization',
            'Bearer ' . $response->json('token')
        )
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.name', 'Budi Santoso');
    }

    public function test_registered_user_can_login_with_password(): void
    {
        $this->postJson(
            '/api/v1/auth/register',
            $this->payload()
        )->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertOk();
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $this->postJson(
            '/api/v1/auth/register',
            $this->payload([
                'email' => '  Budi@Example.COM ',
            ])
        )
            ->assertCreated()
            ->assertJsonPath('user.email', 'budi@example.com');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
        ]);

        $this->postJson(
            '/api/v1/auth/register',
            $this->payload()
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_password_rules_are_enforced(): void
    {
        $this->postJson(
            '/api/v1/auth/register',
            $this->payload([
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->postJson(
            '/api/v1/auth/register',
            $this->payload([
                'password_confirmation' => 'beda12345',
            ])
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_required_fields_are_validated(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);
    }

    public function test_cannot_make_self_super_admin(): void
    {
        $this->postJson(
            '/api/v1/auth/register',
            $this->payload(['is_super_admin' => true])
        )->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'is_super_admin' => false,
        ]);
    }

    public function test_new_visitor_has_no_staff_access(): void
    {
        $token = $this->postJson(
            '/api/v1/auth/register',
            $this->payload()
        )
            ->assertCreated()
            ->json('token');

        $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )
            ->getJson('/api/v1/staff/branches')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
