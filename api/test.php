<?php
// Laravel Test endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('LARAVEL_START', microtime(true));
chdir(dirname(__DIR__));

try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    
    $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
    
    $_SERVER['REQUEST_URI'] = '/api/test';
    $_SERVER['PATH_INFO'] = '/test';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = \Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Laravel bootstrap failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}