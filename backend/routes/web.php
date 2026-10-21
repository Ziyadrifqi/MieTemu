<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Middleware\EnsureStaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', fn() => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureStaffMember::class])->group(function () {
    Route::get('/dashboard', fn(Request $request) => view('dashboard', ['user' => $request->user()]))
        ->name('dashboard');

    Route::prefix('staff-api')->group(base_path('routes/staff.php'));
});

Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => request('email', ''),
    ]);
})->name('password.reset');
