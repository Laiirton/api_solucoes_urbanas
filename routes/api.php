<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ApiDocumentationController;

// API Documentation - Lista todos os endpoints disponíveis
Route::get('/', [ApiDocumentationController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth.jwt')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth.jwt')->group(function () {
    Route::apiResource('users', UserController::class)->parameters(['users' => 'id']);
    
    Route::apiResource('service-requests', ServiceRequestController::class);
    Route::patch('service-requests/{id}/status', [ServiceRequestController::class, 'updateStatus']);
    Route::get('users/{userId}/service-requests', [ServiceRequestController::class, 'getByUser']);
});

