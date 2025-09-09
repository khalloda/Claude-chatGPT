-- T008: Database Performance Testing Script
-- Before and After Index Optimization Comparison
-- Execute before and after running database_index_optimization.sql

-- ================================================================
-- PERFORMANCE TESTING CONFIGURATION
-- ================================================================

-- Set test parameters
SET @test_iterations = 100;
SET @test_customer_id = 1;
SET @test_warehouse_id = 1;
SET @test_product_id = 1;
SET @test_category_id = 1;
SET @test_make_id = 1;
SET @test_model_id = 1;

SELECT 'T008_PERFORMANCE_TESTING_STARTED' as test_phase, NOW() as start_time;

-- ================================================================
-- TEST SUITE 1: PRODUCT SEARCH PERFORMANCE
-- ================================================================

SELECT 'PRODUCT_SEARCH_PERFORMANCE_TESTS' as test_suite;

-- Test 1.1: Basic product filtering by category
SELECT 'TEST_1_1_PRODUCT_CATEGORY_FILTER' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM products p
WHERE p.category_id = @test_category_id;

SET @end_time = NOW(6);
SELECT 
    'PRODUCT_CATEGORY_FILTER' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms,
    @test_iterations as test_note;

-- Test 1.2: Multi-column product filtering (category + make + model)
SELECT 'TEST_1_2_PRODUCT_MULTI_FILTER' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM products p
WHERE p.category_id = @test_category_id 
  AND p.make_id = @test_make_id 
  AND p.model_id = @test_model_id;

SET @end_time = NOW(6);
SELECT 
    'PRODUCT_MULTI_FILTER' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 1.3: Complex product query with joins and aggregation
SELECT 'TEST_1_3_PRODUCT_COMPLEX_QUERY' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE 
    p.id, p.name, p.code, c.name AS category_name, mk.name AS make_name, vm.name AS model_name,
    COALESCE(SUM(ps.qty_on_hand),0) AS on_hand,
    COALESCE(SUM(ps.qty_reserved),0) AS reserved
FROM products p
LEFT JOIN categories c ON c.id=p.category_id
LEFT JOIN makes mk ON mk.id=p.make_id
LEFT JOIN vehicle_models vm ON vm.id=p.model_id
LEFT JOIN product_stocks ps ON ps.product_id=p.id
WHERE p.category_id = @test_category_id 
  AND p.make_id = @test_make_id
GROUP BY p.id 
ORDER BY p.name
LIMIT 50;

SET @end_time = NOW(6);
SELECT 
    'PRODUCT_COMPLEX_QUERY' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- ================================================================
-- TEST SUITE 2: INVOICE AND CUSTOMER PERFORMANCE
-- ================================================================

SELECT 'INVOICE_CUSTOMER_PERFORMANCE_TESTS' as test_suite;

-- Test 2.1: Customer invoice lookup
SELECT 'TEST_2_1_CUSTOMER_INVOICES' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM invoices i
WHERE i.customer_id = @test_customer_id;

SET @end_time = NOW(6);
SELECT 
    'CUSTOMER_INVOICES' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 2.2: Customer invoices with date filter
SELECT 'TEST_2_2_CUSTOMER_INVOICES_DATE' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM invoices i
WHERE i.customer_id = @test_customer_id 
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY);

SET @end_time = NOW(6);
SELECT 
    'CUSTOMER_INVOICES_DATE' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 2.3: Customer invoices with status and date filters
SELECT 'TEST_2_3_CUSTOMER_INVOICES_STATUS_DATE' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE 
    i.inv_no, i.total, i.paid_amount, i.status, i.created_at
FROM invoices i
WHERE i.customer_id = @test_customer_id 
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND i.status IN ('unpaid', 'partial')
ORDER BY i.created_at DESC
LIMIT 100;

SET @end_time = NOW(6);
SELECT 
    'CUSTOMER_INVOICES_STATUS_DATE' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 2.4: Customer aging calculation (complex aggregation)
SELECT 'TEST_2_4_CUSTOMER_AGING_CALC' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE 
    SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) BETWEEN 0 AND 30 THEN i.total - i.paid_amount ELSE 0 END) as current_0_30,
    SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) BETWEEN 31 AND 60 THEN i.total - i.paid_amount ELSE 0 END) as aging_31_60,
    SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) BETWEEN 61 AND 90 THEN i.total - i.paid_amount ELSE 0 END) as aging_61_90,
    SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) > 90 THEN i.total - i.paid_amount ELSE 0 END) as aging_over_90
FROM invoices i
WHERE i.customer_id = @test_customer_id 
  AND i.status != 'paid';

SET @end_time = NOW(6);
SELECT 
    'CUSTOMER_AGING_CALC' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- ================================================================
-- TEST SUITE 3: INVENTORY AND STOCK PERFORMANCE
-- ================================================================

SELECT 'INVENTORY_STOCK_PERFORMANCE_TESTS' as test_suite;

-- Test 3.1: Product stock lookup by warehouse
SELECT 'TEST_3_1_PRODUCT_STOCKS_WAREHOUSE' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM product_stocks ps
WHERE ps.warehouse_id = @test_warehouse_id;

SET @end_time = NOW(6);
SELECT 
    'PRODUCT_STOCKS_WAREHOUSE' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 3.2: Available stock lookup (warehouse + quantity filter)
SELECT 'TEST_3_2_AVAILABLE_STOCK' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM product_stocks ps
WHERE ps.warehouse_id = @test_warehouse_id 
  AND ps.qty_on_hand > 0;

SET @end_time = NOW(6);
SELECT 
    'AVAILABLE_STOCK' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 3.3: Complete stock report with product details
SELECT 'TEST_3_3_STOCK_REPORT_WITH_PRODUCTS' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE 
    ps.product_id, ps.warehouse_id, ps.qty_on_hand, ps.qty_reserved, ps.avg_cost,
    p.name AS product_name, p.code AS product_code,
    w.name AS warehouse_name
FROM product_stocks ps
JOIN products p ON p.id = ps.product_id
JOIN warehouses w ON w.id = ps.warehouse_id
WHERE ps.warehouse_id = @test_warehouse_id
  AND ps.qty_on_hand > 0
ORDER BY p.name
LIMIT 100;

SET @end_time = NOW(6);
SELECT 
    'STOCK_REPORT_WITH_PRODUCTS' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- ================================================================
-- TEST SUITE 4: INVENTORY LEDGER PERFORMANCE
-- ================================================================

SELECT 'INVENTORY_LEDGER_PERFORMANCE_TESTS' as test_suite;

-- Test 4.1: Product history lookup
SELECT 'TEST_4_1_PRODUCT_HISTORY' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM inventory_ledger il
WHERE il.product_id = @test_product_id;

SET @end_time = NOW(6);
SELECT 
    'PRODUCT_HISTORY' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 4.2: Recent product history with date filter
SELECT 'TEST_4_2_RECENT_PRODUCT_HISTORY' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE COUNT(*) as result_count
FROM inventory_ledger il
WHERE il.product_id = @test_product_id 
  AND il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

SET @end_time = NOW(6);
SELECT 
    'RECENT_PRODUCT_HISTORY' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- Test 4.3: Complete audit trail query
SELECT 'TEST_4_3_COMPLETE_AUDIT_TRAIL' as test_name;
SET @start_time = NOW(6);

SELECT SQL_NO_CACHE 
    il.id, il.doc_type, il.doc_id, il.qty_delta, il.unit_cost, il.value_delta, il.created_at,
    p.name AS product_name, p.code AS product_code,
    w.name AS warehouse_name
FROM inventory_ledger il
JOIN products p ON p.id = il.product_id
JOIN warehouses w ON w.id = il.warehouse_id
WHERE il.product_id = @test_product_id
  AND il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY il.created_at DESC
LIMIT 50;

SET @end_time = NOW(6);
SELECT 
    'COMPLETE_AUDIT_TRAIL' as test_name,
    ROUND(TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000, 3) as execution_time_ms;

-- ================================================================
-- BENCHMARK TESTING WITH MULTIPLE ITERATIONS
-- ================================================================

SELECT 'BENCHMARK_TESTING_MULTIPLE_ITERATIONS' as test_suite;

-- Benchmark 1: Product filtering (using BENCHMARK function)
SELECT 'BENCHMARK_1_PRODUCT_FILTERING' as benchmark_name;
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM products p 
    WHERE p.category_id = 1 AND p.make_id = 1
)) as benchmark_result;

-- Benchmark 2: Customer invoice lookup
SELECT 'BENCHMARK_2_CUSTOMER_INVOICES' as benchmark_name;
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM invoices i 
    WHERE i.customer_id = 1 
      AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
)) as benchmark_result;

-- Benchmark 3: Stock warehouse lookup
SELECT 'BENCHMARK_3_STOCK_LOOKUP' as benchmark_name;
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM product_stocks ps 
    WHERE ps.warehouse_id = 1 
      AND ps.qty_on_hand > 0
)) as benchmark_result;

-- Benchmark 4: Inventory ledger history
SELECT 'BENCHMARK_4_INVENTORY_HISTORY' as benchmark_name;
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM inventory_ledger il 
    WHERE il.product_id = 1 
      AND il.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
)) as benchmark_result;

-- ================================================================
-- QUERY PLAN ANALYSIS
-- ================================================================

SELECT 'QUERY_PLAN_ANALYSIS' as analysis_suite;

-- Analyze critical query plans to verify index usage
SELECT 'ANALYZING_QUERY_PLANS' as analysis_phase;

-- Query Plan 1: Product multi-filter
EXPLAIN FORMAT=JSON
SELECT p.*, c.name AS category_name, mk.name AS make_name, vm.name AS model_name
FROM products p
LEFT JOIN categories c ON c.id=p.category_id
LEFT JOIN makes mk ON mk.id=p.make_id
LEFT JOIN vehicle_models vm ON vm.id=p.model_id
WHERE p.category_id = @test_category_id 
  AND p.make_id = @test_make_id 
  AND p.model_id = @test_model_id;

-- Query Plan 2: Customer invoice with filters
EXPLAIN FORMAT=JSON
SELECT i.inv_no, i.total, i.status, i.created_at
FROM invoices i
WHERE i.customer_id = @test_customer_id 
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND i.status IN ('unpaid', 'partial')
ORDER BY i.created_at DESC;

-- Query Plan 3: Product stocks lookup
EXPLAIN FORMAT=JSON
SELECT ps.*, p.name AS product_name
FROM product_stocks ps
JOIN products p ON p.id = ps.product_id
WHERE ps.warehouse_id = @test_warehouse_id
  AND ps.qty_on_hand > 0;

-- Query Plan 4: Inventory ledger history
EXPLAIN FORMAT=JSON
SELECT il.*, p.name AS product_name
FROM inventory_ledger il
JOIN products p ON p.id = il.product_id
WHERE il.product_id = @test_product_id
  AND il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY il.created_at DESC;

-- ================================================================
-- PERFORMANCE SUMMARY AND RECOMMENDATIONS
-- ================================================================

SELECT 'PERFORMANCE_TESTING_SUMMARY' as summary_phase;

-- Check current index statistics
SELECT 
    'CURRENT_INDEX_STATISTICS' as stats_type,
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t USING (TABLE_SCHEMA, TABLE_NAME)
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('products', 'invoices', 'product_stocks', 'inventory_ledger')
  AND s.INDEX_NAME NOT IN ('PRIMARY')
ORDER BY t.INDEX_LENGTH DESC, s.CARDINALITY DESC;

-- Performance recommendations based on testing
SELECT 'PERFORMANCE_RECOMMENDATIONS' as recommendation_type;

SELECT 'RECOMMENDATION' as type, 'Use composite indexes for multi-column WHERE clauses' as recommendation
UNION ALL
SELECT 'RECOMMENDATION', 'Always include date ranges in time-based queries'
UNION ALL
SELECT 'RECOMMENDATION', 'Filter by primary keys (customer_id, product_id, warehouse_id) when possible'
UNION ALL
SELECT 'RECOMMENDATION', 'Use LIMIT clauses to prevent large result sets'
UNION ALL
SELECT 'RECOMMENDATION', 'Monitor query execution plans regularly with EXPLAIN'
UNION ALL
SELECT 'RECOMMENDATION', 'Consider covering indexes for frequently accessed column combinations';

-- Test completion summary
SELECT 
    'T008_PERFORMANCE_TESTING_COMPLETE' as test_status,
    NOW() as completion_time,
    'Run this script before and after index creation to measure performance gains' as note;

-- ================================================================
-- AUTOMATED PERFORMANCE COMPARISON
-- ================================================================

-- Create a simple performance comparison table structure
CREATE TABLE IF NOT EXISTS performance_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_phase ENUM('BEFORE_INDEXES', 'AFTER_INDEXES') NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    execution_time_ms DECIMAL(10,3) NOT NULL,
    result_count INT DEFAULT NULL,
    test_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    INDEX idx_test_phase_name (test_phase, test_name),
    INDEX idx_timestamp (test_timestamp)
);

-- Function to log test results (call this manually with your test results)
-- INSERT INTO performance_test_results (test_phase, test_name, execution_time_ms, result_count, notes) 
-- VALUES ('BEFORE_INDEXES', 'PRODUCT_MULTI_FILTER', 25.123, 5, 'Baseline test before index optimization');

SELECT 'Performance testing script completed. Results should be manually recorded in performance_test_results table for comparison.' as final_note;