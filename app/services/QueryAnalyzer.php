<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use PDO;
use Exception;

/**
 * Query Analyzer Service
 * 
 * Analyzes database query performance, identifies optimization opportunities,
 * and provides recommendations for query improvements and index creation.
 */
class QueryAnalyzer
{
    private static ?QueryAnalyzer $instance = null;
    private array $queryLog = [];
    private array $slowQueries = [];
    private array $recommendations = [];
    
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
     * Analyze query performance and generate recommendations
     */
    public function analyzeCurrentQueries(): array
    {
        Logger::info('Starting comprehensive query performance analysis');
        
        $results = [
            'analysis_time' => date('Y-m-d H:i:s'),
            'slow_queries' => [],
            'index_recommendations' => [],
            'join_optimizations' => [],
            'query_patterns' => [],
            'performance_metrics' => []
        ];
        
        try {
            // Analyze common query patterns
            $results['query_patterns'] = $this->analyzeQueryPatterns();
            
            // Check for missing indexes
            $results['index_recommendations'] = $this->analyzeIndexRequirements();
            
            // Analyze JOIN performance
            $results['join_optimizations'] = $this->analyzeJoinPatterns();
            
            // Get current slow queries
            $results['slow_queries'] = $this->getSlowQueries();
            
            // Performance metrics
            $results['performance_metrics'] = $this->getPerformanceMetrics();
            
            Logger::info('Query analysis completed successfully');
            
        } catch (Exception $e) {
            Logger::error('Query analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Analyze common query patterns in the application
     */
    private function analyzeQueryPatterns(): array
    {
        $patterns = [];
        
        try {
            // Pattern 1: Product listing with filters
            $patterns['product_filtering'] = $this->analyzeProductFilteringPattern();
            
            // Pattern 2: Customer aging reports
            $patterns['customer_aging'] = $this->analyzeCustomerAgingPattern();
            
            // Pattern 3: Inventory lookups
            $patterns['inventory_lookups'] = $this->analyzeInventoryLookupPattern();
            
            // Pattern 4: Invoice operations
            $patterns['invoice_operations'] = $this->analyzeInvoicePattern();
            
            // Pattern 5: Reference data loading
            $patterns['reference_data'] = $this->analyzeReferenceDataPattern();
            
        } catch (Exception $e) {
            Logger::error('Query pattern analysis failed', ['error' => $e->getMessage()]);
            $patterns['error'] = $e->getMessage();
        }
        
        return $patterns;
    }
    
    /**
     * Analyze product filtering query pattern
     */
    private function analyzeProductFilteringPattern(): array
    {
        $query = "
            SELECT p.*, c.name AS category_name, mk.name AS make_name, vm.name AS model_name,
                   COALESCE(SUM(ps.qty_on_hand),0) AS on_hand,
                   COALESCE(SUM(ps.qty_reserved),0) AS reserved
            FROM products p
            LEFT JOIN categories c ON c.id=p.category_id
            LEFT JOIN makes mk ON mk.id=p.make_id
            LEFT JOIN vehicle_models vm ON vm.id=p.model_id
            LEFT JOIN product_stocks ps ON ps.product_id=p.id
            WHERE p.category_id = 1 AND p.make_id = 1 AND p.model_id = 1
            GROUP BY p.id ORDER BY p.name
        ";
        
        return $this->analyzeQueryExecution($query, 'Product Filtering');
    }
    
    /**
     * Analyze customer aging query pattern
     */
    private function analyzeCustomerAgingPattern(): array
    {
        $query = "
            SELECT i.inv_no, i.total, i.paid_amount, i.status, i.created_at, c.name as customer_name
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.customer_id = 1 
              AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
              AND i.status IN ('unpaid', 'partial')
            ORDER BY i.created_at DESC
        ";
        
        return $this->analyzeQueryExecution($query, 'Customer Aging');
    }
    
    /**
     * Analyze inventory lookup pattern
     */
    private function analyzeInventoryLookupPattern(): array
    {
        $query = "
            SELECT ps.*, p.name AS product_name, w.name AS warehouse_name
            FROM product_stocks ps
            JOIN products p ON p.id = ps.product_id
            JOIN warehouses w ON w.id = ps.warehouse_id
            WHERE ps.warehouse_id = 1 AND ps.qty_on_hand > 0
            ORDER BY p.name
        ";
        
        return $this->analyzeQueryExecution($query, 'Inventory Lookup');
    }
    
    /**
     * Analyze invoice operations pattern
     */
    private function analyzeInvoicePattern(): array
    {
        $query = "
            SELECT i.*, c.name AS customer_name,
                   COUNT(il.id) as line_count,
                   SUM(il.qty * il.price) as calculated_total
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            LEFT JOIN invoice_lines il ON il.invoice_id = i.id
            WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY i.id
            ORDER BY i.created_at DESC
        ";
        
        return $this->analyzeQueryExecution($query, 'Invoice Operations');
    }
    
    /**
     * Analyze reference data loading pattern
     */
    private function analyzeReferenceDataPattern(): array
    {
        $analyses = [];
        
        $queries = [
            'categories' => 'SELECT id, name FROM categories ORDER BY name',
            'makes' => 'SELECT id, name FROM makes ORDER BY name',
            'models' => 'SELECT id, name, make_id FROM vehicle_models ORDER BY name',
            'warehouses' => 'SELECT id, name FROM warehouses ORDER BY name'
        ];
        
        foreach ($queries as $type => $query) {
            $analyses[$type] = $this->analyzeQueryExecution($query, "Reference Data: $type");
        }
        
        return $analyses;
    }
    
    /**
     * Analyze specific query execution
     */
    private function analyzeQueryExecution(string $query, string $description): array
    {
        $pdo = DB::conn();
        
        try {
            // Get execution plan
            $explainQuery = "EXPLAIN FORMAT=JSON " . $query;
            $stmt = $pdo->prepare($explainQuery);
            $stmt->execute();
            $explainResult = $stmt->fetchColumn();
            
            // Time the query execution
            $startTime = microtime(true);
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms
            
            // Parse execution plan
            $plan = json_decode($explainResult, true);
            
            return [
                'description' => $description,
                'query' => trim($query),
                'execution_time_ms' => round($executionTime, 2),
                'rows_examined' => $this->extractRowsExamined($plan),
                'rows_returned' => count($results),
                'execution_plan' => $plan,
                'performance_rating' => $this->rateQueryPerformance($executionTime, $plan),
                'optimization_suggestions' => $this->generateOptimizationSuggestions($plan, $query)
            ];
            
        } catch (Exception $e) {
            return [
                'description' => $description,
                'query' => trim($query),
                'error' => $e->getMessage(),
                'performance_rating' => 'error'
            ];
        }
    }
    
    /**
     * Extract rows examined from execution plan
     */
    private function extractRowsExamined(array $plan): int
    {
        try {
            $queryBlock = $plan['query_block'] ?? [];
            $table = $queryBlock['table'] ?? $queryBlock['nested_loop'][0]['table'] ?? [];
            return (int)($table['rows_examined_per_scan'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Rate query performance
     */
    private function rateQueryPerformance(float $executionTime, array $plan): string
    {
        // Rate based on execution time and plan analysis
        if ($executionTime < 1) {
            return 'excellent';
        } elseif ($executionTime < 10) {
            return 'good';
        } elseif ($executionTime < 50) {
            return 'fair';
        } elseif ($executionTime < 100) {
            return 'poor';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Generate optimization suggestions
     */
    private function generateOptimizationSuggestions(array $plan, string $query): array
    {
        $suggestions = [];
        
        try {
            // Check for table scans
            if ($this->hasTableScan($plan)) {
                $suggestions[] = 'Consider adding indexes to avoid full table scans';
            }
            
            // Check for filesort
            if ($this->hasFilesort($plan)) {
                $suggestions[] = 'Add index on ORDER BY columns to avoid filesort';
            }
            
            // Check for temporary tables
            if ($this->hasTemporaryTable($plan)) {
                $suggestions[] = 'Optimize GROUP BY or DISTINCT to avoid temporary tables';
            }
            
            // Check JOIN efficiency
            if ($this->hasInefficientJoins($plan)) {
                $suggestions[] = 'Optimize JOINs by adding indexes on join conditions';
            }
            
            // Check for WHERE clause optimization
            if ($this->needsWhereOptimization($query)) {
                $suggestions[] = 'Add composite index for WHERE clause conditions';
            }
            
        } catch (Exception $e) {
            $suggestions[] = 'Unable to analyze query plan: ' . $e->getMessage();
        }
        
        return $suggestions;
    }
    
    /**
     * Check if query plan has table scan
     */
    private function hasTableScan(array $plan): bool
    {
        $planJson = json_encode($plan);
        return strpos($planJson, '"access_type": "ALL"') !== false;
    }
    
    /**
     * Check if query plan has filesort
     */
    private function hasFilesort(array $plan): bool
    {
        $planJson = json_encode($plan);
        return strpos($planJson, '"using_filesort": true') !== false;
    }
    
    /**
     * Check if query plan uses temporary table
     */
    private function hasTemporaryTable(array $plan): bool
    {
        $planJson = json_encode($plan);
        return strpos($planJson, '"using_temporary_table": true') !== false;
    }
    
    /**
     * Check for inefficient JOINs
     */
    private function hasInefficientJoins(array $plan): bool
    {
        $planJson = json_encode($plan);
        return strpos($planJson, '"access_type": "ALL"') !== false && 
               strpos($planJson, 'JOIN') !== false;
    }
    
    /**
     * Check if WHERE clause needs optimization
     */
    private function needsWhereOptimization(string $query): bool
    {
        // Count WHERE conditions
        $whereConditions = substr_count(strtoupper($query), ' AND ') + 
                          substr_count(strtoupper($query), ' OR ') + 1;
        return $whereConditions > 2; // Multiple conditions likely need composite index
    }
    
    /**
     * Analyze index requirements
     */
    private function analyzeIndexRequirements(): array
    {
        $recommendations = [];
        
        try {
            $pdo = DB::conn();
            
            // Get current indexes
            $stmt = $pdo->query("
                SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
            ");
            $currentIndexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Analyze missing indexes based on common query patterns
            $missingIndexes = $this->identifyMissingIndexes($currentIndexes);
            
            foreach ($missingIndexes as $index) {
                $recommendations[] = [
                    'table' => $index['table'],
                    'columns' => $index['columns'],
                    'type' => $index['type'],
                    'benefit' => $index['benefit'],
                    'sql' => $index['sql']
                ];
            }
            
        } catch (Exception $e) {
            Logger::error('Index analysis failed', ['error' => $e->getMessage()]);
            $recommendations[] = ['error' => $e->getMessage()];
        }
        
        return $recommendations;
    }
    
    /**
     * Identify missing indexes
     */
    private function identifyMissingIndexes(array $currentIndexes): array
    {
        $missing = [];
        
        // Create lookup for existing indexes
        $existingIndexes = [];
        foreach ($currentIndexes as $index) {
            $key = $index['TABLE_NAME'] . '.' . $index['INDEX_NAME'];
            $existingIndexes[$key][] = $index['COLUMN_NAME'];
        }
        
        // Check for commonly needed indexes
        $neededIndexes = [
            // Invoice date and status for aging reports
            [
                'table' => 'invoices',
                'columns' => ['customer_id', 'created_at', 'status'],
                'type' => 'composite',
                'benefit' => 'Faster customer aging calculations',
                'sql' => 'CREATE INDEX idx_invoices_customer_date_status ON invoices(customer_id, created_at, status)'
            ],
            // Product search optimization
            [
                'table' => 'products',
                'columns' => ['category_id', 'make_id', 'model_id'],
                'type' => 'composite',
                'benefit' => 'Optimized product filtering',
                'sql' => 'CREATE INDEX idx_products_category_make_model ON products(category_id, make_id, model_id)'
            ],
            // Inventory management
            [
                'table' => 'product_stocks',
                'columns' => ['warehouse_id', 'qty_on_hand'],
                'type' => 'composite',
                'benefit' => 'Faster warehouse stock lookups',
                'sql' => 'CREATE INDEX idx_product_stocks_warehouse_qty ON product_stocks(warehouse_id, qty_on_hand)'
            ],
            // Invoice lines for performance
            [
                'table' => 'invoice_lines',
                'columns' => ['invoice_id', 'product_id'],
                'type' => 'composite',
                'benefit' => 'Faster invoice line item queries',
                'sql' => 'CREATE INDEX idx_invoice_lines_invoice_product ON invoice_lines(invoice_id, product_id)'
            ]
        ];
        
        foreach ($neededIndexes as $needed) {
            if (!$this->indexExists($needed, $existingIndexes)) {
                $missing[] = $needed;
            }
        }
        
        return $missing;
    }
    
    /**
     * Check if index already exists
     */
    private function indexExists(array $needed, array $existing): bool
    {
        foreach ($existing as $indexKey => $columns) {
            if (strpos($indexKey, $needed['table'] . '.') === 0) {
                // Check if columns match (order matters for composite indexes)
                if ($columns === $needed['columns']) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Analyze JOIN patterns for optimization opportunities
     */
    private function analyzeJoinPatterns(): array
    {
        $optimizations = [];
        
        try {
            // Analyze common JOIN patterns and their performance
            $joinQueries = [
                'product_with_references' => "
                    SELECT p.*, c.name AS category_name, mk.name AS make_name
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN makes mk ON mk.id = p.make_id
                    LIMIT 100
                ",
                'invoice_with_customer' => "
                    SELECT i.*, c.name AS customer_name
                    FROM invoices i
                    LEFT JOIN customers c ON c.id = i.customer_id
                    LIMIT 100
                ",
                'product_stocks_with_warehouse' => "
                    SELECT ps.*, p.name AS product_name, w.name AS warehouse_name
                    FROM product_stocks ps
                    JOIN products p ON p.id = ps.product_id
                    JOIN warehouses w ON w.id = ps.warehouse_id
                    LIMIT 100
                "
            ];
            
            foreach ($joinQueries as $name => $query) {
                $analysis = $this->analyzeQueryExecution($query, "JOIN: $name");
                
                if ($analysis['performance_rating'] === 'poor' || $analysis['performance_rating'] === 'critical') {
                    $optimizations[] = [
                        'query_type' => $name,
                        'issue' => 'Poor JOIN performance',
                        'current_time_ms' => $analysis['execution_time_ms'],
                        'suggestions' => $analysis['optimization_suggestions']
                    ];
                }
            }
            
        } catch (Exception $e) {
            Logger::error('JOIN analysis failed', ['error' => $e->getMessage()]);
            $optimizations[] = ['error' => $e->getMessage()];
        }
        
        return $optimizations;
    }
    
    /**
     * Get slow queries from MySQL slow log
     */
    private function getSlowQueries(): array
    {
        $slowQueries = [];
        
        try {
            $pdo = DB::conn();
            
            // Check if slow query log is enabled
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'slow_query_log'");
            $slowLogEnabled = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($slowLogEnabled && $slowLogEnabled['Value'] === 'ON') {
                // Get slow query information
                $stmt = $pdo->query("
                    SELECT EVENT_NAME, COUNT_STAR, SUM_TIMER_WAIT/1000000000000 as TOTAL_TIME_SEC,
                           AVG_TIMER_WAIT/1000000000000 as AVG_TIME_SEC
                    FROM performance_schema.events_statements_summary_by_digest
                    WHERE AVG_TIMER_WAIT/1000000000000 > 0.1
                    ORDER BY AVG_TIMER_WAIT DESC
                    LIMIT 10
                ");
                $slowQueries = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                $slowQueries[] = [
                    'note' => 'Slow query log is not enabled. Enable with: SET GLOBAL slow_query_log = 1;'
                ];
            }
            
        } catch (Exception $e) {
            Logger::error('Slow query analysis failed', ['error' => $e->getMessage()]);
            $slowQueries[] = ['error' => $e->getMessage()];
        }
        
        return $slowQueries;
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $metrics = [];
        
        try {
            $pdo = DB::conn();
            
            // Query cache statistics
            $stmt = $pdo->query("SHOW STATUS LIKE 'Qcache%'");
            $qcacheStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Table statistics
            $stmt = $pdo->query("
                SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH,
                       ROUND(DATA_LENGTH/1024/1024, 2) as DATA_MB,
                       ROUND(INDEX_LENGTH/1024/1024, 2) as INDEX_MB
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY DATA_LENGTH DESC
                LIMIT 10
            ");
            $tableStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Connection statistics
            $stmt = $pdo->query("SHOW STATUS LIKE 'Connections'");
            $connectionStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $metrics = [
                'query_cache' => $qcacheStats,
                'table_statistics' => $tableStats,
                'connections' => $connectionStats,
                'analysis_time' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            Logger::error('Performance metrics collection failed', ['error' => $e->getMessage()]);
            $metrics['error'] = $e->getMessage();
        }
        
        return $metrics;
    }
    
    /**
     * Generate comprehensive optimization report
     */
    public function generateOptimizationReport(): array
    {
        Logger::info('Generating comprehensive query optimization report');
        
        $analysis = $this->analyzeCurrentQueries();
        
        $report = [
            'report_date' => date('Y-m-d H:i:s'),
            'executive_summary' => $this->generateExecutiveSummary($analysis),
            'detailed_analysis' => $analysis,
            'priority_recommendations' => $this->prioritizeRecommendations($analysis),
            'implementation_plan' => $this->generateImplementationPlan($analysis)
        ];
        
        Logger::info('Query optimization report generated successfully');
        
        return $report;
    }
    
    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(array $analysis): array
    {
        $summary = [
            'total_patterns_analyzed' => count($analysis['query_patterns'] ?? []),
            'slow_queries_identified' => count($analysis['slow_queries'] ?? []),
            'index_recommendations' => count($analysis['index_recommendations'] ?? []),
            'join_optimizations' => count($analysis['join_optimizations'] ?? [])
        ];
        
        // Calculate overall performance rating
        $ratings = [];
        foreach ($analysis['query_patterns'] as $pattern) {
            if (isset($pattern['performance_rating'])) {
                $ratings[] = $pattern['performance_rating'];
            }
        }
        
        $summary['overall_performance'] = $this->calculateOverallRating($ratings);
        
        return $summary;
    }
    
    /**
     * Calculate overall performance rating
     */
    private function calculateOverallRating(array $ratings): string
    {
        if (empty($ratings)) {
            return 'unknown';
        }
        
        $scores = [
            'excellent' => 5,
            'good' => 4,
            'fair' => 3,
            'poor' => 2,
            'critical' => 1
        ];
        
        $totalScore = 0;
        foreach ($ratings as $rating) {
            $totalScore += $scores[$rating] ?? 0;
        }
        
        $averageScore = $totalScore / count($ratings);
        
        if ($averageScore >= 4.5) return 'excellent';
        if ($averageScore >= 3.5) return 'good';
        if ($averageScore >= 2.5) return 'fair';
        if ($averageScore >= 1.5) return 'poor';
        return 'critical';
    }
    
    /**
     * Prioritize recommendations
     */
    private function prioritizeRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // High priority: Critical performance issues
        foreach ($analysis['query_patterns'] as $name => $pattern) {
            if (isset($pattern['performance_rating']) && 
                in_array($pattern['performance_rating'], ['poor', 'critical'])) {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'type' => 'Query Performance',
                    'description' => "Optimize {$name} query (rating: {$pattern['performance_rating']})",
                    'impact' => 'Direct user experience improvement',
                    'effort' => 'Medium'
                ];
            }
        }
        
        // Medium priority: Index recommendations
        foreach ($analysis['index_recommendations'] as $index) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'type' => 'Index Creation',
                'description' => "Create index on {$index['table']}: " . implode(', ', $index['columns']),
                'impact' => $index['benefit'],
                'effort' => 'Low'
            ];
        }
        
        // Lower priority: JOIN optimizations
        foreach ($analysis['join_optimizations'] as $join) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'type' => 'JOIN Optimization',
                'description' => "Optimize {$join['query_type']} JOIN performance",
                'impact' => 'Query performance improvement',
                'effort' => 'Medium'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Generate implementation plan
     */
    private function generateImplementationPlan(array $analysis): array
    {
        return [
            'phase_1' => [
                'name' => 'Critical Index Creation',
                'duration' => '1-2 hours',
                'tasks' => array_slice($analysis['index_recommendations'] ?? [], 0, 3)
            ],
            'phase_2' => [
                'name' => 'Query Pattern Optimization',
                'duration' => '4-6 hours',
                'tasks' => [
                    'Optimize slow queries identified in analysis',
                    'Implement efficient JOIN strategies',
                    'Add query result caching where appropriate'
                ]
            ],
            'phase_3' => [
                'name' => 'Performance Testing and Validation',
                'duration' => '2-3 hours',
                'tasks' => [
                    'Benchmark optimized queries',
                    'Validate performance improvements',
                    'Create performance monitoring'
                ]
            ]
        ];
    }
}