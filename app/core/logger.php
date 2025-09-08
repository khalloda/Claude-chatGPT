<?php declare(strict_types=1);

namespace App\Core;

/**
 * Comprehensive Logging System
 * 
 * Provides structured logging with multiple severity levels, contextual information,
 * log rotation, and security-focused error management.
 * 
 * Features:
 * - PSR-3 compatible logging levels
 * - Contextual logging with user, IP, and request data
 * - Log rotation and cleanup
 * - Security-focused logging
 * - Performance monitoring
 * - Production-safe error handling
 */
final class Logger
{
    // PSR-3 Log Levels
    public const EMERGENCY = 'emergency'; // System is unusable
    public const ALERT = 'alert';         // Action must be taken immediately
    public const CRITICAL = 'critical';   // Critical conditions
    public const ERROR = 'error';         // Error conditions
    public const WARNING = 'warning';     // Warning conditions
    public const NOTICE = 'notice';       // Normal but significant condition
    public const INFO = 'info';           // Informational messages
    public const DEBUG = 'debug';         // Debug-level messages

    private static array $logLevels = [
        self::EMERGENCY => 800,
        self::ALERT => 700,
        self::CRITICAL => 600,
        self::ERROR => 500,
        self::WARNING => 400,
        self::NOTICE => 300,
        self::INFO => 200,
        self::DEBUG => 100,
    ];

    private static ?string $logDirectory = null;
    private static ?string $currentLogLevel = null;

    /**
     * Initialize logger configuration
     */
    public static function init(): void
    {
        self::$logDirectory = __DIR__ . '/../../storage/logs';
        self::$currentLogLevel = getenv('LOG_LEVEL') ?: self::INFO;
        
        // Ensure log directory exists
        if (!is_dir(self::$logDirectory)) {
            @mkdir(self::$logDirectory, 0775, true);
        }

        // Set up log rotation
        self::rotateLogsIfNeeded();
    }

    /**
     * Core logging method with structured format
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        // Initialize if not already done
        if (self::$logDirectory === null) {
            self::init();
        }

        // Check if we should log this level
        if (!self::shouldLog($level)) {
            return;
        }

        // Build comprehensive log entry
        $logEntry = [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'system' => self::getSystemContext(),
            'request' => self::getRequestContext(),
            'user' => self::getUserContext(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        // Format log line
        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        // Determine log file based on level and date
        $logFile = self::getLogFile($level);
        
        // Write to log file
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Handle critical errors
        if (self::$logLevels[$level] >= self::$logLevels[self::CRITICAL]) {
            self::handleCriticalError($level, $message, $context);
        }
    }

    /**
     * PSR-3 Compatible logging methods
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::log(self::ALERT, $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log(self::NOTICE, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Security-focused logging methods
     */
    public static function security(string $message, array $context = []): void
    {
        $securityContext = array_merge($context, [
            'category' => 'security',
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
        ]);
        
        self::critical("SECURITY: {$message}", $securityContext);
    }

    public static function authentication(string $event, array $context = []): void
    {
        $authContext = array_merge($context, [
            'category' => 'authentication',
            'session_id' => session_id(),
        ]);
        
        self::info("AUTH: {$event}", $authContext);
    }

    public static function performance(string $operation, float $duration, array $context = []): void
    {
        $perfContext = array_merge($context, [
            'category' => 'performance',
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
        ]);
        
        $level = $duration > 1.0 ? self::WARNING : self::INFO;
        self::log($level, "PERF: {$operation}", $perfContext);
    }

    /**
     * Database operation logging
     */
    public static function database(string $query, float $duration, array $context = []): void
    {
        $dbContext = array_merge($context, [
            'category' => 'database',
            'query' => self::sanitizeQuery($query),
            'duration_ms' => round($duration * 1000, 2),
        ]);
        
        $level = $duration > 0.5 ? self::WARNING : self::DEBUG;
        self::log($level, "DB: Query executed", $dbContext);
    }

    /**
     * HTTP request logging
     */
    public static function request(string $method, string $uri, int $responseCode, float $duration): void
    {
        $requestContext = [
            'category' => 'http',
            'method' => $method,
            'uri' => $uri,
            'response_code' => $responseCode,
            'duration_ms' => round($duration * 1000, 2),
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        $level = $responseCode >= 500 ? self::ERROR : 
                ($responseCode >= 400 ? self::WARNING : self::INFO);
        
        self::log($level, "HTTP: {$method} {$uri} - {$responseCode}", $requestContext);
    }

    /**
     * Helper Methods
     */
    private static function shouldLog(string $level): bool
    {
        $currentLevelPriority = self::$logLevels[self::$currentLogLevel] ?? self::$logLevels[self::INFO];
        $messageLevelPriority = self::$logLevels[$level] ?? 0;
        
        return $messageLevelPriority >= $currentLevelPriority;
    }

    private static function getLogFile(string $level): string
    {
        $date = date('Y-m-d');
        
        // Use separate files for different log types
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            return self::$logDirectory . "/error-{$date}.log";
        } elseif ($level === self::DEBUG) {
            return self::$logDirectory . "/debug-{$date}.log";
        } else {
            return self::$logDirectory . "/app-{$date}.log";
        }
    }

    private static function getSystemContext(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
        ];
    }

    private static function getRequestContext(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        ];
    }

    private static function getUserContext(): array
    {
        $user = null;
        if (isset($_SESSION['user'])) {
            $user = [
                'id' => $_SESSION['user']['id'] ?? null,
                'email' => $_SESSION['user']['email'] ?? null,
                'role' => $_SESSION['user']['role'] ?? null,
            ];
        }
        
        return [
            'user' => $user,
            'session_id' => session_id(),
            'ip' => self::getClientIP(),
        ];
    }

    private static function getClientIP(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
               $_SERVER['HTTP_X_REAL_IP'] ?? 
               $_SERVER['REMOTE_ADDR'] ?? 
               'unknown';
    }

    private static function sanitizeQuery(string $query): string
    {
        // Remove potential sensitive data from queries
        $query = preg_replace('/(\bPASSWORD\s*=\s*)[\'"][^\'"]*[\'"]/', '$1***', $query);
        $query = preg_replace('/(\bSET\s+password\s*=\s*)[\'"][^\'"]*[\'"]/', '$1***', $query);
        
        return $query;
    }

    private static function rotateLogsIfNeeded(): void
    {
        $files = glob(self::$logDirectory . '/*.log');
        
        foreach ($files as $file) {
            // Rotate files older than 7 days
            if (filemtime($file) < strtotime('-7 days')) {
                $archiveFile = $file . '.old';
                @rename($file, $archiveFile);
            }
            
            // Delete files older than 30 days
            if (filemtime($file) < strtotime('-30 days')) {
                @unlink($file);
            }
        }
    }

    private static function handleCriticalError(string $level, string $message, array $context): void
    {
        // In production, could send alerts, notifications, etc.
        // For now, we'll just ensure the error is logged to a special critical log
        $criticalFile = self::$logDirectory . '/critical.log';
        
        $criticalEntry = [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ];
        
        $logLine = json_encode($criticalEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($criticalFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get log statistics for monitoring
     */
    public static function getStats(): array
    {
        $stats = [
            'total_size' => 0,
            'file_count' => 0,
            'oldest_log' => null,
            'newest_log' => null,
        ];
        
        if (!is_dir(self::$logDirectory)) {
            return $stats;
        }
        
        $files = glob(self::$logDirectory . '/*.log');
        $stats['file_count'] = count($files);
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            
            $mtime = filemtime($file);
            if ($stats['oldest_log'] === null || $mtime < $stats['oldest_log']) {
                $stats['oldest_log'] = $mtime;
            }
            if ($stats['newest_log'] === null || $mtime > $stats['newest_log']) {
                $stats['newest_log'] = $mtime;
            }
        }
        
        return $stats;
    }
}
