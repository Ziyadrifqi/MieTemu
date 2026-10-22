<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(): array
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated();

        return [
            User::where('email', 'budi@example.com')->firstOrFail(),
            $response->json('token'),
        ];
    }

    private function lastCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo(
            $user,
            VerifyEmailCodeNotification::class,
            function ($notification) use (&$code) {
                $code = $notification->code;

                return true;
            }
        );

        if (! is_string($code)) {
            $this->fail('Kode verifikasi email tidak ditemukan.');
        }

        return $code;
    }

    private function verify(string $token, string $code)
    {
        return $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/v1/auth/email/verify', [
            'code' => $code,
        ]);
    }

    public function test_register_creates_unverified_user_and_sends_code(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.email_verified', false)
            ->assertJsonPath('verification_sent', true);

        $user = User::where('email', 'budi@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $this->lastCode($user)
        );
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_code_is_not_stored_in_plain_text(): void
    {
        Notification::fake();

        [$user] = $this->register();

        $record = EmailVerificationCode::where(
            'user_id',
            $user->id
        )->firstOrFail();

        $this->assertNotSame(
            $this->lastCode($user),
            $record->code_hash
        );
    }

    public function test_correct_code_verifies_email(): void
    {
        Notification::fake();

        [$user, $token] = $this->register();

        $this->verify($token, $this->lastCode($user))
            ->assertOk()
            ->assertJsonPath('user.email_verified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame(0, EmailVerificationCode::count());
    }

    public function test_wrong_code_is_rejected_and_locks_after_max_attempts(): void
    {
        Notification::fake();

        [$user, $token] = $this->register();

        $correct = $this->lastCode($user);
        $wrong = $correct === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->verify($token, $wrong)->assertStatus(422);
        }

        $this->verify($token, $correct)->assertStatus(422);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        Notification::fake();

        [$user, $token] = $this->register();

        $code = $this->lastCode($user);

        $this->travel(11)->minutes();

        $this->verify($token, $code)->assertStatus(422);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_has_cooldown_and_replaces_old_code(): void
    {
        Notification::fake();

        [$user, $token] = $this->register();

        $oldCode = $this->lastCode($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/email/resend')
            ->assertStatus(429);

        $this->travel(61)->seconds();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/email/resend')
            ->assertOk();

        $newCode = $this->lastCode($user);

        if ($newCode !== $oldCode) {
            $this->verify($token, $oldCode)->assertStatus(422);
        }

        $this->verify($token, $newCode)->assertOk();
    }



    public function test_code_must_be_six_digits_and_user_must_be_authenticated(): void
    {
        Notification::fake();

        [, $token] = $this->register();

        // Kode dengan format tidak valid harus ditolak.
        $this->verify($token, '12ab')
            ->assertStatus(422);

        // Bersihkan state autentikasi yang mungkin tersimpan di test.
        $this->app['auth']->forgetGuards();

        // Request verifikasi tanpa token harus mendapat 401.
        $this->withoutHeader('Authorization')
            ->postJson('/api/v1/auth/email/verify', [
                'code' => '123456',
            ])
            ->assertUnauthorized();

        // Bersihkan kembali sebelum menguji endpoint resend.
        $this->app['auth']->forgetGuards();

        $this->withoutHeader('Authorization')
            ->postJson('/api/v1/auth/email/resend')
            ->assertUnauthorized();
    }

    public function test_unverified_user_cannot_take_queue_ticket_but_verified_user_can(): void
    {
        Notification::fake();

        $this->travelTo(
            Carbon::parse('2026-10-19 12:00:00', 'Asia/Jakarta')
        );

        $business = Business::create([
            'name' => 'Resto A',
            'slug' => 'resto-a',
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Cabang A',
        ]);

        for ($day = 1; $day <= 7; $day++) {
            BusinessHour::create([
                'branch_id' => $branch->id,
                'day_of_week' => $day,
                'open_time' => '10:00:00',
                'close_time' => '21:00:00',
            ]);
        }

        [$user, $token] = $this->register();

        $url = "/api/v1/branches/{$branch->id}/queue-tickets";

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($url, ['party_size' => 2])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->verify($token, $this->lastCode($user))
            ->assertOk();

        // Reset guard agar status verifikasi terbaru terbaca.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($url, ['party_size' => 2])
            ->assertCreated();
    }
}
