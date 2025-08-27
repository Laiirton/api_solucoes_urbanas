<?php
// Laravel Auth Register endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('LARAVEL_START', microtime(true));
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
    
    $_SERVER['REQUEST_URI'] = '/api/auth/register';
    $_SERVER['PATH_INFO'] = '/auth/register';
    
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = \Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>