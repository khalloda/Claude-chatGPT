<?php declare(strict_types=1);

namespace App\Services;

use Redis;
use Exception;
use App\Core\Logger;

/**
 * Redis Caching Service
 * 
 * Provides distributed caching capabilities using Redis for high-performance
 * data storage and retrieval across multiple application instances.
 */
final class RedisCache
{
    private static ?Redis $redis = null;
    private static bool $connected = false;
    private static array $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'prefix' => 'spare_parts:',
        'timeout' => 5.0,
        'retry_interval' => 100,
        'read_timeout' => 2.0,
        'persistent' => true,
        'serializer' => Redis::SERIALIZER_JSON
    ];
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'errors' => 0
    ];
    
    /**
     * Initialize Redis connection
     */
    public static function init(array $config = []): bool
    {
        self::$config = array_merge(self::$config, $config);
        
        if (self::$connected && self::$redis !== null) {
            return true;
        }
        
        try {
            self::$redis = new Redis();
            
            // Use persistent connection for better performance
            if (self::$config['persistent']) {
                $connected = self::$redis->pconnect(
                    self::$config['host'],
                    self::$config['port'],
                    self::$config['timeout'],
                    'spare_parts_cache', // persistent connection ID
                    self::$config['retry_interval'],
                    self::$config['read_timeout']
                );
            } else {
                $connected = self::$redis->connect(
                    self::$config['host'],
                    self::$config['port'],
                    self::$config['timeout'],
                    null,
                    self::$config['retry_interval'],
                    self::$config['read_timeout']
                );
            }
            
            if (!$connected) {
                Logger::error('Redis connection failed', [
                    'host' => self::$config['host'],
                    'port' => self::$config['port']
                ]);
                return false;
            }
            
            // Authenticate if password is configured
            if (!empty(self::$config['password'])) {
                if (!self::$redis->auth(self::$config['password'])) {
                    Logger::error('Redis authentication failed');
                    return false;
                }
            }
            
            // Select database
            if (!self::$redis->select(self::$config['database'])) {
                Logger::error('Redis database selection failed', [
                    'database' => self::$config['database']
                ]);
                return false;
            }
            
            // Set serialization mode
            if (!self::$redis->setOption(Redis::OPT_SERIALIZER, self::$config['serializer'])) {
                Logger::warning('Redis serializer configuration failed');
            }
            
            // Set key prefix
            if (!empty(self::$config['prefix'])) {
                if (!self::$redis->setOption(Redis::OPT_PREFIX, self::$config['prefix'])) {
                    Logger::warning('Redis prefix configuration failed');
                }
            }
            
            self::$connected = true;
            
            Logger::info('Redis connection established', [
                'host' => self::$config['host'],
                'port' => self::$config['port'],
                'database' => self::$config['database']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Redis connection exception', [
                'error' => $e->getMessage(),
                'host' => self::$config['host'],
                'port' => self::$config['port']
            ]);
            return false;
        }
    }
    
    /**
     * Get value from cache
     */
    public static function get(string $key): mixed
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            $value = self::$redis->get($key);
            
            if ($value === false) {
                self::$stats['misses']++;
                return false;
            }
            
            self::$stats['hits']++;
            return $value;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis get operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Set value in cache with TTL
     */
    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            $result = self::$redis->setex($key, $ttl, $value);
            
            if ($result) {
                self::$stats['sets']++;
            } else {
                self::$stats['errors']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis set operation failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Delete key from cache
     */
    public static function delete(string $key): bool
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            $result = self::$redis->del($key) > 0;
            
            if ($result) {
                self::$stats['deletes']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis delete operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Delete multiple keys matching pattern
     */
    public static function deletePattern(string $pattern): int
    {
        if (!self::ensureConnection()) {
            return 0;
        }
        
        try {
            $keys = self::$redis->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            
            $deleted = self::$redis->del($keys);
            self::$stats['deletes'] += $deleted;
            
            Logger::info('Redis pattern delete completed', [
                'pattern' => $pattern,
                'deleted' => $deleted
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis pattern delete failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Check if key exists
     */
    public static function exists(string $key): bool
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            return self::$redis->exists($key) > 0;
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis exists operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Set key expiration time
     */
    public static function expire(string $key, int $ttl): bool
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            return self::$redis->expire($key, $ttl);
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis expire operation failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get key TTL
     */
    public static function ttl(string $key): int
    {
        if (!self::ensureConnection()) {
            return -2;
        }
        
        try {
            return self::$redis->ttl($key);
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis TTL operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return -2;
        }
    }
    
    /**
     * Increment counter
     */
    public static function increment(string $key, int $amount = 1): int|false
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            return self::$redis->incrBy($key, $amount);
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis increment operation failed', [
                'key' => $key,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get multiple values at once
     */
    public static function getMultiple(array $keys): array
    {
        if (!self::ensureConnection() || empty($keys)) {
            return [];
        }
        
        try {
            $values = self::$redis->mget($keys);
            $result = [];
            
            foreach ($keys as $index => $key) {
                if ($values[$index] !== false) {
                    $result[$key] = $values[$index];
                    self::$stats['hits']++;
                } else {
                    self::$stats['misses']++;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis mget operation failed', [
                'keys_count' => count($keys),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Set multiple values at once
     */
    public static function setMultiple(array $data, int $ttl = 3600): bool
    {
        if (!self::ensureConnection() || empty($data)) {
            return false;
        }
        
        try {
            // Use pipeline for better performance
            self::$redis->multi(Redis::PIPELINE);
            
            foreach ($data as $key => $value) {
                self::$redis->setex($key, $ttl, $value);
            }
            
            $results = self::$redis->exec();
            $success = !in_array(false, $results, true);
            
            if ($success) {
                self::$stats['sets'] += count($data);
            } else {
                self::$stats['errors']++;
            }
            
            return $success;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis mset operation failed', [
                'keys_count' => count($data),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Flush all keys in current database
     */
    public static function flush(): bool
    {
        if (!self::ensureConnection()) {
            return false;
        }
        
        try {
            $result = self::$redis->flushDB();
            
            if ($result) {
                Logger::info('Redis database flushed', [
                    'database' => self::$config['database']
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            self::$stats['errors']++;
            Logger::error('Redis flush operation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $info = [];
        $serverInfo = [];
        
        if (self::ensureConnection()) {
            try {
                $serverInfo = self::$redis->info();
            } catch (Exception $e) {
                Logger::error('Redis info operation failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'connection' => [
                'connected' => self::$connected,
                'host' => self::$config['host'],
                'port' => self::$config['port'],
                'database' => self::$config['database']
            ],
            'operations' => self::$stats,
            'hit_rate' => self::$stats['hits'] > 0 ? 
                round(self::$stats['hits'] / (self::$stats['hits'] + self::$stats['misses']) * 100, 2) : 0,
            'server_info' => $serverInfo,
            'config' => [
                'prefix' => self::$config['prefix'],
                'persistent' => self::$config['persistent'],
                'serializer' => self::$config['serializer']
            ]
        ];
    }
    
    /**
     * Test Redis connection and performance
     */
    public static function healthCheck(): array
    {
        $startTime = microtime(true);
        $health = [
            'status' => 'unknown',
            'connection_time' => 0,
            'ping_time' => 0,
            'read_write_test' => false,
            'memory_usage' => 0,
            'errors' => []
        ];
        
        try {
            // Test connection
            if (!self::ensureConnection()) {
                $health['status'] = 'connection_failed';
                $health['errors'][] = 'Could not establish connection';
                return $health;
            }
            
            $health['connection_time'] = (microtime(true) - $startTime) * 1000;
            
            // Test ping
            $pingStart = microtime(true);
            $pong = self::$redis->ping();
            $health['ping_time'] = (microtime(true) - $pingStart) * 1000;
            
            if ($pong !== '+PONG') {
                $health['errors'][] = 'Ping test failed';
            }
            
            // Test read/write
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_' . time();
            
            if (self::set($testKey, $testValue, 60)) {
                $retrievedValue = self::get($testKey);
                if ($retrievedValue === $testValue) {
                    $health['read_write_test'] = true;
                    self::delete($testKey);
                } else {
                    $health['errors'][] = 'Read/write test failed - value mismatch';
                }
            } else {
                $health['errors'][] = 'Read/write test failed - could not write';
            }
            
            // Get memory usage
            try {
                $info = self::$redis->info('memory');
                if (isset($info['used_memory'])) {
                    $health['memory_usage'] = (int)$info['used_memory'];
                }
            } catch (Exception $e) {
                $health['errors'][] = 'Could not get memory info: ' . $e->getMessage();
            }
            
            $health['status'] = empty($health['errors']) ? 'healthy' : 'degraded';
            
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['errors'][] = $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Ensure Redis connection is active
     */
    private static function ensureConnection(): bool
    {
        if (!self::$connected || self::$redis === null) {
            return self::init();
        }
        
        try {
            // Test connection with ping
            self::$redis->ping();
            return true;
        } catch (Exception $e) {
            Logger::warning('Redis connection lost, reconnecting', [
                'error' => $e->getMessage()
            ]);
            
            self::$connected = false;
            return self::init();
        }
    }
    
    /**
     * Close Redis connection
     */
    public static function close(): void
    {
        if (self::$redis !== null) {
            try {
                if (!self::$config['persistent']) {
                    self::$redis->close();
                }
                self::$connected = false;
                Logger::info('Redis connection closed');
            } catch (Exception $e) {
                Logger::error('Error closing Redis connection', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Get configuration
     */
    public static function getConfig(): array
    {
        return self::$config;
    }
    
    /**
     * Update configuration
     */
    public static function updateConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
        
        // Force reconnection with new config
        if (self::$connected) {
            self::close();
            self::init();
        }
    }
}