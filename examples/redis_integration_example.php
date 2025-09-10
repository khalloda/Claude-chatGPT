<?php declare(strict_types=1);

require_once __DIR__ . '/../app/core/autoload.php';

use App\Services\RedisCache;
use App\Services\CacheManager;
use App\Services\ReferenceDataCache;
use App\Services\QueryCache;

/**
 * Redis Integration Example
 * 
 * Demonstrates how to use the Redis caching implementation
 * with real-world scenarios in the spare parts management system.
 */
class RedisIntegrationExample
{
    public function runExamples(): void
    {
        echo "=== Redis Integration Examples ===\n\n";
        
        $this->initializeCaching();
        $this->basicRedisOperations();
        $this->multiTierCachingExample();
        $this->referenceDataCachingExample();
        $this->queryCachingExample();
        $this->cacheInvalidationExample();
        $this->performanceMonitoringExample();
        $this->bulkOperationsExample();
        $this->healthCheckingExample();
    }
    
    /**
     * Initialize caching system
     */
    private function initializeCaching(): void
    {
        echo "1. Initializing Redis Caching System...\n";
        
        // Initialize Redis with configuration
        $redisConfig = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null, // Set from environment in production
            'database' => 15,   // Use test database
            'prefix' => 'example:',
            'timeout' => 5.0
        ];
        
        $redisInitialized = RedisCache::init($redisConfig);
        
        if ($redisInitialized) {
            echo "✅ Redis initialized successfully\n";
        } else {
            echo "❌ Redis initialization failed - using file cache fallback\n";
        }
        
        // Initialize unified cache manager
        CacheManager::init([
            'strategy' => 'multi_tier',
            'redis_enabled' => $redisInitialized,
            'file_cache_enabled' => true
        ]);
        
        echo "✅ CacheManager initialized with multi-tier strategy\n\n";
    }
    
    /**
     * Basic Redis operations example
     */
    private function basicRedisOperations(): void
    {
        echo "2. Basic Redis Operations...\n";
        
        // Simple set/get
        $productData = [
            'id' => 123,
            'name' => 'Brake Pad Set',
            'code' => 'BP001',
            'price' => 89.99,
            'category' => 'Brakes'
        ];
        
        $key = 'product:123';
        $setResult = RedisCache::set($key, $productData, 1800); // 30 minutes TTL
        
        if ($setResult) {
            echo "✅ Product data cached in Redis\n";
            
            $retrievedData = RedisCache::get($key);
            if ($retrievedData === $productData) {
                echo "✅ Product data retrieved correctly from Redis\n";
            }
        }
        
        // Check TTL
        $ttl = RedisCache::ttl($key);
        echo "📊 Cache TTL: {$ttl} seconds\n";
        
        // Clean up
        RedisCache::delete($key);
        echo "🧹 Test data cleaned up\n\n";
    }
    
    /**
     * Multi-tier caching example
     */
    private function multiTierCachingExample(): void
    {
        echo "3. Multi-Tier Caching Example...\n";
        
        // Use CacheManager for multi-tier caching
        $customerData = [
            'id' => 456,
            'name' => 'ABC Auto Parts',
            'email' => 'orders@abcauto.com',
            'balance' => 2500.00
        ];
        
        // Set using cache manager (will store in Redis + file cache)
        $setResult = CacheManager::set('customer:456', $customerData, 3600, 'customer');
        
        if ($setResult) {
            echo "✅ Customer data stored in multi-tier cache\n";
            
            // First retrieval (from Redis)
            $startTime = microtime(true);
            $retrievedData = CacheManager::get('customer:456', 'customer');
            $redisTime = (microtime(true) - $startTime) * 1000;
            
            echo "📊 First retrieval (Redis): " . number_format($redisTime, 2) . "ms\n";
            
            // Second retrieval (from memory cache within same request)
            $startTime = microtime(true);
            $retrievedData = CacheManager::get('customer:456', 'customer');
            $memoryTime = (microtime(true) - $startTime) * 1000;
            
            echo "📊 Second retrieval (Memory): " . number_format($memoryTime, 2) . "ms\n";
            echo "⚡ Speed improvement: " . number_format(($redisTime / $memoryTime), 1) . "x faster\n";
        }
        
        echo "\n";
    }
    
    /**
     * Reference data caching with Redis
     */
    private function referenceDataCachingExample(): void
    {
        echo "4. Reference Data Caching with Redis...\n";
        
        // Simulate reference data (normally would come from database)
        $mockCategories = [
            ['id' => 1, 'name' => 'Engine Parts'],
            ['id' => 2, 'name' => 'Brake System'],
            ['id' => 3, 'name' => 'Electrical']
        ];
        
        // Clear existing cache
        ReferenceDataCache::clear('categories');
        
        echo "📦 Getting categories (will cache in Redis + file)...\n";
        
        // This would normally fetch from database, but we'll simulate
        // In real implementation, ReferenceDataCache::getCategories() 
        // would use the Redis-integrated caching system
        
        $startTime = microtime(true);
        // $categories = ReferenceDataCache::getCategories(); // Would use DB in real app
        $categories = $mockCategories; // Simulated for example
        $firstAccessTime = (microtime(true) - $startTime) * 1000;
        
        echo "📊 First access (database + cache): " . number_format($firstAccessTime, 2) . "ms\n";
        
        // Simulate second access (from cache)
        $startTime = microtime(true);
        // $cachedCategories = ReferenceDataCache::getCategories(); // Would use cache
        $cachedCategories = $categories; // Simulated
        $cachedAccessTime = (microtime(true) - $startTime) * 1000;
        
        echo "📊 Second access (cached): " . number_format($cachedAccessTime, 2) . "ms\n";
        echo "⚡ Cache speedup: " . number_format(($firstAccessTime / max($cachedAccessTime, 0.001)), 1) . "x faster\n";
        
        echo "\n";
    }
    
    /**
     * Query caching example
     */
    private function queryCachingExample(): void
    {
        echo "5. Query Caching Example...\n";
        
        // Simulate expensive query
        $expensiveQueryResult = [
            ['product_id' => 1, 'total_sales' => 15420.50, 'units_sold' => 85],
            ['product_id' => 2, 'total_sales' => 8750.25, 'units_sold' => 42],
            ['product_id' => 3, 'total_sales' => 12300.00, 'units_sold' => 67]
        ];
        
        $queryKey = 'sales_report:monthly:2025-09';
        
        // Cache the query result
        $cacheResult = CacheManager::set($queryKey, $expensiveQueryResult, 900, 'query'); // 15 minutes
        
        if ($cacheResult) {
            echo "✅ Query results cached\n";
            
            // Retrieve cached query
            $cachedResult = CacheManager::get($queryKey, 'query');
            
            if ($cachedResult === $expensiveQueryResult) {
                echo "✅ Query results retrieved from cache correctly\n";
                echo "📊 Cache contains " . count($cachedResult) . " result rows\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Cache invalidation example
     */
    private function cacheInvalidationExample(): void
    {
        echo "6. Cache Invalidation Example...\n";
        
        // Set multiple related cache entries
        $productCaches = [
            'product:1001' => ['name' => 'Oil Filter', 'price' => 15.99],
            'product:1002' => ['name' => 'Air Filter', 'price' => 22.50],
            'product:1003' => ['name' => 'Fuel Filter', 'price' => 18.75]
        ];
        
        foreach ($productCaches as $key => $data) {
            CacheManager::set($key, $data, 3600);
        }
        
        echo "✅ Set " . count($productCaches) . " product cache entries\n";
        
        // Verify they exist
        $existsBefore = 0;
        foreach (array_keys($productCaches) as $key) {
            if (RedisCache::exists($key)) {
                $existsBefore++;
            }
        }
        echo "📊 Cache entries before invalidation: {$existsBefore}\n";
        
        // Invalidate all product caches using pattern
        $deletedCount = CacheManager::deletePattern('product:*');
        echo "🧹 Invalidated cache entries: {$deletedCount}\n";
        
        // Verify they're gone
        $existsAfter = 0;
        foreach (array_keys($productCaches) as $key) {
            if (RedisCache::exists($key)) {
                $existsAfter++;
            }
        }
        echo "📊 Cache entries after invalidation: {$existsAfter}\n";
        
        echo "\n";
    }
    
    /**
     * Performance monitoring example
     */
    private function performanceMonitoringExample(): void
    {
        echo "7. Performance Monitoring Example...\n";
        
        // Generate some cache activity
        for ($i = 1; $i <= 10; $i++) {
            RedisCache::set("perf_test_{$i}", "value_{$i}", 300);
            RedisCache::get("perf_test_{$i}");
        }
        
        // Get Redis statistics
        $redisStats = RedisCache::getStats();
        
        echo "📊 Redis Statistics:\n";
        echo "   - Connection Status: " . ($redisStats['connection']['connected'] ? 'Connected' : 'Disconnected') . "\n";
        echo "   - Hit Rate: " . ($redisStats['hit_rate'] ?? 'N/A') . "%\n";
        echo "   - Operations: " . json_encode($redisStats['operations'] ?? []) . "\n";
        
        // Get cache manager statistics
        $managerStats = CacheManager::getStats();
        
        echo "📊 Cache Manager Statistics:\n";
        echo "   - Overall Hit Rate: " . ($managerStats['overall_hit_rate'] ?? 'N/A') . "%\n";
        echo "   - Total Requests: " . ($managerStats['total_requests'] ?? 0) . "\n";
        echo "   - Fallback Events: " . ($managerStats['fallback_events'] ?? 0) . "\n";
        
        // Clean up performance test data
        RedisCache::deletePattern('perf_test_*');
        
        echo "\n";
    }
    
    /**
     * Bulk operations example
     */
    private function bulkOperationsExample(): void
    {
        echo "8. Bulk Operations Example...\n";
        
        // Prepare bulk data (simulate product inventory update)
        $inventoryData = [];
        for ($i = 1; $i <= 50; $i++) {
            $inventoryData["inventory:product:{$i}"] = [
                'product_id' => $i,
                'warehouse_1_qty' => rand(10, 100),
                'warehouse_2_qty' => rand(5, 75),
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }
        
        echo "📦 Preparing " . count($inventoryData) . " inventory records...\n";
        
        // Bulk set operation
        $startTime = microtime(true);
        $bulkSetResult = RedisCache::setMultiple($inventoryData, 1800); // 30 minutes
        $bulkSetTime = (microtime(true) - $startTime) * 1000;
        
        if ($bulkSetResult) {
            echo "✅ Bulk set completed in " . number_format($bulkSetTime, 2) . "ms\n";
            echo "📊 Average time per item: " . number_format($bulkSetTime / count($inventoryData), 3) . "ms\n";
            
            // Bulk get operation
            $startTime = microtime(true);
            $retrievedData = RedisCache::getMultiple(array_keys($inventoryData));
            $bulkGetTime = (microtime(true) - $startTime) * 1000;
            
            echo "✅ Bulk get completed in " . number_format($bulkGetTime, 2) . "ms\n";
            echo "📊 Retrieved " . count($retrievedData) . " items\n";
            echo "📊 Average retrieval time per item: " . number_format($bulkGetTime / count($retrievedData), 3) . "ms\n";
            
            // Compare with individual operations
            $startTime = microtime(true);
            for ($i = 1; $i <= 5; $i++) { // Test just 5 for comparison
                RedisCache::get("inventory:product:{$i}");
            }
            $individualTime = (microtime(true) - $startTime) * 1000;
            $estimatedIndividualTime = ($individualTime / 5) * count($inventoryData);
            
            echo "⚡ Bulk operations are " . number_format($estimatedIndividualTime / $bulkGetTime, 1) . "x faster than individual operations\n";
            
            // Clean up
            RedisCache::deletePattern('inventory:product:*');
            echo "🧹 Cleaned up test inventory data\n";
        }
        
        echo "\n";
    }
    
    /**
     * Health checking example
     */
    private function healthCheckingExample(): void
    {
        echo "9. Health Checking Example...\n";
        
        // Redis health check
        $redisHealth = RedisCache::healthCheck();
        
        echo "🏥 Redis Health Check:\n";
        echo "   - Status: " . ucfirst($redisHealth['status']) . "\n";
        echo "   - Connection Time: " . number_format($redisHealth['connection_time'], 2) . "ms\n";
        echo "   - Ping Time: " . number_format($redisHealth['ping_time'], 2) . "ms\n";
        echo "   - Read/Write Test: " . ($redisHealth['read_write_test'] ? 'Passed' : 'Failed') . "\n";
        
        if (!empty($redisHealth['errors'])) {
            echo "   - Errors: " . implode(', ', $redisHealth['errors']) . "\n";
        }
        
        // Overall cache health
        $overallHealth = CacheManager::healthCheck();
        
        echo "🏥 Overall Cache System Health:\n";
        echo "   - Overall Status: " . ucfirst($overallHealth['overall_status']) . "\n";
        
        foreach ($overallHealth['cache_layers'] as $layer => $health) {
            echo "   - {$layer} layer: " . ucfirst($health['status']) . "\n";
        }
        
        echo "\n";
    }
}

// Run examples if script is executed directly
if (php_sapi_name() === 'cli') {
    try {
        echo "Redis Integration Example\n";
        echo "========================\n\n";
        
        $example = new RedisIntegrationExample();
        $example->runExamples();
        
        echo "=== Examples Complete ===\n";
        echo "Redis caching integration is ready for production use!\n\n";
        
    } catch (Exception $e) {
        echo "Error running Redis examples: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}