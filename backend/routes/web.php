<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Super\BranchController;
use App\Http\Controllers\Super\BusinessController;
use App\Http\Middleware\EnsureStaffMember;
use App\Http\Middleware\EnsureSuperAdmin;
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

// Route khusus Super Admin.
Route::middleware(['auth', EnsureSuperAdmin::class])
    ->prefix('super')
    ->name('super.')
    ->group(function () {
        Route::get('/restoran', [BusinessController::class, 'index'])->name('businesses.index');
        Route::post('/restoran', [BusinessController::class, 'store'])->name('businesses.store');
        Route::get('/restoran/{business}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::patch('/restoran/{business}', [BusinessController::class, 'update'])->name('businesses.update');
        Route::patch('/restoran/{business}/status', [BusinessController::class, 'status'])->name('businesses.status');

        Route::post('/restoran/{business}/cabang', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/cabang/{branch}', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/cabang/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::patch('/cabang/{branch}/status', [BranchController::class, 'status'])->name('branches.status');
    });

Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => request('email', ''),
    ]);
})->name('password.reset');
