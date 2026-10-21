<?php

use App\Http\Controllers\Api\V1\Staff\BranchController;
use App\Http\Controllers\Api\V1\Staff\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/branches', [BranchController::class, 'index']);
Route::get('/branches/{branch}', [BranchController::class, 'show']);

Route::get('/branches/{branch}/queue', [QueueController::class, 'index']);
Route::post('/branches/{branch}/queue-tickets', [QueueController::class, 'storeWalkIn']);
Route::post('/branches/{branch}/queue/call-next', [QueueController::class, 'callNext']);

Route::post('/queue-tickets/{ticket}/call', [QueueController::class, 'call']);
Route::post('/queue-tickets/{ticket}/seat', [QueueController::class, 'seat']);
Route::post('/queue-tickets/{ticket}/no-show', [QueueController::class, 'noShow']);
Route::post('/queue-tickets/{ticket}/cancel', [QueueController::class, 'cancel']);
Route::post('/queue-tickets/{ticket}/complete', [QueueController::class, 'complete']);
