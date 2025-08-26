<?php
// Universal Laravel endpoint for API routes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('LARAVEL_START', microtime(true));
chdir(dirname(__DIR__));

try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    
    $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
    
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Create request from globals (this will capture the actual request)
    $request = \Illuminate\Http\Request::createFromGlobals();
    
    $response = $kernel->handle($request);
    $response->send();
    
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Laravel execution failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}