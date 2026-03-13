<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// System endpoints (public)
Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Documents
    Route::apiResource('documents', DocumentController::class)->only([
        'index', 'store', 'show', 'destroy',
    ]);

    // Chat sessions
    Route::apiResource('chat-sessions', ChatSessionController::class)->only([
        'index', 'store', 'show', 'destroy',
    ]);

    // Chat messages
    Route::get('chat-sessions/{chatSession}/messages', [ChatMessageController::class, 'index']);
    Route::post('chat-sessions/{chatSession}/messages', [ChatMessageController::class, 'store']);
});
