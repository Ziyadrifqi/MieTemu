
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\QueueTicketController;
use App\Http\Controllers\Api\V1\Staff\BranchController as StaffBranchController;
use App\Http\Controllers\Api\V1\Staff\QueueController as StaffQueueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Public Branch Routes
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);

    // Password Reset Routes
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:5,1');

    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Authentication
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Staff Branch Routes
        Route::prefix('staff')->group(function () {
            Route::get('/branches', [StaffBranchController::class, 'index']);
            Route::get('/branches/{branch}', [StaffBranchController::class, 'show']);
            Route::get('/branches/{branch}/queue', [StaffQueueController::class, 'index']);
            Route::post('/branches/{branch}/queue-tickets', [StaffQueueController::class, 'storeWalkIn']);
            Route::post('/branches/{branch}/queue/call-next', [StaffQueueController::class, 'callNext']);

            Route::post('/queue-tickets/{ticket}/call', [StaffQueueController::class, 'call']);
            Route::post('/queue-tickets/{ticket}/seat', [StaffQueueController::class, 'seat']);
            Route::post('/queue-tickets/{ticket}/no-show', [StaffQueueController::class, 'noShow']);
            Route::post('/queue-tickets/{ticket}/cancel', [StaffQueueController::class, 'cancel']);
            Route::post('/queue-tickets/{ticket}/complete', [StaffQueueController::class, 'complete']);
        });

        // Queue Ticket Routes
        Route::post(
            '/branches/{branch}/queue-tickets',
            [QueueTicketController::class, 'store']
        )->middleware('throttle:10,1');

        Route::get(
            '/queue-tickets/{ticket}',
            [QueueTicketController::class, 'show']
        );

        Route::post(
            '/queue-tickets/{ticket}/cancel',
            [QueueTicketController::class, 'cancel']
        );
    });
});
