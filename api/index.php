<?php
// Vercel serverless entry point for Laravel API

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Set working directory to Laravel root
chdir(__DIR__ . '/..');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Handle the request through Laravel
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
