<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use PDO;
use Exception;

/**
 * Query Optimizer Service
 * 
 * Provides optimized query patterns, efficient JOIN strategies,
 * and performance-enhanced database operations.
 */
class QueryOptimizer
{
    private static ?QueryOptimizer $instance = null;
    private QueryCache $queryCache;
    private ReferenceDataCache $referenceCache;
    
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
        $this->queryCache = QueryCache::getInstance();
        $this->referenceCache = ReferenceDataCache::getInstance();
    }
    
    /**
     * Optimized product listing with advanced filtering and JOIN optimization
     */
    public function getOptimizedProductList(
        ?string $search = null, 
        ?int $categoryId = null, 
        ?int $makeId = null, 
        ?int $modelId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        // Create cache key for this specific query combination
        $cacheKey = 'products:optimized:' . md5(serialize(func_get_args()));
        
        // Try cache first
        $cached = $this->queryCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $pdo = DB::conn();
        
        // Build optimized query with proper index hints
        $sql = "
            SELECT /*+ USE_INDEX(p, idx_products_category_make_model) */
                p.id, p.code, p.name, p.cost, p.price,
                p.category_id, p.make_id, p.model_id,
                c.name AS category_name,
                mk.name AS make_name,
                vm.name AS model_name,
                COALESCE(stock_summary.total_on_hand, 0) AS total_on_hand,
                COALESCE(stock_summary.total_reserved, 0) AS total_reserved
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN makes mk ON mk.id = p.make_id
            LEFT JOIN vehicle_models vm ON vm.id = p.model_id
            LEFT JOIN (
                SELECT 
                    product_id,
                    SUM(qty_on_hand) as total_on_hand,
                    SUM(qty_reserved) as total_reserved
                FROM product_stocks
                GROUP BY product_id
            ) stock_summary ON stock_summary.product_id = p.id
        ";
        
        // Build WHERE clause with optimal condition ordering
        $whereConditions = [];
        $params = [];
        
        // Most selective conditions first (if available)
        if ($categoryId !== null) {
            $whereConditions[] = "p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($makeId !== null) {
            $whereConditions[] = "p.make_id = ?";
            $params[] = $makeId;
        }
        
        if ($modelId !== null) {
            $whereConditions[] = "p.model_id = ?";
            $params[] = $modelId;
        }
        
        if ($search !== null && $search !== '') {
            $whereConditions[] = "(p.name LIKE ? OR p.code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Optimize ORDER BY
        $sql .= " ORDER BY p.name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $startTime = microtime(true);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log slow queries
            if ($executionTime > 50) {
                Logger::warning('Slow product query detected', [
                    'execution_time_ms' => $executionTime,
                    'params' => $params
                ]);
            }
            
            // Cache results
            $this->queryCache->set($cacheKey, $results, 300); // 5 minutes
            
            return $results;
            
        } catch (Exception $e) {
            Logger::error('Optimized product query failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Optimized customer aging calculation with single query
     */
    public function getOptimizedCustomerAging(int $customerId, int $days = 90): array
    {
        $cacheKey = "customer_aging:{$customerId}:{$days}";
        
        $cached = $this->queryCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $pdo = DB::conn();
        
        // Single optimized query for customer aging
        $sql = "
            SELECT /*+ USE_INDEX(i, idx_invoices_customer_date_status) */
                i.id,
                i.inv_no,
                i.total,
                i.paid_amount,
                i.status,
                i.created_at,
                DATEDIFF(NOW(), i.created_at) as days_outstanding,
                CASE 
                    WHEN DATEDIFF(NOW(), i.created_at) <= 30 THEN '0-30'
                    WHEN DATEDIFF(NOW(), i.created_at) <= 60 THEN '31-60'
                    WHEN DATEDIFF(NOW(), i.created_at) <= 90 THEN '61-90'
                    ELSE '90+'
                END as aging_bucket,
                (i.total - i.paid_amount) as outstanding_amount,
                payment_summary.payment_count,
                payment_summary.last_payment_date
            FROM invoices i
            LEFT JOIN (
                SELECT 
                    invoice_id,
                    COUNT(*) as payment_count,
                    MAX(payment_date) as last_payment_date
                FROM invoice_payments
                GROUP BY invoice_id
            ) payment_summary ON payment_summary.invoice_id = i.id
            WHERE i.customer_id = ?
              AND i.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND i.status IN ('unpaid', 'partial')
              AND (i.total - i.paid_amount) > 0.01
            ORDER BY i.created_at DESC, i.total DESC
        ";
        
        try {
            $startTime = microtime(true);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$customerId, $days]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Calculate aging summary
            $agingSummary = $this->calculateAgingSummary($results);
            
            $response = [
                'invoices' => $results,
                'summary' => $agingSummary,
                'execution_time_ms' => round($executionTime, 2)
            ];
            
            // Cache for 1 hour
            $this->queryCache->set($cacheKey, $response, 3600);
            
            Logger::info('Customer aging calculated', [
                'customer_id' => $customerId,
                'invoice_count' => count($results),
                'execution_time_ms' => $executionTime
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            Logger::error('Customer aging calculation failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Calculate aging summary from results
     */
    private function calculateAgingSummary(array $invoices): array
    {
        $summary = [
            '0-30' => ['count' => 0, 'amount' => 0.0],
            '31-60' => ['count' => 0, 'amount' => 0.0],
            '61-90' => ['count' => 0, 'amount' => 0.0],
            '90+' => ['count' => 0, 'amount' => 0.0],
            'totals' => ['count' => 0, 'amount' => 0.0]
        ];
        
        foreach ($invoices as $invoice) {
            $bucket = $invoice['aging_bucket'];
            $amount = (float)$invoice['outstanding_amount'];
            
            $summary[$bucket]['count']++;
            $summary[$bucket]['amount'] += $amount;
            $summary['totals']['count']++;
            $summary['totals']['amount'] += $amount;
        }
        
        return $summary;
    }
    
    /**
     * Optimized inventory lookup with warehouse filtering
     */
    public function getOptimizedInventoryLookup(
        ?int $warehouseId = null,
        ?int $productId = null,
        bool $lowStockOnly = false,
        int $lowStockThreshold = 5
    ): array {
        $cacheKey = 'inventory:lookup:' . md5(serialize(func_get_args()));
        
        $cached = $this->queryCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $pdo = DB::conn();
        
        // Optimized inventory query with proper JOINs
        $sql = "
            SELECT /*+ USE_INDEX(ps, idx_product_stocks_warehouse_qty) */
                ps.product_id,
                ps.warehouse_id,
                ps.qty_on_hand,
                ps.qty_reserved,
                (ps.qty_on_hand - ps.qty_reserved) as available_qty,
                p.code as product_code,
                p.name as product_name,
                p.cost,
                p.price,
                w.name as warehouse_name,
                c.name as category_name,
                mk.name as make_name
            FROM product_stocks ps
            INNER JOIN products p ON p.id = ps.product_id
            INNER JOIN warehouses w ON w.id = ps.warehouse_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN makes mk ON mk.id = p.make_id
        ";
        
        $whereConditions = [];
        $params = [];
        
        if ($warehouseId !== null) {
            $whereConditions[] = "ps.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        if ($productId !== null) {
            $whereConditions[] = "ps.product_id = ?";
            $params[] = $productId;
        }
        
        if ($lowStockOnly) {
            $whereConditions[] = "ps.qty_on_hand <= ?";
            $params[] = $lowStockThreshold;
        } else {
            // Exclude zero stock items unless specifically requested
            $whereConditions[] = "ps.qty_on_hand > 0";
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY w.name, p.name";
        
        try {
            $startTime = microtime(true);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Cache for 10 minutes (inventory changes frequently)
            $this->queryCache->set($cacheKey, $results, 600);
            
            return $results;
            
        } catch (Exception $e) {
            Logger::error('Optimized inventory lookup failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Batch load invoice details with optimized JOINs
     */
    public function batchLoadInvoiceDetails(array $invoiceIds): array
    {
        if (empty($invoiceIds)) {
            return [];
        }
        
        $cacheKeys = array_map(fn($id) => "invoice:details:$id", $invoiceIds);
        $cached = $this->queryCache->getMultiple($cacheKeys);
        
        // Find uncached invoice IDs
        $uncachedIds = [];
        foreach ($invoiceIds as $id) {
            $cacheKey = "invoice:details:$id";
            if (!isset($cached[$cacheKey])) {
                $uncachedIds[] = $id;
            }
        }
        
        $results = [];
        
        // Add cached results
        foreach ($cached as $cacheKey => $data) {
            if ($data !== null) {
                $invoiceId = (int)str_replace('invoice:details:', '', $cacheKey);
                $results[$invoiceId] = $data;
            }
        }
        
        // Load uncached invoices
        if (!empty($uncachedIds)) {
            $freshResults = $this->loadInvoiceDetailsBatch($uncachedIds);
            
            // Cache fresh results
            $cacheData = [];
            foreach ($freshResults as $invoiceId => $data) {
                $cacheKey = "invoice:details:$invoiceId";
                $cacheData[$cacheKey] = $data;
                $results[$invoiceId] = $data;
            }
            
            $this->queryCache->setMultiple($cacheData, 1800); // 30 minutes
        }
        
        return $results;
    }
    
    /**
     * Load invoice details in batch
     */
    private function loadInvoiceDetailsBatch(array $invoiceIds): array
    {
        $pdo = DB::conn();
        
        $placeholders = str_repeat('?,', count($invoiceIds) - 1) . '?';
        
        $sql = "
            SELECT 
                i.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                line_summary.line_count,
                line_summary.total_quantity
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            LEFT JOIN (
                SELECT 
                    invoice_id,
                    COUNT(*) as line_count,
                    SUM(qty) as total_quantity
                FROM invoice_lines
                GROUP BY invoice_id
            ) line_summary ON line_summary.invoice_id = i.id
            WHERE i.id IN ($placeholders)
        ";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($invoiceIds);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Index by invoice ID
            $indexed = [];
            foreach ($results as $result) {
                $indexed[(int)$result['id']] = $result;
            }
            
            return $indexed;
            
        } catch (Exception $e) {
            Logger::error('Batch invoice loading failed', [
                'error' => $e->getMessage(),
                'invoice_ids' => $invoiceIds
            ]);
            throw $e;
        }
    }
    
    /**
     * Optimized reference data loading with caching integration
     */
    public function getOptimizedReferenceData(string $type): array
    {
        // Use existing cache first
        switch ($type) {
            case 'categories':
                return $this->referenceCache->getCategories();
            case 'makes':
                return $this->referenceCache->getMakes();
            case 'models':
                return $this->referenceCache->getModels();
            case 'warehouses':
                return $this->referenceCache->getWarehouses();
            default:
                throw new \InvalidArgumentException("Unknown reference data type: $type");
        }
    }
    
    /**
     * Optimized search across multiple entities
     */
    public function performOptimizedSearch(string $query, array $types = ['products', 'customers']): array
    {
        $cacheKey = 'search:' . md5($query . implode(',', $types));
        
        $cached = $this->queryCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $results = [];
        $pdo = DB::conn();
        
        try {
            if (in_array('products', $types)) {
                $results['products'] = $this->searchProducts($pdo, $query);
            }
            
            if (in_array('customers', $types)) {
                $results['customers'] = $this->searchCustomers($pdo, $query);
            }
            
            if (in_array('invoices', $types)) {
                $results['invoices'] = $this->searchInvoices($pdo, $query);
            }
            
            // Cache for 5 minutes
            $this->queryCache->set($cacheKey, $results, 300);
            
            return $results;
            
        } catch (Exception $e) {
            Logger::error('Optimized search failed', [
                'query' => $query,
                'types' => $types,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Search products with optimized query
     */
    private function searchProducts(PDO $pdo, string $query): array
    {
        $sql = "
            SELECT p.id, p.code, p.name, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.name LIKE ? OR p.code LIKE ?
            ORDER BY 
                CASE WHEN p.code = ? THEN 1 ELSE 2 END,
                CASE WHEN p.name LIKE ? THEN 1 ELSE 2 END,
                p.name
            LIMIT 20
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $query, $query . '%']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search customers with optimized query
     */
    private function searchCustomers(PDO $pdo, string $query): array
    {
        $sql = "
            SELECT id, name, email, phone
            FROM customers
            WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY 
                CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                name
            LIMIT 10
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $query . '%']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search invoices with optimized query
     */
    private function searchInvoices(PDO $pdo, string $query): array
    {
        $sql = "
            SELECT i.id, i.inv_no, i.total, i.created_at, c.name as customer_name
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.inv_no LIKE ? OR c.name LIKE ?
            ORDER BY i.created_at DESC
            LIMIT 10
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get query performance statistics
     */
    public function getPerformanceStats(): array
    {
        return [
            'cache_stats' => $this->queryCache->getStats(),
            'recent_slow_queries' => $this->getRecentSlowQueries(),
            'optimization_suggestions' => $this->getOptimizationSuggestions()
        ];
    }
    
    /**
     * Get recent slow queries
     */
    private function getRecentSlowQueries(): array
    {
        // This would typically read from a slow query log
        // For now, return placeholder
        return [
            'note' => 'Slow query monitoring would be implemented here',
            'queries' => []
        ];
    }
    
    /**
     * Get optimization suggestions
     */
    private function getOptimizationSuggestions(): array
    {
        return [
            'Enable query cache if not already enabled',
            'Monitor index usage with EXPLAIN plans',
            'Consider partitioning large tables',
            'Implement connection pooling for high-traffic scenarios'
        ];
    }
}