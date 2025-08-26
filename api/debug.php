<?php
// Simple debug endpoint for Vercel
header('Content-Type: application/json');

echo json_encode([
    'status' => 'working',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'not set',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
        'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'not set',
    ],
    'php_version' => phpversion(),
    'working_directory' => getcwd(),
    'file_location' => __FILE__
]);
?>