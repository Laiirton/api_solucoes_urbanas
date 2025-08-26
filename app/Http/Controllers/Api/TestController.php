<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'API funcionando corretamente!',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    }
}