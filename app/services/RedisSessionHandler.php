<?php declare(strict_types=1);

namespace App\Services;

use SessionHandlerInterface;
use App\Core\Logger;
use Exception;

/**
 * Redis Session Handler
 * 
 * Implements PHP's SessionHandlerInterface to store sessions in Redis
 * with enhanced security, performance monitoring, and automatic cleanup.
 */
class RedisSessionHandler implements SessionHandlerInterface
{
    private ?object $redis = null;
    private array $config = [];
    private int $maxLifetime = 7200; // 2 hours default
    private string $keyPrefix = 'sess:';
    private bool $connected = false;
    private array $stats = [
        'reads' => 0,
        'writes' => 0,
        'destroys' => 0,
        'gc_runs' => 0,
        'errors' => 0
    ];
    
    /**
     * Initialize Redis session handler
     */
    public function __construct(array $config = [])
    {
        // Load Redis configuration
        $redisConfigFile = dirname(__DIR__, 2) . '/config/redis.php';
        $redisConfig = file_exists($redisConfigFile) ? require $redisConfigFile : [];
        
        $this->config = array_merge([
            'host' => $redisConfig['session']['host'] ?? '127.0.0.1',
            'port' => $redisConfig['session']['port'] ?? 6379,
            'password' => $redisConfig['session']['password'] ?? null,
            'database' => $redisConfig['session']['database'] ?? 1,
            'prefix' => $redisConfig['session']['prefix'] ?? 'sess:',
            'timeout' => $redisConfig['session']['timeout'] ?? 5.0,
            'persistent' => $redisConfig['session']['persistent'] ?? true,
            'serializer' => $redisConfig['session']['serializer'] ?? Redis::SERIALIZER_PHP,
            'max_lifetime' => $redisConfig['ttl']['user_sessions'] ?? 7200
        ], $config);
        
        $this->keyPrefix = $this->config['prefix'];
        $this->maxLifetime = $this->config['max_lifetime'];
        
        Logger::info('RedisSessionHandler initialized', [
            'database' => $this->config['database'],
            'max_lifetime' => $this->maxLifetime
        ]);
    }
    
    /**
     * Initialize Redis connection
     */
    public function open(string $path, string $name): bool
    {
        if ($this->connected) {
            return true;
        }
        
        try {
            // Ensure Redis extension is available
            if (!class_exists('Redis')) {
                throw new Exception('PHP Redis extension not installed');
            }
            $this->redis = new \Redis();
            
            // Use persistent connection for better performance
            if ($this->config['persistent']) {
                $connected = $this->redis->pconnect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout'],
                    'session_handler'
                );
            } else {
                $connected = $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            }
            
            if (!$connected) {
                throw new Exception('Failed to connect to Redis server');
            }
            
            // Authenticate if password is configured
            if (!empty($this->config['password'])) {
                if (!$this->redis->auth($this->config['password'])) {
                    throw new Exception('Redis authentication failed');
                }
            }
            
            // Select session database
            if (!$this->redis->select($this->config['database'])) {
                throw new Exception('Failed to select Redis database');
            }
            
            // Set serialization mode for PHP sessions (accepts string or int)
            $serializer = $this->config['serializer'] ?? 'php';
            if (is_string($serializer)) {
                $map = [
                    'php' => \Redis::SERIALIZER_PHP,
                    'json' => defined('Redis::SERIALIZER_JSON') ? \Redis::SERIALIZER_JSON : \Redis::SERIALIZER_PHP,
                    'igbinary' => defined('Redis::SERIALIZER_IGBINARY') ? \Redis::SERIALIZER_IGBINARY : \Redis::SERIALIZER_PHP,
                    'msgpack' => defined('Redis::SERIALIZER_MSGPACK') ? \Redis::SERIALIZER_MSGPACK : \Redis::SERIALIZER_PHP,
                ];
                $serializer = $map[strtolower($serializer)] ?? \Redis::SERIALIZER_PHP;
            }
            $this->redis->setOption(\Redis::OPT_SERIALIZER, (int)$serializer);
            
            // Set key prefix if configured
            if (!empty($this->keyPrefix)) {
                $this->redis->setOption(\Redis::OPT_PREFIX, $this->keyPrefix);
            }
            
            $this->connected = true;
            
            Logger::info('Redis session connection established', [
                'host' => $this->config['host'],
                'database' => $this->config['database']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            Logger::error('Redis session connection failed', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'],
                'database' => $this->config['database']
            ]);
            return false;
        }
    }
    
    /**
     * Close Redis connection
     */
    public function close(): bool
    {
        if ($this->redis && !$this->config['persistent']) {
            try {
                $this->redis->close();
                Logger::info('Redis session connection closed');
            } catch (Exception $e) {
                Logger::error('Error closing Redis session connection', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->connected = false;
        return true;
    }
    
    /**
     * Read session data from Redis
     */
    public function read(string $id): string|false
    {
        if (!$this->ensureConnection()) {
            return false;
        }
        
        try {
            $key = $this->getSessionKey($id);
            $data = $this->redis->get($key);
            
            $this->stats['reads']++;
            
            if ($data === false) {
                Logger::debug('Session not found in Redis', ['session_id' => $id]);
                return '';
            }
            
            // Update session expiration on read
            $this->redis->expire($key, $this->maxLifetime);
            
            Logger::debug('Session read from Redis', [
                'session_id' => $id,
                'data_length' => strlen($data)
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            Logger::error('Failed to read session from Redis', [
                'session_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Write session data to Redis
     */
    public function write(string $id, string $data): bool
    {
        if (!$this->ensureConnection()) {
            return false;
        }
        
        try {
            $key = $this->getSessionKey($id);
            $result = $this->redis->setex($key, $this->maxLifetime, $data);
            
            $this->stats['writes']++;
            
            if ($result) {
                Logger::debug('Session written to Redis', [
                    'session_id' => $id,
                    'data_length' => strlen($data),
                    'expires_in' => $this->maxLifetime
                ]);
            } else {
                $this->stats['errors']++;
                Logger::error('Failed to write session to Redis', [
                    'session_id' => $id
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            Logger::error('Exception writing session to Redis', [
                'session_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Destroy session in Redis
     */
    public function destroy(string $id): bool
    {
        if (!$this->ensureConnection()) {
            return false;
        }
        
        try {
            $key = $this->getSessionKey($id);
            $result = $this->redis->del($key);
            
            $this->stats['destroys']++;
            
            Logger::info('Session destroyed in Redis', [
                'session_id' => $id,
                'deleted' => $result > 0
            ]);
            
            return $result > 0;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            Logger::error('Failed to destroy session in Redis', [
                'session_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Garbage collection - clean up expired sessions
     */
    public function gc(int $max_lifetime): int|false
    {
        if (!$this->ensureConnection()) {
            return false;
        }
        
        try {
            $this->stats['gc_runs']++;
            
            // Redis automatically expires keys, so we just need to count
            // and optionally clean up any keys that might have been missed
            $pattern = $this->keyPrefix . '*';
            $keys = $this->redis->keys($pattern);
            
            $expired = 0;
            $current_time = time();
            
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                if ($ttl === -1) {
                    // Key exists but has no expiration - set one
                    $this->redis->expire($key, $this->maxLifetime);
                } elseif ($ttl === -2) {
                    // Key does not exist (already expired)
                    $expired++;
                }
            }
            
            Logger::info('Session garbage collection completed', [
                'total_sessions' => count($keys),
                'expired_sessions' => $expired,
                'max_lifetime' => $max_lifetime
            ]);
            
            return $expired;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            Logger::error('Session garbage collection failed', [
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
        $stats = $this->stats;
        $stats['connected'] = $this->connected;
        $stats['config'] = [
            'host' => $this->config['host'],
            'database' => $this->config['database'],
            'max_lifetime' => $this->maxLifetime,
            'prefix' => $this->keyPrefix
        ];
        
        if ($this->connected && $this->redis) {
            try {
                $info = $this->redis->info();
                $stats['redis_info'] = [
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'used_memory' => $info['used_memory'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0
                ];
            } catch (Exception $e) {
                $stats['redis_info_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Get all active sessions (for admin purposes)
     */
    public function getActiveSessions(): array
    {
        if (!$this->ensureConnection()) {
            return [];
        }
        
        try {
            $pattern = $this->keyPrefix . '*';
            $keys = $this->redis->keys($pattern);
            $sessions = [];
            
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                $sessionId = str_replace($this->keyPrefix, '', $key);
                
                $sessions[] = [
                    'session_id' => $sessionId,
                    'expires_in' => $ttl,
                    'created_at' => date('Y-m-d H:i:s', time() - ($this->maxLifetime - $ttl))
                ];
            }
            
            return $sessions;
            
        } catch (Exception $e) {
            Logger::error('Failed to get active sessions', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Health check for Redis session storage
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'unknown',
            'connection_test' => false,
            'read_write_test' => false,
            'performance_test' => 0,
            'errors' => []
        ];
        
        try {
            // Test connection
            if (!$this->ensureConnection()) {
                $health['status'] = 'connection_failed';
                $health['errors'][] = 'Cannot establish Redis connection';
                return $health;
            }
            
            $health['connection_test'] = true;
            
            // Test read/write operations
            $testSessionId = 'health_check_' . uniqid();
            $testData = 'health_check_data_' . time();
            
            $startTime = microtime(true);
            
            $writeResult = $this->write($testSessionId, $testData);
            $readResult = $this->read($testSessionId);
            $destroyResult = $this->destroy($testSessionId);
            
            $health['performance_test'] = (microtime(true) - $startTime) * 1000; // ms
            
            if ($writeResult && $readResult === $testData && $destroyResult) {
                $health['read_write_test'] = true;
                $health['status'] = 'healthy';
            } else {
                $health['status'] = 'degraded';
                $health['errors'][] = 'Read/write test failed';
            }
            
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['errors'][] = $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Migrate sessions from file storage to Redis
     */
    public function migrateSessions(string $sessionPath): array
    {
        $results = [
            'migrated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        if (!$this->ensureConnection()) {
            $results['errors'][] = 'Redis connection failed';
            return $results;
        }
        
        try {
            $sessionFiles = glob($sessionPath . '/sess_*');
            
            foreach ($sessionFiles as $file) {
                $sessionId = substr(basename($file), 5); // Remove 'sess_' prefix
                
                if (empty($sessionId)) {
                    $results['skipped']++;
                    continue;
                }
                
                $data = file_get_contents($file);
                if ($data === false) {
                    $results['failed']++;
                    $results['errors'][] = "Could not read session file: $file";
                    continue;
                }
                
                if ($this->write($sessionId, $data)) {
                    $results['migrated']++;
                    
                    // Optionally remove the file after successful migration
                    // unlink($file);
                    
                    Logger::info('Session migrated to Redis', [
                        'session_id' => $sessionId,
                        'data_length' => strlen($data)
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to write session to Redis: $sessionId";
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        Logger::info('Session migration completed', $results);
        
        return $results;
    }
    
    /**
     * Ensure Redis connection is active
     */
    private function ensureConnection(): bool
    {
        if (!$this->connected) {
            return $this->open('', '');
        }
        
        try {
            // Test connection with ping
            $this->redis->ping();
            return true;
        } catch (Exception $e) {
            Logger::warning('Redis session connection lost, reconnecting', [
                'error' => $e->getMessage()
            ]);
            
            $this->connected = false;
            return $this->open('', '');
        }
    }
    
    /**
     * Generate Redis key for session
     */
    private function getSessionKey(string $sessionId): string
    {
        // Key prefix is handled by Redis OPT_PREFIX, so just return the session ID
        return $sessionId;
    }
    
    /**
     * Cleanup resources
     */
    public function __destruct()
    {
        $this->close();
    }
}
