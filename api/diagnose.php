<?php
// Laravel Diagnostic endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$diagnostics = [
    'status' => 'checking',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check working directory
$diagnostics['checks']['working_dir'] = getcwd();

// Check if we can change directory
$laravelRoot = dirname(__DIR__);
$diagnostics['checks']['laravel_root'] = $laravelRoot;
$diagnostics['checks']['chdir_success'] = chdir($laravelRoot);
$diagnostics['checks']['current_dir_after_chdir'] = getcwd();

// Check if vendor exists
$vendorPath = $laravelRoot . '/vendor/autoload.php';
$diagnostics['checks']['vendor_exists'] = file_exists($vendorPath);
$diagnostics['checks']['vendor_path'] = $vendorPath;

// Check if bootstrap exists
$bootstrapPath = $laravelRoot . '/bootstrap/app.php';
$diagnostics['checks']['bootstrap_exists'] = file_exists($bootstrapPath);
$diagnostics['checks']['bootstrap_path'] = $bootstrapPath;

// Check environment variables
$diagnostics['checks']['env_vars'] = [
    'APP_ENV' => $_ENV['APP_ENV'] ?? 'not set',
    'APP_KEY' => $_ENV['APP_KEY'] ?? 'not set',
    'APP_DEBUG' => $_ENV['APP_DEBUG'] ?? 'not set',
];

// Try to load composer autoload
if (file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        $diagnostics['checks']['autoload_success'] = true;
        
        // Try to load bootstrap
        if (file_exists($bootstrapPath)) {
            try {
                $app = require_once $bootstrapPath;
                $diagnostics['checks']['bootstrap_success'] = true;
                $diagnostics['checks']['app_type'] = get_class($app);
                
                // Try to get kernel
                try {
                    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
                    $diagnostics['checks']['kernel_success'] = true;
                    $diagnostics['checks']['kernel_type'] = get_class($kernel);
                } catch (Exception $e) {
                    $diagnostics['checks']['kernel_error'] = $e->getMessage();
                }
                
            } catch (Exception $e) {
                $diagnostics['checks']['bootstrap_error'] = $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        $diagnostics['checks']['autoload_error'] = $e->getMessage();
    }
}

// List files in root directory
$diagnostics['checks']['root_files'] = array_slice(scandir($laravelRoot), 0, 10);

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>