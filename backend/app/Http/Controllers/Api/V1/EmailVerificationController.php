<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailCodeRequest;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $service) {}

    public function verify(VerifyEmailCodeRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->service->verify($user, $request->validated('code'));

        return response()->json([
            'message' => 'Email berhasil diverifikasi.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->fresh()->email_verified_at !== null,
            ],
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $this->service->send($request->user());

        return response()->json(['message' => 'Kode verifikasi baru sudah dikirim ke email Anda.']);
    }
}
