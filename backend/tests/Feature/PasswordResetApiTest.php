<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Enums\StaffRole;
use App\Models\Business;
use App\Models\BusinessMember;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;


class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_uses_indonesia_timezone(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    public function test_forgot_password_sends_notification_for_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'tidakada@example.com'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'passwordlama1']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'passwordbaru123',
        ])->assertOk();
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertStatus(422);
    }

    public function test_reset_page_is_displayed(): void
    {
        $this->get('/reset-password/abc123?email=test@example.com')
            ->assertOk()
            ->assertSee('Reset Password');
    }

    public function test_reset_link_uses_app_url_not_request_host(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        // Simulasi permintaan dari emulator Android (host 10.0.2.2).
        $this->withServerVariables(['HTTP_HOST' => '10.0.2.2:8080'])
            ->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_starts_with($mail->actionUrl, rtrim(config('app.url'), '/') . '/reset-password/');
        });
    }

    public function test_reset_response_tells_whether_account_is_staff(): void
    {
        $payload = fn(User $user) => [
            'email' => $user->email,
            'token' => Password::createToken($user),
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ];

        $visitor = User::factory()->create();
        $this->postJson('/api/v1/auth/reset-password', $payload($visitor))
            ->assertOk()
            ->assertJsonPath('staff', false);

        $business = Business::create(['name' => 'Resto A', 'slug' => 'resto-a', 'status' => 'active']);
        $staff = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'branch_id' => null,
            'role' => StaffRole::Admin,
        ]);

        $this->postJson('/api/v1/auth/reset-password', $payload($staff))
            ->assertOk()
            ->assertJsonPath('staff', true);
    }
}
