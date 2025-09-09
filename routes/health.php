<?php

use Illuminate\Support\Facades\Route;
use Exception;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used for health checking the application.
|
*/

Route::get('/health', function () {
    try {
        // Verificar se a aplicação está funcionando
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'api-solucoes-urbanas'
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});