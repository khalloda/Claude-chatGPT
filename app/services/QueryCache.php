<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

/**
 * Query Result Caching Service
 * 
 * Provides efficient caching for database query results to reduce N+1 queries
 * and improve application performance.
 */
final class QueryCache
{
    private static array $cache = [];
    private static int $defaultTtl = 300; // 5 minutes
    private static bool $enabled = true;
    
    /**
     * Get cached query result or execute and cache
     */
    public static function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!self::$enabled) {
            return $callback();
        }
        
        $ttl = $ttl ?? self::$defaultTtl;
        
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
        
        // Execute callback and cache result
        $data = $callback();
        $expires = time() + $ttl;
        
        $cached = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Store in memory cache
        self::$cache[$key] = $cached;
        
        // Store in file cache
        self::storeFileCache($cacheFile, $cached);
        
        return $data;
    }
    
    /**
     * Cache query results by SQL and parameters
     */
    public static function query(string $sql, array $params = [], ?int $ttl = null): array
    {
        $key = self::generateQueryKey($sql, $params);
        
        return self::get($key, function() use ($sql, $params) {
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }, $ttl);
    }
    
    /**
     * Cache single row query results
     */
    public static function queryRow(string $sql, array $params = [], ?int $ttl = null): ?array
    {
        $key = self::generateQueryKey($sql . '_ROW', $params);
        
        return self::get($key, function() use ($sql, $params) {
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        }, $ttl);
    }
    
    /**
     * Cache scalar query results
     */
    public static function queryScalar(string $sql, array $params = [], ?int $ttl = null): mixed
    {
        $key = self::generateQueryKey($sql . '_SCALAR', $params);
        
        return self::get($key, function() use ($sql, $params) {
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        }, $ttl);
    }
    
    /**
     * Cached model finder method
     */
    public static function find(string $model, int $id, ?int $ttl = null): ?array
    {
        $key = "model_{$model}_find_{$id}";
        
        return self::get($key, function() use ($model, $id) {
            $className = "\\App\\Models\\{$model}";
            if (class_exists($className) && method_exists($className, 'find')) {
                return $className::find($id);
            }
            return null;
        }, $ttl);
    }
    
    /**
     * Cached model all method
     */
    public static function all(string $model, array $filters = [], ?int $ttl = null): array
    {
        $key = "model_{$model}_all_" . md5(serialize($filters));
        
        return self::get($key, function() use ($model, $filters) {
            $className = "\\App\\Models\\{$model}";
            if (class_exists($className) && method_exists($className, 'all')) {
                return $className::all(...$filters);
            }
            return [];
        }, $ttl);
    }
    
    /**
     * Preload multiple records to avoid N+1 queries
     */
    public static function preload(string $model, array $ids, ?int $ttl = null): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $key = "model_{$model}_preload_" . md5(implode(',', $ids));
        
        return self::get($key, function() use ($model, $ids) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "SELECT * FROM " . strtolower($model) . "s WHERE id IN ($placeholders)";
            
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Index by ID for quick lookup
            $indexed = [];
            foreach ($results as $row) {
                $indexed[$row['id']] = $row;
            }
            
            return $indexed;
        }, $ttl);
    }
    
    /**
     * Cache product stocks for multiple products
     */
    public static function preloadProductStocks(array $productIds, ?int $ttl = null): array
    {
        if (empty($productIds)) {
            return [];
        }
        
        $key = "product_stocks_" . md5(implode(',', $productIds));
        
        return self::get($key, function() use ($productIds) {
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $sql = "SELECT ps.product_id, ps.warehouse_id, ps.qty_on_hand, ps.qty_reserved,
                           w.name as warehouse_name
                    FROM product_stocks ps
                    JOIN warehouses w ON w.id = ps.warehouse_id
                    WHERE ps.product_id IN ($placeholders)
                    ORDER BY ps.product_id, w.name";
            
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($productIds);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Group by product ID
            $grouped = [];
            foreach ($results as $row) {
                $grouped[$row['product_id']][] = $row;
            }
            
            return $grouped;
        }, $ttl);
    }
    
    /**
     * Cache invoice items for multiple invoices
     */
    public static function preloadInvoiceItems(array $invoiceIds, ?int $ttl = null): array
    {
        if (empty($invoiceIds)) {
            return [];
        }
        
        $key = "invoice_items_" . md5(implode(',', $invoiceIds));
        
        return self::get($key, function() use ($invoiceIds) {
            $placeholders = str_repeat('?,', count($invoiceIds) - 1) . '?';
            
            // Detect table structure
            $pdo = DB::conn();
            $candidates = ['invoice_lines', 'invoice_items', 'sales_invoice_items'];
            $table = null;
            
            foreach ($candidates as $candidate) {
                $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables 
                                      WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
                $stmt->execute([$candidate]);
                if ($stmt->fetchColumn()) {
                    $table = $candidate;
                    break;
                }
            }
            
            if (!$table) {
                return [];
            }
            
            $sql = "SELECT il.invoice_id, il.product_id, il.qty, il.price,
                           (il.qty * il.price) as line_total,
                           p.code as product_code, p.name as product_name
                    FROM {$table} il
                    JOIN products p ON p.id = il.product_id
                    WHERE il.invoice_id IN ($placeholders)
                    ORDER BY il.invoice_id, il.id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($invoiceIds);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Group by invoice ID
            $grouped = [];
            foreach ($results as $row) {
                $grouped[$row['invoice_id']][] = $row;
            }
            
            return $grouped;
        }, $ttl);
    }
    
    /**
     * Clear cache by key or pattern
     */
    public static function forget(string $keyOrPattern): void
    {
        if (strpos($keyOrPattern, '*') !== false) {
            // Pattern matching
            $pattern = str_replace('*', '.*', $keyOrPattern);
            foreach (array_keys(self::$cache) as $key) {
                if (preg_match("/^{$pattern}$/", $key)) {
                    unset(self::$cache[$key]);
                }
            }
            
            // Clear file cache
            $cacheDir = self::getCacheDir();
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*.cache');
                foreach ($files as $file) {
                    $basename = basename($file, '.cache');
                    if (preg_match("/^{$pattern}$/", $basename)) {
                        @unlink($file);
                    }
                }
            }
        } else {
            // Exact key
            unset(self::$cache[$keyOrPattern]);
            $cacheFile = self::getCacheFile($keyOrPattern);
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }
    
    /**
     * Clear all cache
     */
    public static function flush(): void
    {
        self::$cache = [];
        
        $cacheDir = self::getCacheDir();
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/query_*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Enable/disable caching
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }
    
    /**
     * Set default TTL
     */
    public static function setDefaultTtl(int $seconds): void
    {
        self::$defaultTtl = $seconds;
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $stats = [
            'enabled' => self::$enabled,
            'memory_cache_size' => count(self::$cache),
            'default_ttl' => self::$defaultTtl,
            'cache_keys' => []
        ];
        
        foreach (self::$cache as $key => $data) {
            $stats['cache_keys'][$key] = [
                'expires_in' => max(0, $data['expires'] - time()),
                'created_at' => date('Y-m-d H:i:s', $data['created']),
                'size_bytes' => strlen(serialize($data['data']))
            ];
        }
        
        return $stats;
    }
    
    /**
     * Warm up cache with common queries
     */
    public static function warmUp(): void
    {
        // Warm up reference data
        ReferenceDataCache::warmUp();
        
        // Warm up common queries
        self::query("SELECT COUNT(*) FROM customers", [], 3600);
        self::query("SELECT COUNT(*) FROM products", [], 3600);
        self::query("SELECT COUNT(*) FROM invoices", [], 3600);
    }
    
    /**
     * Generate cache key for query
     */
    private static function generateQueryKey(string $sql, array $params): string
    {
        return 'query_' . md5($sql . serialize($params));
    }
    
    /**
     * Get cache directory
     */
    private static function getCacheDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache';
    }
    
    /**
     * Get cache file path
     */
    private static function getCacheFile(string $key): string
    {
        return self::getCacheDir() . '/query_' . md5($key) . '.cache';
    }
    
    /**
     * Store data in file cache
     */
    private static function storeFileCache(string $cacheFile, array $data): void
    {
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, serialize($data), LOCK_EX);
    }
}