<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function send(User $user): void
    {
        if ($user->email_verified_at) {
            throw ValidationException::withMessages(['email' => ['Email sudah terverifikasi.']])->status(409);
        }

        $cooldown = (int) config('mietemu.verification.resend_cooldown_seconds');
        $ttl = (int) config('mietemu.verification.code_ttl_minutes');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $cooldown, $ttl, $code) {
            $existing = EmailVerificationCode::where('user_id', $user->id)->lockForUpdate()->first();

            if ($existing && $existing->last_sent_at->copy()->addSeconds($cooldown)->isFuture()) {
                $wait = max(1, (int) ceil(now()->diffInSeconds($existing->last_sent_at->copy()->addSeconds($cooldown))));

                throw ValidationException::withMessages([
                    'email' => ["Tunggu {$wait} detik sebelum meminta kode baru."],
                ])->status(429);
            }

            EmailVerificationCode::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'code_hash' => $this->hash($code),
                    'expires_at' => now()->addMinutes($ttl),
                    'attempts' => 0,
                    'last_sent_at' => now(),
                ]
            );
        });

        $user->notify(new VerifyEmailCodeNotification($code, $ttl));
    }

    public function verify(User $user, string $code): void
    {
        if ($user->email_verified_at) {
            return;
        }

        $maxAttempts = (int) config('mietemu.verification.max_attempts');

        // Hasil dikembalikan dulu, error dilempar setelah transaksi selesai,
        // supaya penambahan jumlah percobaan salah tidak ikut dibatalkan (rollback).
        $result = DB::transaction(function () use ($user, $code, $maxAttempts) {
            $record = EmailVerificationCode::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $record || $record->expires_at->isPast()) {
                return 'expired';
            }

            if ($record->attempts >= $maxAttempts) {
                return 'locked';
            }

            if (! hash_equals($record->code_hash, $this->hash($code))) {
                $record->increment('attempts');

                return 'wrong';
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            $record->delete();

            return 'ok';
        });

        $message = match ($result) {
            'expired' => 'Kode sudah kedaluwarsa atau tidak ditemukan. Minta kode baru.',
            'locked' => 'Terlalu banyak percobaan salah. Minta kode baru.',
            'wrong' => 'Kode salah.',
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['code' => [$message]]);
        }
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
