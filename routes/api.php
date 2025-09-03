<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ImageController;

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
    
    Route::prefix('images')->group(function () {
        Route::post('/upload', [ImageController::class, 'upload']);
        Route::delete('/delete', [ImageController::class, 'delete']);
        Route::get('/list', [ImageController::class, 'list']);
    });
});

