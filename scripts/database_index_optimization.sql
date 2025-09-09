-- T008: Critical Database Index Creation
-- Performance Optimization Script for Spare Parts Management System
-- Execute as database administrator

-- ================================================================
-- PERFORMANCE BASELINE AND INDEX ANALYSIS
-- ================================================================

SELECT 'DATABASE_INDEX_OPTIMIZATION_T008' as task, NOW() as start_time;

-- Display current database status
SELECT 'CURRENT_DATABASE_STATUS' as analysis_type;
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH,
    ROUND(DATA_LENGTH/1024/1024, 2) as data_mb,
    ROUND(INDEX_LENGTH/1024/1024, 2) as index_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('products', 'invoices', 'purchase_invoices', 'product_stocks', 'inventory_ledger')
ORDER BY DATA_LENGTH DESC;

-- Check existing indexes on target tables
SELECT 'EXISTING_INDEXES_ANALYSIS' as analysis_type;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('products', 'invoices', 'purchase_invoices', 'product_stocks', 'inventory_ledger')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ================================================================
-- PERFORMANCE IMPACT ASSESSMENT (PRE-OPTIMIZATION)
-- ================================================================

SELECT 'PRE_OPTIMIZATION_QUERY_ANALYSIS' as analysis_type;

-- Test critical queries without indexes
-- Query 1: Products filtering by category, make, model (most common product search)
EXPLAIN FORMAT=JSON 
SELECT p.*, c.name AS category_name, mk.name AS make_name, vm.name AS model_name,
       COALESCE(SUM(ps.qty_on_hand),0) AS on_hand,
       COALESCE(SUM(ps.qty_reserved),0) AS reserved
FROM products p
LEFT JOIN categories c ON c.id=p.category_id
LEFT JOIN makes mk ON mk.id=p.make_id
LEFT JOIN vehicle_models vm ON vm.id=p.model_id
LEFT JOIN product_stocks ps ON ps.product_id=p.id
WHERE p.category_id=1 AND p.make_id=1 AND p.model_id=1
GROUP BY p.id ORDER BY p.name;

-- Query 2: Customer invoices by date and status (customer aging reports)
EXPLAIN FORMAT=JSON
SELECT i.inv_no, i.total, i.paid_amount, i.status, i.created_at
FROM invoices i
WHERE i.customer_id = 1 
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND i.status IN ('unpaid', 'partial')
ORDER BY i.created_at DESC;

-- Query 3: Product stock lookup by warehouse (inventory management)
EXPLAIN FORMAT=JSON
SELECT ps.*, p.name AS product_name, w.name AS warehouse_name
FROM product_stocks ps
JOIN products p ON p.id = ps.product_id
JOIN warehouses w ON w.id = ps.warehouse_id
WHERE ps.warehouse_id = 1
  AND ps.qty_on_hand > 0
ORDER BY p.name;

-- Query 4: Inventory ledger history by product and date (audit trail)
EXPLAIN FORMAT=JSON
SELECT il.*, p.name AS product_name
FROM inventory_ledger il
JOIN products p ON p.id = il.product_id
WHERE il.product_id = 1
  AND il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY il.created_at DESC;

-- ================================================================
-- CRITICAL INDEX CREATION
-- ================================================================

SELECT 'CREATING_CRITICAL_INDEXES' as optimization_phase, NOW() as phase_start;

-- Index 1: Composite index on products for multi-column filtering
-- Covers the most common product search pattern: category + make + model
SELECT 'CREATING_PRODUCTS_COMPOSITE_INDEX' as index_action;
CREATE INDEX idx_products_category_make_model 
ON products(category_id, make_id, model_id);

-- Verify index creation
SHOW INDEX FROM products WHERE Key_name = 'idx_products_category_make_model';

-- Index 2: Customer invoices index for aging reports and payment tracking
-- Covers customer-specific queries with date filtering and status checks
SELECT 'CREATING_INVOICES_CUSTOMER_INDEX' as index_action;
CREATE INDEX idx_invoices_customer_date_status 
ON invoices(customer_id, created_at, status);

-- Verify index creation
SHOW INDEX FROM invoices WHERE Key_name = 'idx_invoices_customer_date_status';

-- Index 3: Product stocks warehouse lookup index
-- Note: product_stocks already has PRIMARY KEY (product_id, warehouse_id)
-- but we need efficient warehouse-based lookups
SELECT 'CREATING_PRODUCT_STOCKS_WAREHOUSE_INDEX' as index_action;
CREATE INDEX idx_product_stocks_warehouse_qty
ON product_stocks(warehouse_id, qty_on_hand);

-- Verify index creation
SHOW INDEX FROM product_stocks WHERE Key_name = 'idx_product_stocks_warehouse_qty';

-- Index 4: Inventory ledger for product history and audit trails
-- Covers product-specific history queries with date filtering
SELECT 'CREATING_INVENTORY_LEDGER_INDEX' as index_action;
CREATE INDEX idx_inventory_ledger_product_date
ON inventory_ledger(product_id, created_at);

-- Verify index creation
SHOW INDEX FROM inventory_ledger WHERE Key_name = 'idx_inventory_ledger_product_date';

-- ================================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- ================================================================

SELECT 'CREATING_ADDITIONAL_PERFORMANCE_INDEXES' as optimization_phase;

-- Additional index for purchase invoices (similar to sales invoices)
SELECT 'CREATING_PURCHASE_INVOICES_SUPPLIER_INDEX' as index_action;
CREATE INDEX idx_purchase_invoices_supplier_date_status
ON purchase_invoices(supplier_id, created_at, status);

-- Index for activity log performance (audit and reporting)
SELECT 'CREATING_ACTIVITY_LOG_INDEX' as index_action;
CREATE INDEX idx_activity_log_entity_action_date
ON activity_log(entity_type, entity_id, action, created_at);

-- Index for COGS entries performance
SELECT 'CREATING_COGS_ENTRIES_INDEX' as index_action;
CREATE INDEX idx_cogs_entries_invoice_product
ON cogs_entries(invoice_id, product_id, created_at);

-- ================================================================
-- POST-OPTIMIZATION QUERY ANALYSIS
-- ================================================================

SELECT 'POST_OPTIMIZATION_QUERY_ANALYSIS' as analysis_type;

-- Re-test the same critical queries with new indexes
-- Query 1: Products filtering (should now use composite index)
SELECT 'PRODUCTS_FILTERING_WITH_INDEX' as test_query;
EXPLAIN FORMAT=JSON 
SELECT p.*, c.name AS category_name, mk.name AS make_name, vm.name AS model_name,
       COALESCE(SUM(ps.qty_on_hand),0) AS on_hand,
       COALESCE(SUM(ps.qty_reserved),0) AS reserved
FROM products p
LEFT JOIN categories c ON c.id=p.category_id
LEFT JOIN makes mk ON mk.id=p.make_id
LEFT JOIN vehicle_models vm ON vm.id=p.model_id
LEFT JOIN product_stocks ps ON ps.product_id=p.id
WHERE p.category_id=1 AND p.make_id=1 AND p.model_id=1
GROUP BY p.id ORDER BY p.name;

-- Query 2: Customer invoices (should now use customer+date+status index)
SELECT 'CUSTOMER_INVOICES_WITH_INDEX' as test_query;
EXPLAIN FORMAT=JSON
SELECT i.inv_no, i.total, i.paid_amount, i.status, i.created_at
FROM invoices i
WHERE i.customer_id = 1 
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND i.status IN ('unpaid', 'partial')
ORDER BY i.created_at DESC;

-- Query 3: Product stocks (should now use warehouse index)
SELECT 'PRODUCT_STOCKS_WITH_INDEX' as test_query;
EXPLAIN FORMAT=JSON
SELECT ps.*, p.name AS product_name, w.name AS warehouse_name
FROM product_stocks ps
JOIN products p ON p.id = ps.product_id
JOIN warehouses w ON w.id = ps.warehouse_id
WHERE ps.warehouse_id = 1
  AND ps.qty_on_hand > 0
ORDER BY p.name;

-- Query 4: Inventory ledger (should now use product+date index)
SELECT 'INVENTORY_LEDGER_WITH_INDEX' as test_query;
EXPLAIN FORMAT=JSON
SELECT il.*, p.name AS product_name
FROM inventory_ledger il
JOIN products p ON p.id = il.product_id
WHERE il.product_id = 1
  AND il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY il.created_at DESC;

-- ================================================================
-- INDEX USAGE VALIDATION
-- ================================================================

SELECT 'INDEX_USAGE_VALIDATION' as validation_phase;

-- Check that indexes are being used by examining query plans
-- Force MySQL to analyze table statistics for accurate cardinality
ANALYZE TABLE products, invoices, purchase_invoices, product_stocks, inventory_ledger;

-- Verify index cardinality and selectivity
SELECT 
    'INDEX_STATISTICS' as analysis_type,
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    CARDINALITY,
    ROUND(CARDINALITY / (SELECT TABLE_ROWS FROM information_schema.TABLES t WHERE t.TABLE_NAME = s.TABLE_NAME AND t.TABLE_SCHEMA = s.TABLE_SCHEMA), 4) as selectivity
FROM information_schema.STATISTICS s
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
    'idx_products_category_make_model',
    'idx_invoices_customer_date_status',
    'idx_product_stocks_warehouse_qty',
    'idx_inventory_ledger_product_date',
    'idx_purchase_invoices_supplier_date_status',
    'idx_activity_log_entity_action_date',
    'idx_cogs_entries_invoice_product'
  )
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ================================================================
-- PERFORMANCE BENCHMARKING QUERIES
-- ================================================================

SELECT 'PERFORMANCE_BENCHMARKING' as benchmark_phase;

-- Benchmark common query patterns
-- Test 1: Product search with multiple filters
SELECT 'BENCHMARK_PRODUCT_SEARCH' as benchmark_test;
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM products p
    WHERE p.category_id IN (1,2,3) 
      AND p.make_id IN (1,2) 
      AND p.model_id IS NOT NULL
)) as iterations_per_second;

-- Test 2: Customer invoice aging calculation
SELECT 'BENCHMARK_CUSTOMER_AGING' as benchmark_test;
SELECT BENCHMARK(1000, (
    SELECT SUM(i.total - i.paid_amount)
    FROM invoices i
    WHERE i.customer_id <= 5
      AND i.status != 'paid'
      AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
)) as iterations_per_second;

-- Test 3: Inventory stock lookup across warehouses
SELECT 'BENCHMARK_INVENTORY_LOOKUP' as benchmark_test;
SELECT BENCHMARK(1000, (
    SELECT SUM(ps.qty_on_hand)
    FROM product_stocks ps
    WHERE ps.warehouse_id <= 3
      AND ps.qty_on_hand > 0
)) as iterations_per_second;

-- ================================================================
-- STORAGE AND PERFORMANCE IMPACT ANALYSIS
-- ================================================================

SELECT 'STORAGE_IMPACT_ANALYSIS' as analysis_phase;

-- Check storage impact of new indexes
SELECT 
    'POST_INDEX_STORAGE' as analysis_type,
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(DATA_LENGTH/1024/1024, 2) as data_mb,
    ROUND(INDEX_LENGTH/1024/1024, 2) as index_mb,
    ROUND((INDEX_LENGTH/DATA_LENGTH)*100, 2) as index_ratio_percent
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('products', 'invoices', 'purchase_invoices', 'product_stocks', 'inventory_ledger')
ORDER BY INDEX_LENGTH DESC;

-- Check index fragmentation and efficiency
SELECT 
    'INDEX_FRAGMENTATION' as analysis_type,
    TABLE_NAME,
    INDEX_NAME,
    ROUND(100 * (1 - (CARDINALITY / TABLE_ROWS)), 2) as fragmentation_percent
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t USING (TABLE_SCHEMA, TABLE_NAME)
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.INDEX_NAME IN (
    'idx_products_category_make_model',
    'idx_invoices_customer_date_status',
    'idx_product_stocks_warehouse_qty',
    'idx_inventory_ledger_product_date'
  )
  AND t.TABLE_ROWS > 0;

-- ================================================================
-- INDEX MAINTENANCE RECOMMENDATIONS
-- ================================================================

SELECT 'INDEX_MAINTENANCE_RECOMMENDATIONS' as recommendations_phase;

-- Create stored procedure for index maintenance
DELIMITER //
CREATE OR REPLACE PROCEDURE OptimizeIndexes()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE table_name VARCHAR(64);
    DECLARE cur CURSOR FOR 
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND ENGINE = 'InnoDB'
          AND TABLE_NAME IN ('products', 'invoices', 'purchase_invoices', 'product_stocks', 'inventory_ledger');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SELECT 'STARTING_INDEX_OPTIMIZATION' as status, NOW() as start_time;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO table_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Analyze table statistics
        SET @sql = CONCAT('ANALYZE TABLE ', table_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Optimize table (rebuilds indexes if needed)
        SET @sql = CONCAT('OPTIMIZE TABLE ', table_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SELECT CONCAT('OPTIMIZED_TABLE: ', table_name) as status;
        
    END LOOP;
    CLOSE cur;
    
    SELECT 'INDEX_OPTIMIZATION_COMPLETE' as status, NOW() as end_time;
END //
DELIMITER ;

-- ================================================================
-- PERFORMANCE MONITORING QUERIES
-- ================================================================

SELECT 'PERFORMANCE_MONITORING_SETUP' as setup_phase;

-- Create view for index usage monitoring
CREATE OR REPLACE VIEW v_index_usage_stats AS
SELECT 
    s.TABLE_NAME,
    s.INDEX_NAME,
    s.COLUMN_NAME,
    s.CARDINALITY,
    t.TABLE_ROWS,
    CASE 
        WHEN t.TABLE_ROWS > 0 THEN ROUND(s.CARDINALITY / t.TABLE_ROWS * 100, 2)
        ELSE 0 
    END as selectivity_percent,
    CASE
        WHEN s.CARDINALITY / t.TABLE_ROWS > 0.3 THEN 'HIGH_SELECTIVITY'
        WHEN s.CARDINALITY / t.TABLE_ROWS > 0.1 THEN 'MEDIUM_SELECTIVITY'
        ELSE 'LOW_SELECTIVITY'
    END as selectivity_rating,
    ROUND(t.INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t USING (TABLE_SCHEMA, TABLE_NAME)
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.INDEX_NAME NOT IN ('PRIMARY')
ORDER BY t.INDEX_LENGTH DESC, s.CARDINALITY DESC;

-- Create view for slow query candidates
CREATE OR REPLACE VIEW v_slow_query_candidates AS
SELECT 
    'PRODUCTS_WITHOUT_FILTERS' as query_type,
    'Consider adding WHERE clauses to product queries' as recommendation,
    'Add category_id, make_id, or model_id filters' as suggestion
UNION ALL
SELECT 
    'INVOICE_DATE_RANGES',
    'Use date range filters for invoice queries',
    'Add created_at >= DATE_SUB(NOW(), INTERVAL X DAY)'
UNION ALL
SELECT 
    'STOCK_WAREHOUSE_FILTER',
    'Filter product_stocks by warehouse_id',
    'Add warehouse_id filter to avoid full table scans'
UNION ALL
SELECT 
    'INVENTORY_LEDGER_LIMITS',
    'Limit inventory_ledger queries by date',
    'Add created_at filter and LIMIT clause';

-- ================================================================
-- COMPLETION SUMMARY AND VALIDATION
-- ================================================================

SELECT 'T008_INDEX_OPTIMIZATION_SUMMARY' as completion_phase;

-- Final index count and summary
SELECT 
    'FINAL_INDEX_SUMMARY' as summary_type,
    COUNT(*) as total_indexes_created,
    GROUP_CONCAT(DISTINCT INDEX_NAME ORDER BY INDEX_NAME) as new_indexes
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
    'idx_products_category_make_model',
    'idx_invoices_customer_date_status',
    'idx_product_stocks_warehouse_qty',
    'idx_inventory_ledger_product_date',
    'idx_purchase_invoices_supplier_date_status',
    'idx_activity_log_entity_action_date',
    'idx_cogs_entries_invoice_product'
  );

-- Performance improvement validation
SELECT 'PERFORMANCE_VALIDATION' as validation_type;

-- Test key queries one final time to confirm index usage
EXPLAIN 
SELECT COUNT(*) 
FROM products p 
WHERE p.category_id=1 AND p.make_id=1 AND p.model_id=1;

EXPLAIN
SELECT COUNT(*) 
FROM invoices i 
WHERE i.customer_id=1 AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

EXPLAIN
SELECT COUNT(*) 
FROM product_stocks ps 
WHERE ps.warehouse_id=1 AND ps.qty_on_hand > 0;

EXPLAIN
SELECT COUNT(*) 
FROM inventory_ledger il 
WHERE il.product_id=1 AND il.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- ================================================================
-- RECOMMENDATIONS FOR FUTURE OPTIMIZATION
-- ================================================================

SELECT 'FUTURE_OPTIMIZATION_RECOMMENDATIONS' as recommendations;

SELECT 'RECOMMENDATION' as type, 'Monitor index usage regularly with v_index_usage_stats view' as recommendation
UNION ALL
SELECT 'RECOMMENDATION', 'Run OptimizeIndexes() procedure monthly for index maintenance'
UNION ALL
SELECT 'RECOMMENDATION', 'Consider partitioning inventory_ledger table by date if it grows large'
UNION ALL
SELECT 'RECOMMENDATION', 'Monitor query performance with EXPLAIN plans regularly'
UNION ALL
SELECT 'RECOMMENDATION', 'Consider adding covering indexes for specific query patterns if needed'
UNION ALL
SELECT 'RECOMMENDATION', 'Use v_slow_query_candidates view to identify optimization opportunities';

SELECT 'T008_DATABASE_INDEX_OPTIMIZATION_COMPLETE' as status, NOW() as completion_time;

-- ================================================================
-- QUERY PATTERN GUIDELINES FOR DEVELOPERS
-- ================================================================

SELECT 'DEVELOPER_GUIDELINES' as guidelines_section;

SELECT 'GUIDELINE' as type, 'Products: Always filter by category_id, make_id, or model_id when possible' as guideline
UNION ALL
SELECT 'GUIDELINE', 'Invoices: Include customer_id and date ranges in WHERE clauses'
UNION ALL
SELECT 'GUIDELINE', 'Product Stocks: Filter by warehouse_id for warehouse-specific queries'
UNION ALL
SELECT 'GUIDELINE', 'Inventory Ledger: Always include product_id and date filters'
UNION ALL
SELECT 'GUIDELINE', 'Use LIMIT clauses on large result sets to improve response time'
UNION ALL
SELECT 'GUIDELINE', 'Avoid SELECT * queries; specify only needed columns'
UNION ALL
SELECT 'GUIDELINE', 'Use EXPLAIN to verify query plans use the expected indexes';

-- Script completion confirmation
SELECT 'SUCCESS: T008 Critical Database Index Creation completed successfully' as final_status,
       NOW() as completion_timestamp;