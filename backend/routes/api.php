
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\Staff\BranchController as StaffBranchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Public Branch Routes
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);

    // Password Reset
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:5,1');

    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Staff Branch Routes
        Route::prefix('staff')->group(function () {
            Route::get('/branches', [StaffBranchController::class, 'index']);
            Route::get('/branches/{branch}', [StaffBranchController::class, 'show']);
        });
    });
});
