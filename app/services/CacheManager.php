<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Cache Manager Service
 * 
 * Unified interface for managing different caching layers (Redis, Memory, File)
 * with intelligent fallback and performance optimization strategies.
 */
final class CacheManager
{
    private static bool $initialized = false;
    private static array $config = [];
    private static array $stats = [
        'redis_hits' => 0,
        'redis_misses' => 0,
        'file_hits' => 0,
        'file_misses' => 0,
        'memory_hits' => 0,
        'memory_misses' => 0,
        'total_requests' => 0,
        'fallback_events' => 0
    ];
    
    /**
     * Initialize cache manager
     */
    public static function init(array $config = []): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Load Redis configuration
        $redisConfigFile = dirname(__DIR__, 2) . '/config/redis.php';
        $redisConfig = file_exists($redisConfigFile) ? require $redisConfigFile : [];
        
        self::$config = array_merge([
            'strategy' => 'multi_tier', // 'redis_only', 'file_only', 'multi_tier'
            'redis_enabled' => true,
            'file_cache_enabled' => true,
            'memory_cache_enabled' => true,
            'fallback_enabled' => true,
            'cache_ttl' => [
                'default' => 300,
                'reference' => $redisConfig['ttl']['reference_data'] ?? 3600,
                'query' => $redisConfig['ttl']['query_cache'] ?? 300,
                'product' => $redisConfig['ttl']['product_data'] ?? 1800,
                'customer' => $redisConfig['ttl']['customer_aging'] ?? 900,
                'inventory' => $redisConfig['ttl']['inventory_data'] ?? 600
            ]
        ], $config);
        
        // Initialize Redis if enabled
        if (self::$config['redis_enabled']) {
            $redisInitialized = RedisCache::init($redisConfig['default'] ?? []);
            if (!$redisInitialized) {
                Logger::warning('Redis initialization failed, falling back to file cache');
                self::$config['redis_enabled'] = false;
            }
        }
        
        self::$initialized = true;
        
        Logger::info('CacheManager initialized', [
            'strategy' => self::$config['strategy'],
            'redis_enabled' => self::$config['redis_enabled'],
            'file_cache_enabled' => self::$config['file_cache_enabled']
        ]);
    }
    
    /**
     * Get value from cache with multi-tier lookup
     */
    public static function get(string $key, ?string $type = null): mixed
    {
        self::ensureInitialized();
        self::$stats['total_requests']++;
        
        $ttl = self::$config['cache_ttl'][$type] ?? self::$config['cache_ttl']['default'];
        
        switch (self::$config['strategy']) {
            case 'redis_only':
                return self::getFromRedis($key);
                
            case 'file_only':
                return self::getFromFile($key);
                
            case 'multi_tier':
            default:
                return self::getMultiTier($key);
        }
    }
    
    /**
     * Set value in cache with multi-tier storage
     */
    public static function set(string $key, mixed $value, ?int $ttl = null, ?string $type = null): bool
    {
        self::ensureInitialized();
        
        $ttl = $ttl ?? (self::$config['cache_ttl'][$type] ?? self::$config['cache_ttl']['default']);
        $success = false;
        
        switch (self::$config['strategy']) {
            case 'redis_only':
                return self::setToRedis($key, $value, $ttl);
                
            case 'file_only':
                return self::setToFile($key, $value, $ttl);
                
            case 'multi_tier':
            default:
                return self::setMultiTier($key, $value, $ttl);
        }
    }
    
    /**
     * Delete value from all cache layers
     */
    public static function delete(string $key): bool
    {
        self::ensureInitialized();
        
        $results = [];
        
        // Delete from Redis
        if (self::$config['redis_enabled']) {
            $results['redis'] = RedisCache::delete($key);
        }
        
        // Delete from file cache
        if (self::$config['file_cache_enabled']) {
            $results['file'] = self::deleteFromFile($key);
        }
        
        return !in_array(false, $results, true);
    }
    
    /**
     * Delete pattern from all cache layers
     */
    public static function deletePattern(string $pattern): int
    {
        self::ensureInitialized();
        
        $totalDeleted = 0;
        
        // Delete from Redis
        if (self::$config['redis_enabled']) {
            $totalDeleted += RedisCache::deletePattern($pattern);
        }
        
        // Delete from file cache
        if (self::$config['file_cache_enabled']) {
            $totalDeleted += self::deletePatternFromFile($pattern);
        }
        
        return $totalDeleted;
    }
    
    /**
     * Flush all cache layers
     */
    public static function flush(): bool
    {
        self::ensureInitialized();
        
        $results = [];
        
        // Flush Redis
        if (self::$config['redis_enabled']) {
            $results['redis'] = RedisCache::flush();
        }
        
        // Flush file cache
        if (self::$config['file_cache_enabled']) {
            $results['file'] = self::flushFileCache();
        }
        
        return !in_array(false, $results, true);
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        self::ensureInitialized();
        
        $stats = self::$stats;
        
        // Calculate hit rates
        $totalRequests = $stats['total_requests'];
        if ($totalRequests > 0) {
            $stats['overall_hit_rate'] = round(
                (($stats['redis_hits'] + $stats['file_hits'] + $stats['memory_hits']) / $totalRequests) * 100, 
                2
            );
            $stats['redis_hit_rate'] = round(
                ($stats['redis_hits'] / ($stats['redis_hits'] + $stats['redis_misses'] ?: 1)) * 100, 
                2
            );
            $stats['file_hit_rate'] = round(
                ($stats['file_hits'] / ($stats['file_hits'] + $stats['file_misses'] ?: 1)) * 100, 
                2
            );
        } else {
            $stats['overall_hit_rate'] = 0;
            $stats['redis_hit_rate'] = 0;
            $stats['file_hit_rate'] = 0;
        }
        
        // Add Redis stats if available
        if (self::$config['redis_enabled']) {
            $stats['redis_server'] = RedisCache::getStats();
        }
        
        $stats['config'] = self::$config;
        
        return $stats;
    }
    
    /**
     * Health check for all cache layers
     */
    public static function healthCheck(): array
    {
        self::ensureInitialized();
        
        $health = [
            'overall_status' => 'healthy',
            'cache_layers' => [],
            'performance_metrics' => self::getStats()
        ];
        
        // Check Redis health
        if (self::$config['redis_enabled']) {
            $redisHealth = RedisCache::healthCheck();
            $health['cache_layers']['redis'] = $redisHealth;
            
            if ($redisHealth['status'] !== 'healthy') {
                $health['overall_status'] = 'degraded';
            }
        }
        
        // Check file cache health
        if (self::$config['file_cache_enabled']) {
            $fileHealth = self::checkFileCacheHealth();
            $health['cache_layers']['file'] = $fileHealth;
            
            if ($fileHealth['status'] !== 'healthy') {
                $health['overall_status'] = 'degraded';
            }
        }
        
        return $health;
    }
    
    /**
     * Warm up cache with frequently accessed data
     */
    public static function warmUp(): void
    {
        self::ensureInitialized();
        
        Logger::info('Starting cache warm-up process');
        
        try {
            // Warm up reference data
            ReferenceDataCache::warmUp();
            
            // Warm up common queries
            QueryCache::warmUp();
            
            Logger::info('Cache warm-up completed successfully');
            
        } catch (\Exception $e) {
            Logger::error('Cache warm-up failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get from Redis cache layer
     */
    private static function getFromRedis(string $key): mixed
    {
        if (!self::$config['redis_enabled']) {
            return false;
        }
        
        $value = RedisCache::get($key);
        
        if ($value !== false) {
            self::$stats['redis_hits']++;
            return $value;
        }
        
        self::$stats['redis_misses']++;
        return false;
    }
    
    /**
     * Set to Redis cache layer
     */
    private static function setToRedis(string $key, mixed $value, int $ttl): bool
    {
        if (!self::$config['redis_enabled']) {
            return false;
        }
        
        return RedisCache::set($key, $value, $ttl);
    }
    
    /**
     * Multi-tier cache get with fallback
     */
    private static function getMultiTier(string $key): mixed
    {
        // Try Redis first
        if (self::$config['redis_enabled']) {
            $value = self::getFromRedis($key);
            if ($value !== false) {
                return $value;
            }
        }
        
        // Fallback to file cache
        if (self::$config['file_cache_enabled']) {
            $value = self::getFromFile($key);
            if ($value !== false) {
                // Promote to Redis if available
                if (self::$config['redis_enabled']) {
                    $ttl = self::$config['cache_ttl']['default'];
                    RedisCache::set($key, $value, $ttl);
                }
                return $value;
            }
        }
        
        return false;
    }
    
    /**
     * Multi-tier cache set
     */
    private static function setMultiTier(string $key, mixed $value, int $ttl): bool
    {
        $results = [];
        
        // Set in Redis
        if (self::$config['redis_enabled']) {
            $results['redis'] = RedisCache::set($key, $value, $ttl);
        }
        
        // Set in file cache as backup
        if (self::$config['file_cache_enabled']) {
            $results['file'] = self::setToFile($key, $value, $ttl);
        }
        
        // Success if at least one layer succeeded
        return !empty($results) && in_array(true, $results, true);
    }
    
    /**
     * Get from file cache
     */
    private static function getFromFile(string $key): mixed
    {
        $cacheFile = self::getFileCachePath($key);
        
        if (!file_exists($cacheFile)) {
            self::$stats['file_misses']++;
            return false;
        }
        
        $cached = @unserialize(file_get_contents($cacheFile));
        
        if ($cached && is_array($cached) && $cached['expires'] > time()) {
            self::$stats['file_hits']++;
            return $cached['data'];
        }
        
        // Expired, clean up
        @unlink($cacheFile);
        self::$stats['file_misses']++;
        return false;
    }
    
    /**
     * Set to file cache
     */
    private static function setToFile(string $key, mixed $value, int $ttl): bool
    {
        $cacheFile = self::getFileCachePath($key);
        $cacheDir = dirname($cacheFile);
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $cached = [
            'data' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return @file_put_contents($cacheFile, serialize($cached), LOCK_EX) !== false;
    }
    
    /**
     * Delete from file cache
     */
    private static function deleteFromFile(string $key): bool
    {
        $cacheFile = self::getFileCachePath($key);
        
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Delete pattern from file cache
     */
    private static function deletePatternFromFile(string $pattern): int
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        
        if (!is_dir($cacheDir)) {
            return 0;
        }
        
        $pattern = str_replace('*', '.*', $pattern);
        $deleted = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match("/^{$pattern}$/", basename($file, '.cache'))) {
                if (@unlink($file->getPathname())) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Flush file cache
     */
    private static function flushFileCache(): bool
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        
        if (!is_dir($cacheDir)) {
            return true;
        }
        
        $files = glob($cacheDir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }
    
    /**
     * Check file cache health
     */
    private static function checkFileCacheHealth(): array
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        
        $health = [
            'status' => 'healthy',
            'writable' => false,
            'size' => 0,
            'file_count' => 0,
            'errors' => []
        ];
        
        // Check if cache directory exists and is writable
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0755, true)) {
                $health['status'] = 'error';
                $health['errors'][] = 'Cache directory does not exist and cannot be created';
                return $health;
            }
        }
        
        $health['writable'] = is_writable($cacheDir);
        
        if (!$health['writable']) {
            $health['status'] = 'error';
            $health['errors'][] = 'Cache directory is not writable';
        }
        
        // Calculate cache size and file count
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $health['size'] += $file->getSize();
                    $health['file_count']++;
                }
            }
        } catch (\Exception $e) {
            $health['errors'][] = 'Could not calculate cache statistics';
        }
        
        return $health;
    }
    
    /**
     * Get file cache path
     */
    private static function getFileCachePath(string $key): string
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        return $cacheDir . '/cache_' . md5($key) . '.cache';
    }
    
    /**
     * Ensure cache manager is initialized
     */
    private static function ensureInitialized(): void
    {
        if (!self::$initialized) {
            self::init();
        }
    }
}