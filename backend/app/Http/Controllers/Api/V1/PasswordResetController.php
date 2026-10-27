<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Respons selalu sama supaya email yang terdaftar tidak bisa ditebak orang lain.
        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password sudah dikirim.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $isStaff = false;
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$isStaff) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Semua sesi login lama dicabut setelah password diganti.
                $user->tokens()->delete();

                // Menentukan tautan lanjutan di halaman reset (staf ke dashboard, pengunjung kembali ke aplikasi).
                $isStaff = $user->businessMemberships()->exists();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Tautan reset tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login dengan password baru.',
            'staff' => $isStaff,
        ]);
    }
}
