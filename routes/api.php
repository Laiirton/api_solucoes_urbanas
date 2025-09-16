<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\GeolocationController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    
    Route::apiResource('service-requests', ServiceRequestController::class);

    Route::get('geolocation', GeolocationController::class);

    
});

Route::apiResource('users', UserController::class)->parameters(['users' => 'id']);
