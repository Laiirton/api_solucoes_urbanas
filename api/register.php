<?php
// Laravel Auth Register endpoint
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    
    // Create request for /api/auth/register
    $request = \Illuminate\Http\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/api/auth/register');
    $request->server->set('REQUEST_METHOD', 'POST');
    
    $response = $kernel->handle($request);
    $response->send();
    
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>