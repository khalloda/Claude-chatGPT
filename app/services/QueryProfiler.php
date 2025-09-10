<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

/**
 * Query Profiling Service for N+1 Detection
 * 
 * Monitors and analyzes database queries to detect N+1 patterns
 * and provide performance insights.
 */
final class QueryProfiler
{
    private static bool $enabled = false;
    private static array $queries = [];
    private static array $queryPatterns = [];
    private static float $startTime = 0;
    private static array $thresholds = [
        'slow_query_time' => 0.1, // 100ms
        'n_plus_one_threshold' => 5, // 5+ similar queries
        'total_queries_threshold' => 20 // 20+ queries per request
    ];
    
    /**
     * Start profiling
     */
    public static function start(): void
    {
        if (self::$enabled) {
            return;
        }
        
        self::$enabled = true;
        self::$queries = [];
        self::$queryPatterns = [];
        self::$startTime = microtime(true);
        
        // Hook into PDO to track queries
        self::setupQueryHook();
    }
    
    /**
     * Stop profiling and return results
     */
    public static function stop(): array
    {
        if (!self::$enabled) {
            return [];
        }
        
        self::$enabled = false;
        $totalTime = microtime(true) - self::$startTime;
        
        $analysis = self::analyzeQueries();
        $analysis['total_time'] = $totalTime;
        $analysis['total_queries'] = count(self::$queries);
        
        return $analysis;
    }
    
    /**
     * Record a query execution
     */
    public static function recordQuery(string $sql, array $params, float $executionTime, ?string $trace = null): void
    {
        if (!self::$enabled) {
            return;
        }
        
        $normalizedSql = self::normalizeQuery($sql);
        $pattern = self::getQueryPattern($normalizedSql);
        
        $queryRecord = [
            'sql' => $sql,
            'normalized_sql' => $normalizedSql,
            'pattern' => $pattern,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true),
            'trace' => $trace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ];
        
        self::$queries[] = $queryRecord;
        
        // Track pattern frequency
        if (!isset(self::$queryPatterns[$pattern])) {
            self::$queryPatterns[$pattern] = ['count' => 0, 'total_time' => 0, 'queries' => []];
        }
        self::$queryPatterns[$pattern]['count']++;
        self::$queryPatterns[$pattern]['total_time'] += $executionTime;
        self::$queryPatterns[$pattern]['queries'][] = count(self::$queries) - 1;
    }
    
    /**
     * Analyze queries for N+1 patterns and other issues
     */
    public static function analyzeQueries(): array
    {
        $analysis = [
            'n_plus_one_detected' => [],
            'slow_queries' => [],
            'duplicate_queries' => [],
            'query_patterns' => [],
            'recommendations' => [],
            'summary' => [
                'total_queries' => count(self::$queries),
                'total_time' => 0,
                'avg_time' => 0,
                'n_plus_one_count' => 0,
                'slow_query_count' => 0
            ]
        ];
        
        $totalTime = 0;
        
        // Analyze individual queries
        foreach (self::$queries as $i => $query) {
            $totalTime += $query['execution_time'];
            
            // Check for slow queries
            if ($query['execution_time'] > self::$thresholds['slow_query_time']) {
                $analysis['slow_queries'][] = [
                    'index' => $i,
                    'sql' => $query['sql'],
                    'execution_time' => $query['execution_time'],
                    'trace' => self::formatTrace($query['trace'])
                ];
            }
        }
        
        // Analyze patterns for N+1 queries
        foreach (self::$queryPatterns as $pattern => $data) {
            if ($data['count'] >= self::$thresholds['n_plus_one_threshold']) {
                $analysis['n_plus_one_detected'][] = [
                    'pattern' => $pattern,
                    'count' => $data['count'],
                    'total_time' => $data['total_time'],
                    'avg_time' => $data['total_time'] / $data['count'],
                    'queries' => array_slice($data['queries'], 0, 5), // First 5 occurrences
                    'recommendation' => self::generateRecommendation($pattern)
                ];
                $analysis['summary']['n_plus_one_count']++;
            }
            
            $analysis['query_patterns'][] = [
                'pattern' => $pattern,
                'count' => $data['count'],
                'total_time' => $data['total_time'],
                'avg_time' => $data['total_time'] / $data['count']
            ];
        }
        
        // Sort patterns by frequency
        usort($analysis['query_patterns'], fn($a, $b) => $b['count'] <=> $a['count']);
        
        $analysis['summary']['total_time'] = $totalTime;
        $analysis['summary']['avg_time'] = count(self::$queries) > 0 ? $totalTime / count(self::$queries) : 0;
        $analysis['summary']['slow_query_count'] = count($analysis['slow_queries']);
        
        // Generate general recommendations
        $analysis['recommendations'] = self::generateRecommendations($analysis);
        
        return $analysis;
    }
    
    /**
     * Get current profiling statistics
     */
    public static function getStats(): array
    {
        return [
            'enabled' => self::$enabled,
            'queries_recorded' => count(self::$queries),
            'unique_patterns' => count(self::$queryPatterns),
            'elapsed_time' => self::$enabled ? microtime(true) - self::$startTime : 0,
            'thresholds' => self::$thresholds
        ];
    }
    
    /**
     * Set profiling thresholds
     */
    public static function setThresholds(array $thresholds): void
    {
        self::$thresholds = array_merge(self::$thresholds, $thresholds);
    }
    
    /**
     * Generate profiling report
     */
    public static function generateReport(): string
    {
        if (!self::$enabled && empty(self::$queries)) {
            return "No profiling data available. Call QueryProfiler::start() first.";
        }
        
        $analysis = self::analyzeQueries();
        
        $report = "=== Database Query Profiling Report ===\n\n";
        $report .= "Summary:\n";
        $report .= "- Total Queries: {$analysis['summary']['total_queries']}\n";
        $report .= "- Total Time: " . number_format($analysis['summary']['total_time'] * 1000, 2) . "ms\n";
        $report .= "- Average Time: " . number_format($analysis['summary']['avg_time'] * 1000, 2) . "ms\n";
        $report .= "- Slow Queries: {$analysis['summary']['slow_query_count']}\n";
        $report .= "- N+1 Patterns: {$analysis['summary']['n_plus_one_count']}\n\n";
        
        if (!empty($analysis['n_plus_one_detected'])) {
            $report .= "=== N+1 Query Patterns Detected ===\n";
            foreach ($analysis['n_plus_one_detected'] as $nplus1) {
                $report .= "Pattern: {$nplus1['pattern']}\n";
                $report .= "Count: {$nplus1['count']}\n";
                $report .= "Total Time: " . number_format($nplus1['total_time'] * 1000, 2) . "ms\n";
                $report .= "Recommendation: {$nplus1['recommendation']}\n\n";
            }
        }
        
        if (!empty($analysis['slow_queries'])) {
            $report .= "=== Slow Queries ===\n";
            foreach (array_slice($analysis['slow_queries'], 0, 5) as $slow) {
                $report .= "Time: " . number_format($slow['execution_time'] * 1000, 2) . "ms\n";
                $report .= "SQL: {$slow['sql']}\n";
                $report .= "Location: {$slow['trace']}\n\n";
            }
        }
        
        if (!empty($analysis['recommendations'])) {
            $report .= "=== Recommendations ===\n";
            foreach ($analysis['recommendations'] as $rec) {
                $report .= "- {$rec}\n";
            }
        }
        
        return $report;
    }
    
    /**
     * Test for N+1 queries in specific scenarios
     */
    public static function testScenario(string $name, callable $scenario): array
    {
        self::start();
        
        $startTime = microtime(true);
        $result = $scenario();
        $endTime = microtime(true);
        
        $analysis = self::stop();
        $analysis['scenario_name'] = $name;
        $analysis['scenario_time'] = $endTime - $startTime;
        $analysis['scenario_result'] = $result;
        
        return $analysis;
    }
    
    /**
     * Normalize query for pattern matching
     */
    private static function normalizeQuery(string $sql): string
    {
        // Remove extra whitespace and normalize
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // Replace parameter placeholders with ?
        $sql = preg_replace('/\$\d+/', '?', $sql);
        
        // Replace numeric values with placeholders
        $sql = preg_replace('/\b\d+\b/', '?', $sql);
        
        // Replace quoted strings with placeholders
        $sql = preg_replace("/'[^']*'/", '?', $sql);
        $sql = preg_replace('/"[^"]*"/', '?', $sql);
        
        return strtolower($sql);
    }
    
    /**
     * Get query pattern for grouping
     */
    private static function getQueryPattern(string $sql): string
    {
        // Extract the basic query structure
        if (preg_match('/^(select|insert|update|delete)\s+.*?\s+from\s+(\w+)/i', $sql, $matches)) {
            return strtolower($matches[1]) . '_' . strtolower($matches[2]);
        }
        
        if (preg_match('/^(insert|update|delete)\s+(\w+)/i', $sql, $matches)) {
            return strtolower($matches[1]) . '_' . strtolower($matches[2]);
        }
        
        return 'unknown';
    }
    
    /**
     * Generate recommendation for query pattern
     */
    private static function generateRecommendation(string $pattern): string
    {
        $recommendations = [
            'select_products' => 'Consider using eager loading with JOINs or preloading related data',
            'select_customers' => 'Use batch loading for customer data or implement caching',
            'select_invoices' => 'Implement invoice data preloading or use optimized queries',
            'select_categories' => 'Cache category data using ReferenceDataCache service',
            'select_makes' => 'Cache make data using ReferenceDataCache service',
            'select_warehouses' => 'Cache warehouse data using ReferenceDataCache service',
            'select_product_stocks' => 'Batch load stock data for multiple products at once'
        ];
        
        return $recommendations[$pattern] ?? 'Consider optimizing this query pattern with eager loading or caching';
    }
    
    /**
     * Generate general recommendations
     */
    private static function generateRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['summary']['total_queries'] > self::$thresholds['total_queries_threshold']) {
            $recommendations[] = "High query count detected. Consider implementing query caching or batch loading.";
        }
        
        if ($analysis['summary']['n_plus_one_count'] > 0) {
            $recommendations[] = "N+1 query patterns detected. Implement eager loading strategies.";
        }
        
        if ($analysis['summary']['slow_query_count'] > 0) {
            $recommendations[] = "Slow queries detected. Add database indexes or optimize query structure.";
        }
        
        if ($analysis['summary']['avg_time'] > 0.05) {
            $recommendations[] = "Average query time is high. Consider query optimization and caching.";
        }
        
        return $recommendations;
    }
    
    /**
     * Format stack trace for display
     */
    private static function formatTrace(array $trace): string
    {
        foreach ($trace as $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $file = basename($frame['file']);
                return "{$file}:{$frame['line']}";
            }
        }
        return 'Unknown location';
    }
    
    /**
     * Setup query hooks (simplified version)
     */
    private static function setupQueryHook(): void
    {
        // This would require more complex PDO wrapper implementation
        // For now, we'll rely on manual recording in our models
    }
}