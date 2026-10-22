<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->email_verified_at) {
            return response()->json([
                'message' => 'Verifikasi email Anda terlebih dahulu.',
                'code' => 'email_not_verified',
            ], 403);
        }

        return $next($request);
    }
}
