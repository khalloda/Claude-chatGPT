<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Core\Env;
use PDO;
use Exception;

/**
 * Smart Query Cache Service
 * 
 * Advanced query result caching with intelligent invalidation,
 * dependency tracking, and performance optimization.
 */
class SmartQueryCache
{
    private static ?SmartQueryCache $instance = null;
    private RedisCache $redisCache;
    private QueryCache $queryCache;
    private array $dependencies = [];
    private array $tags = [];
    
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
    
    public function __construct()
    {
        $this->redisCache = RedisCache::getInstance();
        $this->queryCache = QueryCache::getInstance();
        $this->loadDependencyMappings();
    }
    
    /**
     * Load dependency mappings for intelligent cache invalidation
     */
    private function loadDependencyMappings(): void
    {
        $this->dependencies = [
            'products' => ['categories', 'makes', 'vehicle_models', 'product_stocks'],
            'invoices' => ['customers', 'invoice_lines', 'invoice_payments'],
            'invoice_lines' => ['products', 'warehouses'],
            'product_stocks' => ['products', 'warehouses'],
            'customers' => ['invoices'],
            'categories' => ['products'],
            'makes' => ['products'],
            'vehicle_models' => ['products'],
            'warehouses' => ['product_stocks']
        ];
        
        $this->tags = [
            'product_listing' => ['products', 'categories', 'makes', 'vehicle_models', 'product_stocks'],
            'customer_aging' => ['customers', 'invoices', 'invoice_payments'],
            'inventory_status' => ['products', 'product_stocks', 'warehouses'],
            'invoice_details' => ['invoices', 'invoice_lines', 'customers', 'products'],
            'reference_data' => ['categories', 'makes', 'vehicle_models', 'warehouses']
        ];
    }
    
    /**
     * Execute query with smart caching
     */
    public function query(string $sql, array $params = [], array $options = []): array
    {
        $cacheKey = $this->generateQueryCacheKey($sql, $params, $options);
        $ttl = $options['ttl'] ?? $this->calculateOptimalTTL($sql);
        $tags = $options['tags'] ?? $this->extractTagsFromQuery($sql);
        
        // Try to get from cache first
        $cached = $this->getCachedResult($cacheKey);
        if ($cached !== null) {
            $this->logCacheHit($cacheKey, $tags);
            return $cached;
        }
        
        // Execute query
        $startTime = microtime(true);
        $results = $this->executeQuery($sql, $params);
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // Cache the results with tags
        $this->cacheResultWithTags($cacheKey, $results, $ttl, $tags);
        
        $this->logCacheMiss($cacheKey, $executionTime, $tags);
        
        return $results;
    }
    
    /**
     * Execute prepared query
     */
    private function executeQuery(string $sql, array $params): array
    {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Query execution failed', [
                'sql' => substr($sql, 0, 200) . '...',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Generate cache key for query
     */
    private function generateQueryCacheKey(string $sql, array $params, array $options): string
    {
        $normalized = $this->normalizeQuery($sql);
        $keyData = [
            'query' => $normalized,
            'params' => $params,
            'version' => $options['version'] ?? 'v1'
        ];
        
        return 'smart_query:' . hash('sha256', serialize($keyData));
    }
    
    /**
     * Normalize query for consistent caching
     */
    private function normalizeQuery(string $sql): string
    {
        // Remove comments, extra whitespace, and normalize case
        $normalized = preg_replace('/\/\*.*?\*\//', '', $sql);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim(strtolower($normalized));
        
        return $normalized;
    }
    
    /**
     * Calculate optimal TTL based on query characteristics
     */
    private function calculateOptimalTTL(string $sql): int
    {
        $normalizedSql = strtolower($sql);
        
        // Reference data - long TTL (1 hour)
        if (preg_match('/from\s+(categories|makes|vehicle_models|warehouses)\b/', $normalizedSql)) {
            return 3600;
        }
        
        // Product listings - medium TTL (15 minutes)
        if (preg_match('/from\s+products\b/', $normalizedSql)) {
            return 900;
        }
        
        // Invoice data - shorter TTL (5 minutes)
        if (preg_match('/from\s+invoices\b/', $normalizedSql)) {
            return 300;
        }
        
        // Stock data - very short TTL (2 minutes)
        if (preg_match('/from\s+product_stocks\b/', $normalizedSql)) {
            return 120;
        }
        
        // Customer data - medium TTL (10 minutes)
        if (preg_match('/from\s+customers\b/', $normalizedSql)) {
            return 600;
        }
        
        // Default TTL (5 minutes)
        return 300;
    }
    
    /**
     * Extract tags from query for dependency tracking
     */
    private function extractTagsFromQuery(string $sql): array
    {
        $tags = [];
        $normalizedSql = strtolower($sql);
        
        // Extract table names from FROM and JOIN clauses
        preg_match_all('/(?:from|join)\s+(\w+)/', $normalizedSql, $matches);
        
        foreach ($matches[1] as $tableName) {
            $tags[] = "table:{$tableName}";
        }
        
        // Add functional tags based on query patterns
        if (preg_match('/group\s+by|count\s*\(|sum\s*\(|avg\s*\(/', $normalizedSql)) {
            $tags[] = 'type:aggregate';
        }
        
        if (preg_match('/order\s+by/', $normalizedSql)) {
            $tags[] = 'type:sorted';
        }
        
        if (preg_match('/limit\s+\d+/', $normalizedSql)) {
            $tags[] = 'type:paginated';
        }
        
        return array_unique($tags);
    }
    
    /**
     * Get cached result with fallback strategy
     */
    private function getCachedResult(string $cacheKey): ?array
    {
        // Try Redis cache first
        $result = $this->redisCache->get($cacheKey);
        if ($result !== null) {
            return $result;
        }
        
        // Fallback to file-based cache
        $result = $this->queryCache->get($cacheKey);
        if ($result !== null) {
            // Warm Redis cache with file cache result
            $this->redisCache->set($cacheKey, $result, 300);
            return $result;
        }
        
        return null;
    }
    
    /**
     * Cache result with tags for intelligent invalidation
     */
    private function cacheResultWithTags(string $cacheKey, array $results, int $ttl, array $tags): void
    {
        try {
            // Cache in both Redis and file cache
            $this->redisCache->set($cacheKey, $results, $ttl);
            $this->queryCache->set($cacheKey, $results, $ttl);
            
            // Store tag associations for invalidation
            foreach ($tags as $tag) {
                $this->addCacheKeyToTag($tag, $cacheKey, $ttl);
            }
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Failed to cache results', [
                'cache_key' => $cacheKey,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Add cache key to tag for invalidation tracking
     */
    private function addCacheKeyToTag(string $tag, string $cacheKey, int $ttl): void
    {
        $tagKey = "cache_tag:{$tag}";
        
        try {
            // Get current keys for this tag
            $taggedKeys = $this->redisCache->get($tagKey) ?: [];
            
            // Add new key with expiration time
            $taggedKeys[$cacheKey] = time() + $ttl;
            
            // Clean expired keys
            $now = time();
            $taggedKeys = array_filter($taggedKeys, fn($expiry) => $expiry > $now);
            
            // Store updated tag mapping
            $this->redisCache->set($tagKey, $taggedKeys, $ttl + 300); // Tag lives longer
            
        } catch (Exception $e) {
            Logger::warning('Smart query cache: Failed to update tag mapping', [
                'tag' => $tag,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Invalidate cache by table changes
     */
    public function invalidateByTable(string $tableName): int
    {
        $invalidatedCount = 0;
        
        try {
            // Direct table invalidation
            $directTag = "table:{$tableName}";
            $invalidatedCount += $this->invalidateByTag($directTag);
            
            // Cascade invalidation to dependent tables
            if (isset($this->dependencies[$tableName])) {
                foreach ($this->dependencies[$tableName] as $dependentTable) {
                    $dependentTag = "table:{$dependentTable}";
                    $invalidatedCount += $this->invalidateByTag($dependentTag);
                }
            }
            
            Logger::info('Smart query cache: Table invalidation completed', [
                'table' => $tableName,
                'invalidated_count' => $invalidatedCount
            ]);
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Table invalidation failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
        }
        
        return $invalidatedCount;
    }
    
    /**
     * Invalidate cache by tag
     */
    public function invalidateByTag(string $tag): int
    {
        $invalidatedCount = 0;
        
        try {
            $tagKey = "cache_tag:{$tag}";
            $taggedKeys = $this->redisCache->get($tagKey);
            
            if (!empty($taggedKeys) && is_array($taggedKeys)) {
                foreach (array_keys($taggedKeys) as $cacheKey) {
                    // Remove from both Redis and file cache
                    $this->redisCache->delete($cacheKey);
                    $this->queryCache->delete($cacheKey);
                    $invalidatedCount++;
                }
                
                // Clear the tag mapping
                $this->redisCache->delete($tagKey);
            }
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Tag invalidation failed', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
        }
        
        return $invalidatedCount;
    }
    
    /**
     * Invalidate cache by functional tag (e.g., customer_aging)
     */
    public function invalidateByFunctionalTag(string $functionalTag): int
    {
        $invalidatedCount = 0;
        
        if (isset($this->tags[$functionalTag])) {
            foreach ($this->tags[$functionalTag] as $tableName) {
                $invalidatedCount += $this->invalidateByTable($tableName);
            }
        }
        
        return $invalidatedCount;
    }
    
    /**
     * Preload frequently accessed queries
     */
    public function preloadFrequentQueries(): array
    {
        $preloadResults = [];
        
        $frequentQueries = [
            'categories' => [
                'sql' => 'SELECT id, name FROM categories ORDER BY name',
                'params' => [],
                'tags' => ['reference_data']
            ],
            'makes' => [
                'sql' => 'SELECT id, name FROM makes ORDER BY name',
                'params' => [],
                'tags' => ['reference_data']
            ],
            'warehouses' => [
                'sql' => 'SELECT id, name FROM warehouses ORDER BY name',
                'params' => [],
                'tags' => ['reference_data']
            ],
            'top_products' => [
                'sql' => 'SELECT p.id, p.name, p.code, SUM(ps.qty_on_hand) as total_stock 
                         FROM products p 
                         LEFT JOIN product_stocks ps ON ps.product_id = p.id 
                         GROUP BY p.id, p.name, p.code 
                         ORDER BY total_stock DESC 
                         LIMIT 50',
                'params' => [],
                'tags' => ['product_listing']
            ]
        ];
        
        foreach ($frequentQueries as $queryName => $queryConfig) {
            try {
                $results = $this->query(
                    $queryConfig['sql'], 
                    $queryConfig['params'],
                    ['tags' => $queryConfig['tags'], 'ttl' => 3600]
                );
                
                $preloadResults[$queryName] = [
                    'status' => 'success',
                    'count' => count($results)
                ];
                
            } catch (Exception $e) {
                $preloadResults[$queryName] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        Logger::info('Smart query cache: Frequent queries preloaded', $preloadResults);
        
        return $preloadResults;
    }
    
    /**
     * Get cache statistics
     */
    public function getStatistics(): array
    {
        return [
            'redis_stats' => $this->redisCache->getStats(),
            'file_cache_stats' => $this->queryCache->getStats(),
            'dependency_mappings' => count($this->dependencies),
            'functional_tags' => count($this->tags),
            'cache_performance' => $this->getCachePerformanceStats()
        ];
    }
    
    /**
     * Get cache performance statistics
     */
    private function getCachePerformanceStats(): array
    {
        // This would typically track hits/misses over time
        // For now, return basic information
        return [
            'note' => 'Performance tracking would be implemented with persistent storage',
            'recommendations' => [
                'Monitor cache hit rates for query optimization',
                'Track slow queries for index recommendations',
                'Analyze invalidation patterns for TTL optimization'
            ]
        ];
    }
    
    /**
     * Log cache hit
     */
    private function logCacheHit(string $cacheKey, array $tags): void
    {
        Logger::debug('Smart query cache: Cache hit', [
            'cache_key' => substr($cacheKey, 0, 32) . '...',
            'tags' => $tags
        ]);
    }
    
    /**
     * Log cache miss
     */
    private function logCacheMiss(string $cacheKey, float $executionTime, array $tags): void
    {
        $logLevel = $executionTime > 100 ? 'warning' : 'debug';
        
        Logger::log($logLevel, 'Smart query cache: Cache miss', [
            'cache_key' => substr($cacheKey, 0, 32) . '...',
            'execution_time_ms' => round($executionTime, 2),
            'tags' => $tags
        ]);
    }
    
    /**
     * Clear all cached queries
     */
    public function clearAll(): bool
    {
        try {
            $this->redisCache->flush();
            $this->queryCache->clearAll();
            
            Logger::info('Smart query cache: All caches cleared');
            return true;
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Failed to clear all caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Optimize cache by removing expired entries
     */
    public function optimize(): array
    {
        $optimizationResults = [
            'expired_keys_removed' => 0,
            'invalid_tags_cleaned' => 0,
            'memory_freed' => 0
        ];
        
        try {
            // Clean expired tag mappings
            $tagPattern = 'cache_tag:*';
            $tagKeys = $this->redisCache->getKeysByPattern($tagPattern);
            $now = time();
            
            foreach ($tagKeys as $tagKey) {
                $taggedKeys = $this->redisCache->get($tagKey);
                if (is_array($taggedKeys)) {
                    $validKeys = array_filter($taggedKeys, fn($expiry) => $expiry > $now);
                    
                    if (count($validKeys) !== count($taggedKeys)) {
                        if (empty($validKeys)) {
                            $this->redisCache->delete($tagKey);
                            $optimizationResults['invalid_tags_cleaned']++;
                        } else {
                            $this->redisCache->set($tagKey, $validKeys, 3600);
                        }
                        
                        $optimizationResults['expired_keys_removed'] += count($taggedKeys) - count($validKeys);
                    }
                }
            }
            
            Logger::info('Smart query cache: Optimization completed', $optimizationResults);
            
        } catch (Exception $e) {
            Logger::error('Smart query cache: Optimization failed', [
                'error' => $e->getMessage()
            ]);
            $optimizationResults['error'] = $e->getMessage();
        }
        
        return $optimizationResults;
    }
}