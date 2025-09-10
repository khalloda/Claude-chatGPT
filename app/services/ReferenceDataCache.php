<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

/**
 * Reference Data Cache Service
 * 
 * Caches frequently accessed reference data (categories, makes, models, warehouses)
 * to eliminate N+1 queries when loading product listings and forms.
 */
final class ReferenceDataCache
{
    private static array $cache = [];
    private static int $cacheLifetime = 3600; // 1 hour
    
    /**
     * Get all categories with caching
     */
    public static function getCategories(): array
    {
        return self::getCachedData('categories', function() {
            $pdo = DB::conn();
            $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        });
    }
    
    /**
     * Get all makes with caching
     */
    public static function getMakes(): array
    {
        return self::getCachedData('makes', function() {
            $pdo = DB::conn();
            $stmt = $pdo->query('SELECT id, name FROM makes ORDER BY name');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        });
    }
    
    /**
     * Get models for a specific make with caching
     */
    public static function getModels(?int $makeId = null): array
    {
        $cacheKey = 'models_' . ($makeId ?? 'all');
        
        return self::getCachedData($cacheKey, function() use ($makeId) {
            $pdo = DB::conn();
            
            if ($makeId) {
                $stmt = $pdo->prepare('SELECT id, name, make_id FROM vehicle_models WHERE make_id = ? ORDER BY name');
                $stmt->execute([$makeId]);
            } else {
                $stmt = $pdo->query('SELECT id, name, make_id FROM vehicle_models ORDER BY name');
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        });
    }
    
    /**
     * Get all warehouses with caching
     */
    public static function getWarehouses(): array
    {
        return self::getCachedData('warehouses', function() {
            $pdo = DB::conn();
            $stmt = $pdo->query('SELECT id, name FROM warehouses ORDER BY name');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        });
    }
    
    /**
     * Get category by ID from cache
     */
    public static function getCategoryById(int $id): ?array
    {
        $categories = self::getCategories();
        foreach ($categories as $category) {
            if ((int)$category['id'] === $id) {
                return $category;
            }
        }
        return null;
    }
    
    /**
     * Get make by ID from cache
     */
    public static function getMakeById(int $id): ?array
    {
        $makes = self::getMakes();
        foreach ($makes as $make) {
            if ((int)$make['id'] === $id) {
                return $make;
            }
        }
        return null;
    }
    
    /**
     * Get model by ID from cache
     */
    public static function getModelById(int $id): ?array
    {
        $models = self::getModels();
        foreach ($models as $model) {
            if ((int)$model['id'] === $id) {
                return $model;
            }
        }
        return null;
    }
    
    /**
     * Get warehouse by ID from cache
     */
    public static function getWarehouseById(int $id): ?array
    {
        $warehouses = self::getWarehouses();
        foreach ($warehouses as $warehouse) {
            if ((int)$warehouse['id'] === $id) {
                return $warehouse;
            }
        }
        return null;
    }
    
    /**
     * Clear all cached data
     */
    public static function clearAll(): void
    {
        self::$cache = [];
        
        // Also clear file cache if exists
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/ref_data_*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Clear specific cache key
     */
    public static function clear(string $key): void
    {
        unset(self::$cache[$key]);
        
        $cacheFile = self::getCacheFile($key);
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
    
    /**
     * Set cache lifetime in seconds
     */
    public static function setCacheLifetime(int $seconds): void
    {
        self::$cacheLifetime = $seconds;
    }
    
    /**
     * Generic cached data retrieval with memory and file caching
     */
    private static function getCachedData(string $key, callable $dataLoader): array
    {
        // Check memory cache first
        if (isset(self::$cache[$key])) {
            $cached = self::$cache[$key];
            if ($cached['expires'] > time()) {
                return $cached['data'];
            }
            unset(self::$cache[$key]);
        }
        
        // Check file cache
        $cacheFile = self::getCacheFile($key);
        if (file_exists($cacheFile)) {
            $cached = @unserialize(file_get_contents($cacheFile));
            if ($cached && is_array($cached) && $cached['expires'] > time()) {
                // Store in memory cache too
                self::$cache[$key] = $cached;
                return $cached['data'];
            }
            @unlink($cacheFile);
        }
        
        // Load fresh data
        $data = $dataLoader();
        $expires = time() + self::$cacheLifetime;
        
        $cached = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Store in memory cache
        self::$cache[$key] = $cached;
        
        // Store in file cache
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, serialize($cached), LOCK_EX);
        
        return $data;
    }
    
    /**
     * Get cache file path for key
     */
    private static function getCacheFile(string $key): string
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        return $cacheDir . '/ref_data_' . md5($key) . '.cache';
    }
    
    /**
     * Get cache statistics for monitoring
     */
    public static function getStats(): array
    {
        $stats = [
            'memory_cache_size' => count(self::$cache),
            'cache_lifetime' => self::$cacheLifetime,
            'cache_keys' => []
        ];
        
        foreach (self::$cache as $key => $data) {
            $stats['cache_keys'][$key] = [
                'expires_in' => max(0, $data['expires'] - time()),
                'created_at' => date('Y-m-d H:i:s', $data['created']),
                'size' => count($data['data'])
            ];
        }
        
        return $stats;
    }
    
    /**
     * Warm up cache by pre-loading all reference data
     */
    public static function warmUp(): void
    {
        self::getCategories();
        self::getMakes();
        self::getModels();
        self::getWarehouses();
    }
}