<?php
// Vercel serverless entry point for Laravel API

define('LARAVEL_START', microtime(true));

// Define the path constants
$public_path = realpath(__DIR__ . '/../public');
$app_path = realpath(__DIR__ . '/../');

// Change to the app directory
chdir($app_path);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $app_path . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $app_path . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $app_path . '/bootstrap/app.php';

// Capture the request
$request = Request::capture();

// Handle the request
$response = $app->handleRequest($request);

// Send the response
if ($response) {
    $response->send();
}
