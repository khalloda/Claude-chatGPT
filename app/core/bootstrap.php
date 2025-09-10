<?php declare(strict_types=1);

// PSR-ish autoloader (lowercase files)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', '/', $relative);
        $path = __DIR__ . '/../' . strtolower($relative) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

require __DIR__ . '/env.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/flash.php';

use App\Core\Env;
use App\Core\Logger;
use App\Core\ErrorHandler;
use App\Services\SessionManager;

// Initialize logging system
Logger::init();

// Initialize error handling system
ErrorHandler::init();

// timezone & error display from .env
date_default_timezone_set(Env::get('APP_TIMEZONE', 'UTC'));
$debug = Env::get('APP_DEBUG', 'false') === 'true';

// Initialize session management with Redis support
$sessionManager = SessionManager::getInstance();
if (!$sessionManager->initialize()) {
    Logger::error('Failed to initialize session management system');
    // Fall back to default PHP sessions as emergency measure
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
