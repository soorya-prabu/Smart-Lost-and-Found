<?php
/**
 * Smart Lost & Found - Central Application Bootstrap
 */

// 1. Load Environment Configuration
require_once __DIR__ . '/env.php';

// Configure Environment-Sensitive Error Reporting
$appEnv = env('APP_ENV', 'development');
$appDebug = env('APP_DEBUG', true);
if (is_string($appDebug)) {
    $appDebug = filter_var($appDebug, FILTER_VALIDATE_BOOLEAN);
}

if ($appEnv === 'production' || !$appDebug) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    
    // Graceful exception handler in production
    set_exception_handler(function($e) {
        error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        http_response_code(500);
        if (php_sapi_name() !== 'cli') {
            echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><title>Service Unavailable</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-light d-flex align-items-center justify-content-center' style='min-height:100vh;'><div class='card p-4 shadow-sm text-center' style='max-width:520px;'><h4>⚠️ System Notice</h4><p class='text-muted'>An unexpected error occurred while processing your request. Please try again shortly or contact campus support.</p><a href='/lost_and_found/' class='btn btn-primary'>Return to Home</a></div></body></html>";
        }
        exit(1);
    });
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// 2. Start Secure Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Load Database Connection & Prepared Statement Engine
require_once __DIR__ . '/database.php';

// 4. Load Core Utilities
require_once dirname(__DIR__) . '/src/utils/helpers.php';
require_once dirname(__DIR__) . '/src/utils/sanitize.php';
require_once dirname(__DIR__) . '/src/utils/auth.php';
require_once dirname(__DIR__) . '/src/utils/uploader.php';
require_once dirname(__DIR__) . '/src/utils/logger.php';
require_once dirname(__DIR__) . '/src/utils/matching.php';
