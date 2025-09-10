<?php declare(strict_types=1);

/**
 * Comprehensive Query Optimization Testing Suite
 * 
 * Tests query performance improvements, validates optimizations,
 * and benchmarks database operations.
 */

require_once dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Services\QueryAnalyzer;
use App\Services\QueryOptimizer;
use App\Services\SmartQueryCache;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use App\Core\DB;
use App\Core\Logger;

class QueryOptimizationTestSuite
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private QueryAnalyzer $analyzer;
    private QueryOptimizer $optimizer;
    private SmartQueryCache $smartCache;
    
    public function __construct()
    {
        $this->analyzer = QueryAnalyzer::getInstance();
        $this->optimizer = QueryOptimizer::getInstance();
        $this->smartCache = SmartQueryCache::getInstance();
    }
    
    /**
     * Run all query optimization tests
     */
    public function runAllTests(): array
    {
        $this->log("Starting comprehensive query optimization test suite");
        
        $testGroups = [
            'Query Analysis' => [
                'testQueryAnalyzerFunctionality',
                'testIndexRecommendations',
                'testSlowQueryDetection'
            ],
            'Query Optimization' => [
                'testOptimizedProductListing',
                'testOptimizedCustomerAging',
                'testOptimizedInventoryLookup',
                'testBatchLoadOperations'
            ],
            'Model Optimizations' => [
                'testProductModelOptimizations',
                'testInvoiceModelOptimizations',
                'testCustomerModelOptimizations'
            ],
            'Smart Caching' => [
                'testSmartQueryCache',
                'testCacheInvalidation',
                'testCachePerformance',
                'testPreloadOperations'
            ],
            'Performance Benchmarks' => [
                'testQueryPerformanceBenchmarks',
                'testConcurrentQueryPerformance',
                'testMemoryUsageOptimization',
                'testScalingPerformance'
            ],
            'Integration Tests' => [
                'testEndToEndOptimizations',
                'testRealWorldScenarios',
                'testErrorHandlingOptimizations'
            ]
        ];
        
        foreach ($testGroups as $groupName => $tests) {
            $this->log("Running test group: {$groupName}");
            
            foreach ($tests as $testMethod) {
                $this->runTest($testMethod);
            }
        }
        
        return $this->generateReport();
    }
    
    /**
     * Run individual test
     */
    private function runTest(string $testMethod): void
    {
        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            if (!method_exists($this, $testMethod)) {
                throw new Exception("Test method {$testMethod} not found");
            }
            
            $result = $this->$testMethod();
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            if ($result === true) {
                $this->passed++;
                $status = 'PASS';
            } else {
                $this->failed++;
                $status = 'FAIL';
            }
            
            $this->results[] = [
                'test' => $testMethod,
                'status' => $status,
                'duration' => round($duration * 1000, 2),
                'memory' => $memoryUsed,
                'message' => is_string($result) ? $result : ''
            ];
            
            $this->log("  {$status}: {$testMethod} ({$this->results[count($this->results)-1]['duration']}ms)");
            
        } catch (Exception $e) {
            $this->failed++;
            $this->results[] = [
                'test' => $testMethod,
                'status' => 'ERROR',
                'duration' => 0,
                'memory' => 0,
                'message' => $e->getMessage()
            ];
            
            $this->log("  ERROR: {$testMethod} - " . $e->getMessage());
        }
    }
    
    /**
     * Test query analyzer functionality
     */
    private function testQueryAnalyzerFunctionality(): bool
    {
        $analysis = $this->analyzer->analyzeCurrentQueries();
        
        if (!isset($analysis['query_patterns']) || empty($analysis['query_patterns'])) {
            return "Query analyzer returned no patterns";
        }
        
        if (!isset($analysis['index_recommendations']) || !is_array($analysis['index_recommendations'])) {
            return "Query analyzer missing index recommendations";
        }
        
        if (!isset($analysis['performance_metrics'])) {
            return "Query analyzer missing performance metrics";
        }
        
        return true;
    }
    
    /**
     * Test index recommendations
     */
    private function testIndexRecommendations(): bool
    {
        $report = $this->analyzer->generateOptimizationReport();
        
        if (!isset($report['priority_recommendations']) || empty($report['priority_recommendations'])) {
            return "No priority recommendations generated";
        }
        
        $hasIndexRecommendations = false;
        foreach ($report['priority_recommendations'] as $recommendation) {
            if (isset($recommendation['type']) && $recommendation['type'] === 'Index Creation') {
                $hasIndexRecommendations = true;
                break;
            }
        }
        
        if (!$hasIndexRecommendations) {
            return "No index creation recommendations found";
        }
        
        return true;
    }
    
    /**
     * Test slow query detection
     */
    private function testSlowQueryDetection(): bool
    {
        $analysis = $this->analyzer->analyzeCurrentQueries();
        
        if (!isset($analysis['slow_queries'])) {
            return "Slow queries section missing from analysis";
        }
        
        // The slow query detection should at least try to analyze
        return true;
    }
    
    /**
     * Test optimized product listing
     */
    private function testOptimizedProductListing(): bool
    {
        $startTime = microtime(true);
        
        $products = $this->optimizer->getOptimizedProductList(
            'brake', // search term
            1,       // category
            1,       // make
            null,    // model
            20,      // limit
            0        // offset
        );
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($products)) {
            return "Optimized product listing returned non-array";
        }
        
        // Performance threshold: should complete within 100ms
        if ($duration > 100) {
            return "Optimized product listing too slow: {$duration}ms";
        }
        
        return true;
    }
    
    /**
     * Test optimized customer aging
     */
    private function testOptimizedCustomerAging(): bool
    {
        // First, get a customer ID for testing
        $pdo = DB::conn();
        $stmt = $pdo->query("SELECT id FROM customers LIMIT 1");
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            return "No customers available for aging test";
        }
        
        $startTime = microtime(true);
        
        $aging = $this->optimizer->getOptimizedCustomerAging((int)$customer['id'], 90);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!isset($aging['invoices']) || !isset($aging['summary'])) {
            return "Optimized customer aging missing required fields";
        }
        
        if ($duration > 50) {
            return "Optimized customer aging too slow: {$duration}ms";
        }
        
        return true;
    }
    
    /**
     * Test optimized inventory lookup
     */
    private function testOptimizedInventoryLookup(): bool
    {
        $startTime = microtime(true);
        
        $inventory = $this->optimizer->getOptimizedInventoryLookup(
            1,     // warehouse ID
            null,  // product ID
            false, // low stock only
            5      // threshold
        );
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($inventory)) {
            return "Optimized inventory lookup returned non-array";
        }
        
        if ($duration > 50) {
            return "Optimized inventory lookup too slow: {$duration}ms";
        }
        
        return true;
    }
    
    /**
     * Test batch load operations
     */
    private function testBatchLoadOperations(): bool
    {
        // Get some invoice IDs for testing
        $pdo = DB::conn();
        $stmt = $pdo->query("SELECT id FROM invoices LIMIT 5");
        $invoices = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($invoices)) {
            return "No invoices available for batch load test";
        }
        
        $startTime = microtime(true);
        
        $details = $this->optimizer->batchLoadInvoiceDetails($invoices);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($details)) {
            return "Batch load returned non-array";
        }
        
        // Should be faster than individual loads
        if ($duration > count($invoices) * 10) {
            return "Batch load not performant enough: {$duration}ms for " . count($invoices) . " invoices";
        }
        
        return true;
    }
    
    /**
     * Test product model optimizations
     */
    private function testProductModelOptimizations(): bool
    {
        // Test optimized search
        $startTime = microtime(true);
        $searchResults = Product::search('brake', 10);
        $searchDuration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($searchResults)) {
            return "Product search returned non-array";
        }
        
        if ($searchDuration > 50) {
            return "Product search too slow: {$searchDuration}ms";
        }
        
        // Test low stock
        $lowStock = Product::getLowStock(5);
        if (!is_array($lowStock)) {
            return "Low stock query returned non-array";
        }
        
        return true;
    }
    
    /**
     * Test invoice model optimizations
     */
    private function testInvoiceModelOptimizations(): bool
    {
        $startTime = microtime(true);
        $invoices = Invoice::all(10, 0, 'unpaid');
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($invoices)) {
            return "Invoice::all returned non-array";
        }
        
        if ($duration > 100) {
            return "Invoice::all too slow: {$duration}ms";
        }
        
        return true;
    }
    
    /**
     * Test customer model optimizations
     */
    private function testCustomerModelOptimizations(): bool
    {
        $startTime = microtime(true);
        $customers = Customer::search('test', 5);
        $duration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($customers)) {
            return "Customer search returned non-array";
        }
        
        if ($duration > 50) {
            return "Customer search too slow: {$duration}ms";
        }
        
        return true;
    }
    
    /**
     * Test smart query cache
     */
    private function testSmartQueryCache(): bool
    {
        $testQuery = "SELECT id, name FROM categories ORDER BY name LIMIT 10";
        
        // First call - should cache
        $startTime = microtime(true);
        $result1 = $this->smartCache->query($testQuery, [], ['tags' => ['reference_data']]);
        $firstCallDuration = (microtime(true) - $startTime) * 1000;
        
        // Second call - should be cached
        $startTime = microtime(true);
        $result2 = $this->smartCache->query($testQuery, [], ['tags' => ['reference_data']]);
        $secondCallDuration = (microtime(true) - $startTime) * 1000;
        
        if (!is_array($result1) || !is_array($result2)) {
            return "Smart cache query returned non-array";
        }
        
        if ($result1 !== $result2) {
            return "Smart cache returned different results";
        }
        
        // Second call should be significantly faster
        if ($secondCallDuration >= $firstCallDuration) {
            return "Smart cache not providing performance benefit: {$firstCallDuration}ms vs {$secondCallDuration}ms";
        }
        
        return true;
    }
    
    /**
     * Test cache invalidation
     */
    private function testCacheInvalidation(): bool
    {
        // Cache a query
        $this->smartCache->query(
            "SELECT id, name FROM categories ORDER BY name LIMIT 5", 
            [], 
            ['tags' => ['reference_data']]
        );
        
        // Invalidate by table
        $invalidatedCount = $this->smartCache->invalidateByTable('categories');
        
        if ($invalidatedCount < 0) {
            return "Invalid invalidation count: {$invalidatedCount}";
        }
        
        return true;
    }
    
    /**
     * Test cache performance
     */
    private function testCachePerformance(): bool
    {
        $stats = $this->smartCache->getStatistics();
        
        if (!isset($stats['redis_stats']) || !isset($stats['file_cache_stats'])) {
            return "Cache statistics missing required fields";
        }
        
        return true;
    }
    
    /**
     * Test preload operations
     */
    private function testPreloadOperations(): bool
    {
        $preloadResults = $this->smartCache->preloadFrequentQueries();
        
        if (!is_array($preloadResults) || empty($preloadResults)) {
            return "Preload operations returned no results";
        }
        
        foreach ($preloadResults as $queryName => $result) {
            if (!isset($result['status'])) {
                return "Preload result missing status for {$queryName}";
            }
        }
        
        return true;
    }
    
    /**
     * Test query performance benchmarks
     */
    private function testQueryPerformanceBenchmarks(): bool
    {
        $benchmarkQueries = [
            'simple_select' => "SELECT id, name FROM customers LIMIT 10",
            'join_query' => "SELECT p.name, c.name as category FROM products p LEFT JOIN categories c ON c.id = p.category_id LIMIT 10",
            'aggregate_query' => "SELECT COUNT(*) as count, status FROM invoices GROUP BY status"
        ];
        
        foreach ($benchmarkQueries as $queryName => $sql) {
            $startTime = microtime(true);
            
            $pdo = DB::conn();
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            // All queries should complete within reasonable time
            if ($duration > 200) {
                return "Benchmark query {$queryName} too slow: {$duration}ms";
            }
        }
        
        return true;
    }
    
    /**
     * Test concurrent query performance
     */
    private function testConcurrentQueryPerformance(): bool
    {
        // Simulate concurrent queries
        $queries = [];
        $startTime = microtime(true);
        
        for ($i = 0; $i < 5; $i++) {
            $queries[] = $this->optimizer->getOptimizedProductList(null, null, null, null, 20, $i * 20);
        }
        
        $totalDuration = (microtime(true) - $startTime) * 1000;
        
        // All queries should complete within reasonable total time
        if ($totalDuration > 500) {
            return "Concurrent queries too slow: {$totalDuration}ms";
        }
        
        return true;
    }
    
    /**
     * Test memory usage optimization
     */
    private function testMemoryUsageOptimization(): bool
    {
        $initialMemory = memory_get_usage(true);
        
        // Execute several operations
        Product::all('test', 1, 1, 1, 50);
        Invoice::all(50);
        Customer::search('test', 20);
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 10MB)
        if ($memoryIncrease > 10 * 1024 * 1024) {
            return "Excessive memory usage: " . $this->formatBytes($memoryIncrease);
        }
        
        return true;
    }
    
    /**
     * Test scaling performance
     */
    private function testScalingPerformance(): bool
    {
        // Test with increasing data sizes
        $limits = [10, 50, 100];
        $previousDuration = 0;
        
        foreach ($limits as $limit) {
            $startTime = microtime(true);
            Product::all(null, null, null, null, $limit);
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Performance should scale reasonably (not exponentially)
            if ($previousDuration > 0 && $duration > $previousDuration * 15) {
                return "Poor scaling performance: {$limit} limit took {$duration}ms vs previous {$previousDuration}ms";
            }
            
            $previousDuration = $duration;
        }
        
        return true;
    }
    
    /**
     * Test end-to-end optimizations
     */
    private function testEndToEndOptimizations(): bool
    {
        $startTime = microtime(true);
        
        // Simulate a typical workflow
        $categories = $this->optimizer->getOptimizedReferenceData('categories');
        $products = $this->optimizer->getOptimizedProductList(null, 1, null, null, 10);
        $search = $this->optimizer->performOptimizedSearch('test', ['products', 'customers']);
        
        $totalDuration = (microtime(true) - $startTime) * 1000;
        
        if ($totalDuration > 200) {
            return "End-to-end optimization too slow: {$totalDuration}ms";
        }
        
        return true;
    }
    
    /**
     * Test real world scenarios
     */
    private function testRealWorldScenarios(): bool
    {
        // Test dashboard-like queries
        $startTime = microtime(true);
        
        // Get recent invoices
        $recentInvoices = Invoice::all(10, 0, 'unpaid');
        
        // Get low stock products
        $lowStock = Product::getLowStock(5);
        
        // Get customers with outstanding balances
        $outstandingCustomers = Customer::getWithOutstandingBalances(100);
        
        $totalDuration = (microtime(true) - $startTime) * 1000;
        
        if ($totalDuration > 300) {
            return "Real world scenario too slow: {$totalDuration}ms";
        }
        
        return true;
    }
    
    /**
     * Test error handling optimizations
     */
    private function testErrorHandlingOptimizations(): bool
    {
        try {
            // Test with invalid parameters
            $result = $this->optimizer->getOptimizedProductList(null, -1, null, null, -5);
            
            if (!is_array($result)) {
                return "Error handling didn't return array for invalid parameters";
            }
            
        } catch (Exception $e) {
            // Exceptions should be handled gracefully
            if (strpos($e->getMessage(), 'optimization') === false) {
                return "Unexpected error during optimization: " . $e->getMessage();
            }
        }
        
        return true;
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateReport(): array
    {
        $totalTests = $this->passed + $this->failed;
        $successRate = $totalTests > 0 ? round(($this->passed / $totalTests) * 100, 2) : 0;
        
        // Calculate performance statistics
        $durations = array_column($this->results, 'duration');
        $avgDuration = count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
        $maxDuration = count($durations) > 0 ? max($durations) : 0;
        
        return [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $this->passed,
                'failed' => $this->failed,
                'success_rate' => $successRate . '%',
                'avg_duration_ms' => round($avgDuration, 2),
                'max_duration_ms' => $maxDuration,
                'execution_time' => date('Y-m-d H:i:s')
            ],
            'results' => $this->results,
            'performance_analysis' => $this->analyzePerformance(),
            'optimization_recommendations' => $this->generateOptimizationRecommendations()
        ];
    }
    
    /**
     * Analyze performance from test results
     */
    private function analyzePerformance(): array
    {
        $slowTests = array_filter($this->results, fn($r) => $r['duration'] > 100);
        $fastTests = array_filter($this->results, fn($r) => $r['duration'] < 10);
        
        return [
            'slow_tests' => count($slowTests),
            'fast_tests' => count($fastTests),
            'performance_rating' => $this->calculatePerformanceRating(),
            'bottlenecks' => array_map(fn($t) => $t['test'], array_slice($slowTests, 0, 5))
        ];
    }
    
    /**
     * Calculate overall performance rating
     */
    private function calculatePerformanceRating(): string
    {
        $durations = array_column($this->results, 'duration');
        $avgDuration = count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
        
        if ($avgDuration < 10) return 'excellent';
        if ($avgDuration < 25) return 'good';
        if ($avgDuration < 50) return 'fair';
        if ($avgDuration < 100) return 'poor';
        return 'critical';
    }
    
    /**
     * Generate optimization recommendations
     */
    private function generateOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        $slowTests = array_filter($this->results, fn($r) => $r['duration'] > 100);
        if (!empty($slowTests)) {
            $recommendations[] = 'Address slow test cases: ' . implode(', ', array_map(fn($t) => $t['test'], $slowTests));
        }
        
        $failedTests = array_filter($this->results, fn($r) => $r['status'] !== 'PASS');
        if (!empty($failedTests)) {
            $recommendations[] = 'Fix failing tests to ensure optimization reliability';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'All optimizations are performing well';
        }
        
        return $recommendations;
    }
    
    /**
     * Format bytes for human reading
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Log message
     */
    private function log(string $message): void
    {
        echo "[" . date('H:i:s') . "] {$message}\n";
    }
}

// CLI interface
if (PHP_SAPI === 'cli') {
    $options = getopt('hv', ['help', 'verbose']);
    
    if (isset($options['h']) || isset($options['help'])) {
        echo "Query Optimization Test Suite\n\n";
        echo "Usage: php query_optimization_test.php [options]\n\n";
        echo "Options:\n";
        echo "  -h, --help     Show this help message\n";
        echo "  -v, --verbose  Show detailed test output\n\n";
        exit(0);
    }
    
    try {
        $testSuite = new QueryOptimizationTestSuite();
        $report = $testSuite->runAllTests();
        
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "QUERY OPTIMIZATION TEST RESULTS\n";
        echo str_repeat('=', 70) . "\n\n";
        
        echo "Summary:\n";
        echo "  Total Tests: {$report['summary']['total_tests']}\n";
        echo "  Passed: {$report['summary']['passed']}\n";
        echo "  Failed: {$report['summary']['failed']}\n";
        echo "  Success Rate: {$report['summary']['success_rate']}\n";
        echo "  Average Duration: {$report['summary']['avg_duration_ms']}ms\n";
        echo "  Max Duration: {$report['summary']['max_duration_ms']}ms\n";
        echo "  Execution Time: {$report['summary']['execution_time']}\n\n";
        
        echo "Performance Analysis:\n";
        echo "  Performance Rating: {$report['performance_analysis']['performance_rating']}\n";
        echo "  Slow Tests: {$report['performance_analysis']['slow_tests']}\n";
        echo "  Fast Tests: {$report['performance_analysis']['fast_tests']}\n\n";
        
        if (isset($options['v']) || isset($options['verbose'])) {
            echo "Detailed Results:\n";
            foreach ($report['results'] as $result) {
                $status = str_pad($result['status'], 6);
                $test = str_pad($result['test'], 45);
                $duration = str_pad($result['duration'] . 'ms', 10);
                
                echo "  {$status} {$test} {$duration}";
                
                if (!empty($result['message'])) {
                    echo " - {$result['message']}";
                }
                
                echo "\n";
            }
            echo "\n";
        }
        
        if (!empty($report['optimization_recommendations'])) {
            echo "Optimization Recommendations:\n";
            foreach ($report['optimization_recommendations'] as $i => $recommendation) {
                echo "  " . ($i + 1) . ". {$recommendation}\n";
            }
            echo "\n";
        }
        
        // Exit with appropriate code
        exit($report['summary']['failed'] > 0 ? 1 : 0);
        
    } catch (Exception $e) {
        echo "Test suite failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}