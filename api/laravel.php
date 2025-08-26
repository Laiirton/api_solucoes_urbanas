<?php
// Laravel entry point for Vercel

define('LARAVEL_START', microtime(true));

// Set the working directory to the Laravel root
$laravelRoot = dirname(__DIR__);
chdir($laravelRoot);

// Debug: Check if files exist
$composerPath = $laravelRoot . '/vendor/autoload.php';
$bootstrapPath = $laravelRoot . '/bootstrap/app.php';

if (!file_exists($composerPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Composer autoload not found', 'path' => $composerPath, 'cwd' => getcwd()]);
    exit;
}

if (!file_exists($bootstrapPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Bootstrap file not found', 'path' => $bootstrapPath, 'cwd' => getcwd()]);
    exit;
}

// Check if maintenance mode is enabled
if (file_exists($maintenance = $laravelRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader
require $composerPath;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

try {
    // Bootstrap Laravel
    $app = require_once $bootstrapPath;

    // Handle the request
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = Request::capture();
    $response = $kernel->handle($request);
    $response->send();

    $kernel->terminate($request, $response);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Laravel bootstrap failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}