<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\VideoController;

// Rotas de health check
require __DIR__.'/health.php';

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

    Route::prefix('videos')->group(function () {
        Route::post('/upload', [VideoController::class, 'upload']);
        Route::delete('/delete', [VideoController::class, 'delete']);
        Route::get('/list', [VideoController::class, 'list']);
    });
});

