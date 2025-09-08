<?php declare(strict_types=1);

namespace App\Core;

/**
 * Comprehensive Error Handler
 * 
 * Provides secure error handling with production-safe error display,
 * comprehensive logging, and security-focused error management.
 * 
 * Features:
 * - Production-safe error display (no sensitive data exposure)
 * - Comprehensive error logging with context
 * - Exception handling with stack trace sanitization
 * - Security-focused error responses
 * - Custom error pages for different error types
 * - Performance monitoring for error scenarios
 */
final class ErrorHandler
{
    private static bool $isProduction = true;
    private static bool $isInitialized = false;

    /**
     * Initialize the error handler
     */
    public static function init(): void
    {
        if (self::$isInitialized) {
            return;
        }

        self::$isProduction = (getenv('APP_ENV') !== 'development');

        // Set custom error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        // Configure error reporting
        if (self::$isProduction) {
            error_reporting(0);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        self::$isInitialized = true;
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        // Convert to ErrorException for consistent handling
        $exception = new \ErrorException($message, 0, $severity, $file, $line);
        self::handleException($exception);
        
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException(\Throwable $exception): void
    {
        $errorId = self::generateErrorId();
        
        // Log the complete error details
        self::logError($exception, $errorId);

        // Determine if this is an HTTP request or CLI
        if (self::isWebRequest()) {
            self::handleWebException($exception, $errorId);
        } else {
            self::handleCliException($exception, $errorId);
        }
    }

    /**
     * Handle fatal errors during shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new \ErrorException(
                $error['message'], 
                0, 
                $error['type'], 
                $error['file'], 
                $error['line']
            );
            
            self::handleException($exception);
        }
    }

    /**
     * Handle specific application errors
     */
    public static function handleApplicationError(string $type, string $message, array $context = []): void
    {
        $errorId = self::generateErrorId();
        
        Logger::error("Application Error: {$type}", array_merge($context, [
            'error_id' => $errorId,
            'error_type' => $type,
            'message' => $message,
        ]));

        if (self::isWebRequest()) {
            self::renderErrorPage($type, $message, $errorId);
        }
    }

    /**
     * Handle validation errors
     */
    public static function handleValidationError(array $errors, array $context = []): void
    {
        $errorId = self::generateErrorId();
        
        Logger::warning("Validation Error", [
            'error_id' => $errorId,
            'validation_errors' => $errors,
            'context' => $context,
        ]);

        if (self::isWebRequest()) {
            // For validation errors, we typically redirect back with errors
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['validation_errors'] = $errors;
                $_SESSION['validation_error_id'] = $errorId;
            }
        }
    }

    /**
     * Handle security-related errors
     */
    public static function handleSecurityError(string $message, array $context = []): void
    {
        $errorId = self::generateErrorId();
        
        Logger::security($message, array_merge($context, [
            'error_id' => $errorId,
            'timestamp' => time(),
        ]));

        if (self::isWebRequest()) {
            http_response_code(403);
            self::renderErrorPage('security', 'Access denied', $errorId);
        }
    }

    /**
     * Handle database errors
     */
    public static function handleDatabaseError(\Throwable $exception, string $query = ''): void
    {
        $errorId = self::generateErrorId();
        
        Logger::critical("Database Error", [
            'error_id' => $errorId,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'query' => Logger::sanitizeQuery($query),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        if (self::isWebRequest()) {
            http_response_code(500);
            self::renderErrorPage('database', 'Database connection error', $errorId);
        }
    }

    /**
     * Log error with comprehensive context
     */
    private static function logError(\Throwable $exception, string $errorId): void
    {
        $context = [
            'error_id' => $errorId,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::sanitizeStackTrace($exception->getTrace()),
            'previous' => $exception->getPrevious() ? [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $exception->getPrevious()->getFile(),
                'line' => $exception->getPrevious()->getLine(),
            ] : null,
        ];

        // Use appropriate log level based on exception type
        if ($exception instanceof \Error) {
            Logger::critical("Fatal Error: {$exception->getMessage()}", $context);
        } elseif ($exception instanceof \ErrorException) {
            $level = self::getLogLevelForErrorType($exception->getSeverity());
            Logger::log($level, "PHP Error: {$exception->getMessage()}", $context);
        } else {
            Logger::error("Uncaught Exception: {$exception->getMessage()}", $context);
        }
    }

    /**
     * Handle web exceptions
     */
    private static function handleWebException(\Throwable $exception, string $errorId): void
    {
        // Prevent any previous output
        if (ob_get_level()) {
            ob_clean();
        }

        // Determine HTTP status code
        $statusCode = self::getHttpStatusCode($exception);
        http_response_code($statusCode);

        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');

        // Determine error type and message
        $errorType = self::getErrorType($exception);
        $errorMessage = self::getPublicErrorMessage($exception);

        // Render appropriate error page
        self::renderErrorPage($errorType, $errorMessage, $errorId);
    }

    /**
     * Handle CLI exceptions
     */
    private static function handleCliException(\Throwable $exception, string $errorId): void
    {
        $output = self::$isProduction ? 
            "Fatal error occurred. Error ID: {$errorId}" :
            sprintf(
                "Fatal error: %s in %s:%d\nError ID: %s\n",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $errorId
            );

        fwrite(STDERR, $output);
        exit(1);
    }

    /**
     * Render error page
     */
    private static function renderErrorPage(string $type, string $message, string $errorId): void
    {
        $errorPagePath = __DIR__ . '/../views/errors/' . $type . '.php';
        $genericErrorPath = __DIR__ . '/../views/errors/500.php';
        
        // Try specific error page first, fall back to generic
        if (file_exists($errorPagePath)) {
            $viewPath = $errorPagePath;
        } elseif (file_exists($genericErrorPath)) {
            $viewPath = $genericErrorPath;
        } else {
            // Fallback to inline error page
            self::renderInlineError($type, $message, $errorId);
            return;
        }

        // Safe variables for error template
        $errorType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $errorMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $showDetails = !self::$isProduction;
        
        try {
            require $viewPath;
        } catch (\Throwable $renderException) {
            // If error page fails to render, show inline error
            Logger::critical("Error page rendering failed", [
                'original_error_id' => $errorId,
                'render_error' => $renderException->getMessage(),
            ]);
            
            self::renderInlineError($type, $message, $errorId);
        }
    }

    /**
     * Render inline error when templates fail
     */
    private static function renderInlineError(string $type, string $message, string $errorId): void
    {
        $title = self::getErrorTitle($type);
        $publicMessage = self::$isProduction ? 
            'An error occurred while processing your request.' : 
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { line-height: 1.6; color: #555; }
        .error-id { font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 20px; }
        .back-link { display: inline-block; margin-top: 20px; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$title}</h1>
        <p>{$publicMessage}</p>
        <div class="error-id">Error ID: {$errorId}</div>
        <a href="/" class="back-link">← Return to homepage</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Utility methods
     */
    private static function generateErrorId(): string
    {
        return date('YmdHis') . '-' . substr(md5(uniqid()), 0, 8);
    }

    private static function isWebRequest(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) || isset($_SERVER['HTTP_HOST']);
    }

    private static function getHttpStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof \InvalidArgumentException) {
            return 400;
        }
        
        if ($exception instanceof \UnauthorizedAccessException) {
            return 401;
        }
        
        if ($exception instanceof \ForbiddenException) {
            return 403;
        }
        
        if ($exception instanceof \NotFoundException) {
            return 404;
        }
        
        return 500;
    }

    private static function getErrorType(\Throwable $exception): string
    {
        if ($exception instanceof \Error) {
            return 'fatal';
        }
        
        if ($exception instanceof \ErrorException) {
            return 'php_error';
        }
        
        return 'exception';
    }

    private static function getPublicErrorMessage(\Throwable $exception): string
    {
        if (self::$isProduction) {
            // Generic messages in production
            if ($exception instanceof \Error) {
                return 'A fatal error occurred.';
            }
            return 'An unexpected error occurred.';
        }
        
        return $exception->getMessage();
    }

    private static function getErrorTitle(string $type): string
    {
        $titles = [
            'fatal' => 'Fatal Error',
            'php_error' => 'System Error',
            'exception' => 'Application Error',
            'database' => 'Database Error',
            'security' => 'Access Denied',
            'validation' => 'Validation Error',
            'not_found' => 'Page Not Found',
        ];
        
        return $titles[$type] ?? 'Error';
    }

    private static function getLogLevelForErrorType(int $severity): string
    {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return Logger::ERROR;
            
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return Logger::WARNING;
            
            case E_NOTICE:
            case E_USER_NOTICE:
                return Logger::NOTICE;
            
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return Logger::INFO;
            
            default:
                return Logger::ERROR;
        }
    }

    private static function sanitizeStackTrace(array $trace): array
    {
        $sanitized = [];
        
        foreach ($trace as $frame) {
            $sanitizedFrame = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
            ];
            
            // Remove sensitive arguments
            if (isset($frame['args'])) {
                $sanitizedFrame['args'] = self::sanitizeArguments($frame['args']);
            }
            
            $sanitized[] = $sanitizedFrame;
        }
        
        return $sanitized;
    }

    private static function sanitizeArguments(array $args): array
    {
        $sanitized = [];
        
        foreach ($args as $arg) {
            if (is_string($arg)) {
                // Truncate long strings and hide potential passwords
                if (strlen($arg) > 100) {
                    $sanitized[] = substr($arg, 0, 100) . '...';
                } elseif (preg_match('/password|secret|token/i', $arg)) {
                    $sanitized[] = '***';
                } else {
                    $sanitized[] = $arg;
                }
            } elseif (is_array($arg)) {
                $sanitized[] = '[Array(' . count($arg) . ')]';
            } elseif (is_object($arg)) {
                $sanitized[] = '[Object(' . get_class($arg) . ')]';
            } else {
                $sanitized[] = $arg;
            }
        }
        
        return $sanitized;
    }

    /**
     * Get current error handler status
     */
    public static function getStatus(): array
    {
        return [
            'initialized' => self::$isInitialized,
            'production_mode' => self::$isProduction,
            'error_reporting' => error_reporting(),
            'display_errors' => ini_get('display_errors'),
            'log_errors' => ini_get('log_errors'),
        ];
    }
}