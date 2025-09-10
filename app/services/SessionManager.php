<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Env;
use Exception;

/**
 * Session Manager
 * 
 * Provides centralized session management with Redis backend support,
 * including configuration, initialization, and lifecycle management.
 */
class SessionManager
{
    private static ?SessionManager $instance = null;
    private ?RedisSessionHandler $sessionHandler = null;
    private array $config = [];
    private bool $initialized = false;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize session manager
     */
    public function __construct()
    {
        $this->loadConfig();
    }
    
    /**
     * Load session configuration
     */
    private function loadConfig(): void
    {
        $this->config = [
            'driver' => Env::get('SESSION_DRIVER', 'redis'),
            'lifetime' => (int)Env::get('SESSION_LIFETIME', 7200), // 2 hours
            'path' => Env::get('SESSION_PATH', '/'),
            'domain' => Env::get('SESSION_DOMAIN', null),
            'secure' => Env::get('SESSION_SECURE', 'auto') === 'true' || 
                       (Env::get('SESSION_SECURE', 'auto') === 'auto' && $this->isHttps()),
            'httponly' => Env::get('SESSION_HTTP_ONLY', 'true') === 'true',
            'samesite' => Env::get('SESSION_SAME_SITE', 'Lax'),
            'cookie_name' => Env::get('SESSION_COOKIE', 'spare_parts_session'),
            'gc_probability' => (int)Env::get('SESSION_GC_PROBABILITY', 1),
            'gc_divisor' => (int)Env::get('SESSION_GC_DIVISOR', 100),
            'encrypt' => Env::get('SESSION_ENCRYPT', 'false') === 'true',
        ];
        
        Logger::info('Session configuration loaded', [
            'driver' => $this->config['driver'],
            'lifetime' => $this->config['lifetime'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly']
        ]);
    }
    
    /**
     * Initialize session system
     */
    public function initialize(): bool
    {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // Configure session parameters before starting
            $this->configureSessionParameters();
            
            // Set up session handler if using Redis
            if ($this->config['driver'] === 'redis') {
                $this->setupRedisHandler();
            }
            
            // Start session if not already active
            if (session_status() !== PHP_SESSION_ACTIVE) {
                if (!session_start()) {
                    throw new Exception('Failed to start session');
                }
            }
            
            // Configure session security
            $this->configureSessionSecurity();
            
            $this->initialized = true;
            
            Logger::info('Session system initialized successfully', [
                'session_id' => session_id(),
                'handler' => $this->config['driver']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Failed to initialize session system', [
                'error' => $e->getMessage(),
                'driver' => $this->config['driver']
            ]);
            return false;
        }
    }
    
    /**
     * Configure session parameters
     */
    private function configureSessionParameters(): void
    {
        // Set session name
        session_name($this->config['cookie_name']);
        
        // Configure cookie parameters
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite'],
        ]);
        
        // Configure garbage collection
        ini_set('session.gc_probability', (string)$this->config['gc_probability']);
        ini_set('session.gc_divisor', (string)$this->config['gc_divisor']);
        ini_set('session.gc_maxlifetime', (string)$this->config['lifetime']);
        
        // Additional security settings
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_trans_sid', '0');
        
        Logger::debug('Session parameters configured');
    }
    
    /**
     * Setup Redis session handler
     */
    private function setupRedisHandler(): void
    {
        // Load Redis configuration for sessions
        $redisConfigFile = dirname(__DIR__, 2) . '/config/redis.php';
        $redisConfig = file_exists($redisConfigFile) ? require $redisConfigFile : [];
        
        $sessionConfig = [
            'host' => $redisConfig['session']['host'] ?? Env::get('REDIS_SESSION_HOST', '127.0.0.1'),
            'port' => $redisConfig['session']['port'] ?? (int)Env::get('REDIS_SESSION_PORT', 6379),
            'password' => $redisConfig['session']['password'] ?? Env::get('REDIS_SESSION_PASSWORD', null),
            'database' => $redisConfig['session']['database'] ?? (int)Env::get('REDIS_SESSION_DATABASE', 1),
            'prefix' => $redisConfig['session']['prefix'] ?? Env::get('REDIS_SESSION_PREFIX', 'sess:'),
            'timeout' => $redisConfig['session']['timeout'] ?? (float)Env::get('REDIS_SESSION_TIMEOUT', 5.0),
            'persistent' => $redisConfig['session']['persistent'] ?? (Env::get('REDIS_SESSION_PERSISTENT', 'true') === 'true'),
            'max_lifetime' => $this->config['lifetime']
        ];
        
        $this->sessionHandler = new RedisSessionHandler($sessionConfig);
        
        // Set the session handler
        if (!session_set_save_handler($this->sessionHandler, true)) {
            throw new Exception('Failed to set Redis session handler');
        }
        
        Logger::info('Redis session handler configured', [
            'database' => $sessionConfig['database'],
            'prefix' => $sessionConfig['prefix']
        ]);
    }
    
    /**
     * Configure session security features
     */
    private function configureSessionSecurity(): void
    {
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = time();
            session_regenerate_id(true);
        } elseif (time() - $_SESSION['_last_regeneration'] > 1800) { // 30 minutes
            $_SESSION['_last_regeneration'] = time();
            session_regenerate_id(true);
            Logger::info('Session ID regenerated for security');
        }
        
        // Set session metadata
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        }
        
        $_SESSION['_last_activity'] = time();
        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_ip_address'] = $this->getClientIpAddress();
        
        // Validate session integrity
        $this->validateSessionIntegrity();
    }
    
    /**
     * Validate session integrity
     */
    private function validateSessionIntegrity(): void
    {
        // Check for session hijacking
        $expectedUserAgent = $_SESSION['_user_agent'] ?? '';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($expectedUserAgent !== '' && $expectedUserAgent !== $currentUserAgent) {
            Logger::warning('Session user agent mismatch detected', [
                'session_id' => session_id(),
                'expected' => $expectedUserAgent,
                'current' => $currentUserAgent
            ]);
            $this->destroySession();
            return;
        }
        
        // Check session expiration
        if (isset($_SESSION['_last_activity'])) {
            $inactivityTime = time() - $_SESSION['_last_activity'];
            if ($inactivityTime > $this->config['lifetime']) {
                Logger::info('Session expired due to inactivity', [
                    'session_id' => session_id(),
                    'inactive_for' => $inactivityTime
                ]);
                $this->destroySession();
                return;
            }
        }
    }
    
    /**
     * Destroy current session
     */
    public function destroySession(): bool
    {
        try {
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            // Destroy session
            $sessionId = session_id();
            $result = session_destroy();
            
            Logger::info('Session destroyed', [
                'session_id' => $sessionId,
                'success' => $result
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('Failed to destroy session', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        try {
            $oldSessionId = session_id();
            $result = session_regenerate_id($deleteOldSession);
            
            if ($result) {
                $_SESSION['_last_regeneration'] = time();
                
                Logger::info('Session ID regenerated', [
                    'old_session_id' => $oldSessionId,
                    'new_session_id' => session_id()
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('Failed to regenerate session ID', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get session statistics
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => $this->config['driver'],
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_status' => session_status(),
            'initialized' => $this->initialized,
            'config' => $this->config
        ];
        
        if ($this->sessionHandler) {
            $stats['redis_stats'] = $this->sessionHandler->getStats();
        }
        
        return $stats;
    }
    
    /**
     * Get active sessions (admin function)
     */
    public function getActiveSessions(): array
    {
        if ($this->sessionHandler) {
            return $this->sessionHandler->getActiveSessions();
        }
        
        return [];
    }
    
    /**
     * Perform session health check
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'unknown',
            'session_working' => false,
            'handler_healthy' => false,
            'errors' => []
        ];
        
        try {
            // Test basic session functionality
            $testKey = '_health_check_' . uniqid();
            $testValue = 'test_' . time();
            
            $_SESSION[$testKey] = $testValue;
            
            if (isset($_SESSION[$testKey]) && $_SESSION[$testKey] === $testValue) {
                $health['session_working'] = true;
                unset($_SESSION[$testKey]);
            } else {
                $health['errors'][] = 'Session read/write test failed';
            }
            
            // Test session handler if Redis
            if ($this->sessionHandler) {
                $handlerHealth = $this->sessionHandler->healthCheck();
                $health['handler_healthy'] = $handlerHealth['status'] === 'healthy';
                $health['handler_details'] = $handlerHealth;
                
                if (!$health['handler_healthy']) {
                    $health['errors'] = array_merge($health['errors'], $handlerHealth['errors'] ?? []);
                }
            } else {
                $health['handler_healthy'] = true; // File sessions
            }
            
            // Determine overall status
            if ($health['session_working'] && $health['handler_healthy']) {
                $health['status'] = 'healthy';
            } else {
                $health['status'] = 'degraded';
            }
            
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['errors'][] = $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Migrate sessions from file to Redis
     */
    public function migrateSessions(string $sessionPath = null): array
    {
        if (!$this->sessionHandler) {
            return [
                'error' => 'Redis session handler not available'
            ];
        }
        
        $sessionPath = $sessionPath ?? session_save_path();
        return $this->sessionHandler->migrateSessions($sessionPath);
    }
    
    /**
     * Check if HTTPS is being used
     */
    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
               (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIpAddress(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Cleanup resources
     */
    public function __destruct()
    {
        if ($this->sessionHandler) {
            // Session handler cleanup is handled by its own destructor
        }
    }
}