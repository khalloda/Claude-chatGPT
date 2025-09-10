-- T018: Additional Database Index Creation
-- Advanced Query Optimization Script for Spare Parts Management System
-- Execute as database administrator

-- ================================================================
-- ADDITIONAL INDEX ANALYSIS AND CREATION
-- ================================================================

SELECT 'T018_ADDITIONAL_DATABASE_INDEXES' as task, NOW() as start_time;

-- Display current database status
SELECT 'CURRENT_INDEX_STATUS' as analysis_type;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    CARDINALITY,
    INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('invoices', 'invoice_items', 'invoice_payments', 'product_stocks', 'customers')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ================================================================
-- ADVANCED QUERY PERFORMANCE TESTING (PRE-OPTIMIZATION)
-- ================================================================

SELECT 'PRE_OPTIMIZATION_ADVANCED_ANALYSIS' as analysis_type;

-- Test 1: Invoice line items JOIN performance
EXPLAIN FORMAT=JSON
SELECT il.*, p.name as product_name, p.code as product_code
FROM invoice_items il
JOIN products p ON p.id = il.product_id
WHERE il.invoice_id IN (1, 2, 3, 4, 5)
ORDER BY il.invoice_id, p.name;

-- Test 2: Customer invoice summary with payments
EXPLAIN FORMAT=JSON
SELECT 
    c.id,
    c.name,
    COUNT(i.id) as invoice_count,
    SUM(i.total) as total_invoiced,
    SUM(i.paid_amount) as total_paid,
    SUM(i.total - i.paid_amount) as outstanding
FROM customers c
LEFT JOIN invoices i ON i.customer_id = c.id
WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
GROUP BY c.id, c.name
HAVING outstanding > 0
ORDER BY outstanding DESC;

-- Test 3: Product stock across all warehouses
EXPLAIN FORMAT=JSON
SELECT 
    p.id,
    p.name,
    SUM(ps.qty_on_hand) as total_on_hand,
    COUNT(ps.warehouse_id) as warehouse_count
FROM products p
LEFT JOIN product_stocks ps ON ps.product_id = p.id
GROUP BY p.id, p.name
HAVING total_on_hand > 0
ORDER BY total_on_hand DESC;

-- Test 4: Invoice payment tracking
EXPLAIN FORMAT=JSON
SELECT 
    i.inv_no,
    i.total,
    COALESCE(payment_summary.total_paid, 0) as paid_amount,
    payment_summary.payment_count,
    payment_summary.last_payment
FROM invoices i
LEFT JOIN (
    SELECT 
        invoice_id,
        SUM(amount) as total_paid,
        COUNT(*) as payment_count,
        MAX(payment_date) as last_payment
    FROM invoice_payments
    GROUP BY invoice_id
) payment_summary ON payment_summary.invoice_id = i.id
WHERE i.status IN ('unpaid', 'partial')
ORDER BY i.created_at DESC;

-- ================================================================
-- ADVANCED INDEX CREATION
-- ================================================================

SELECT 'CREATING_ADVANCED_INDEXES' as optimization_phase, NOW() as phase_start;

-- Index 1: Invoice lines optimization for JOIN performance
-- Covers invoice_id and product_id for efficient JOINs
SELECT 'CREATING_INVOICE_ITEMS_COMPOSITE_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_product 
ON invoice_items(invoice_id, product_id);

-- Verify index creation
SELECT 'INDEX_VERIFICATION' as verification_type;
SHOW INDEX FROM invoice_items WHERE Key_name = 'idx_invoice_items_invoice_product';

-- Index 2: Invoice payments for payment tracking
-- Covers invoice_id and payment_date for efficient payment summaries
SELECT 'CREATING_INVOICE_PAYMENTS_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_invoice_payments_invoice_date 
ON invoice_payments(invoice_id, paid_at);

-- Verify index creation
SHOW INDEX FROM invoice_payments WHERE Key_name = 'idx_invoice_payments_invoice_date';

-- Index 3: Customer search optimization
-- Covers name and email for customer search functionality
SELECT 'CREATING_CUSTOMER_SEARCH_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_customers_name_email 
ON customers(name, email);

-- Verify index creation
SHOW INDEX FROM customers WHERE Key_name = 'idx_customers_name_email';

-- Index 4: Product search optimization
-- Covers name and code for product search functionality
SELECT 'CREATING_PRODUCT_SEARCH_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_products_name_code 
ON products(name, code);

-- Verify index creation
SHOW INDEX FROM products WHERE Key_name = 'idx_products_name_code';

-- Index 5: Invoice status and date optimization
-- Covers status and created_at for status-based queries
SELECT 'CREATING_INVOICE_STATUS_DATE_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_invoices_status_date 
ON invoices(status, created_at DESC);

-- Verify index creation
SHOW INDEX FROM invoices WHERE Key_name = 'idx_invoices_status_date';

-- Index 6: Product stocks warehouse lookup optimization
-- Enhanced version with quantity filtering
SELECT 'CREATING_ENHANCED_PRODUCT_STOCKS_INDEX' as index_action;
CREATE INDEX IF NOT EXISTS idx_product_stocks_warehouse_product_qty 
ON product_stocks(warehouse_id, product_id, qty_on_hand);

-- Verify index creation
SHOW INDEX FROM product_stocks WHERE Key_name = 'idx_product_stocks_warehouse_product_qty';

-- ================================================================
-- FULL-TEXT SEARCH INDEXES FOR ADVANCED SEARCH
-- ================================================================

SELECT 'CREATING_FULLTEXT_INDEXES' as optimization_phase;

-- Full-text index on product names and descriptions
SELECT 'CREATING_PRODUCT_FULLTEXT_INDEX' as index_action;
-- Check if a text/description column exists before creating fulltext index
SELECT COUNT(*) as has_description_column
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME IN ('description', 'details');

-- Create fulltext index on products name (and description if it exists)
-- Note: This will be conditional based on schema
SET @sql = '';
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 
            'CREATE FULLTEXT INDEX IF NOT EXISTS idx_products_fulltext ON products(name, description);'
        ELSE 
            'CREATE FULLTEXT INDEX IF NOT EXISTS idx_products_fulltext ON products(name);'
    END INTO @sql
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME = 'description';

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Full-text index on customer names
SELECT 'CREATING_CUSTOMER_FULLTEXT_INDEX' as index_action;
CREATE FULLTEXT INDEX IF NOT EXISTS idx_customers_fulltext ON customers(name);

-- ================================================================
-- POST-OPTIMIZATION PERFORMANCE TESTING
-- ================================================================

SELECT 'POST_OPTIMIZATION_QUERY_ANALYSIS' as analysis_type;

-- Re-test critical queries with new indexes
-- Test 1: Invoice line items JOIN performance (should be faster)
SELECT 'TESTING_INVOICE_LINES_JOIN_PERFORMANCE' as test_name;
EXPLAIN FORMAT=JSON
SELECT il.*, p.name as product_name, p.code as product_code
FROM invoice_lines il
JOIN products p ON p.id = il.product_id
WHERE il.invoice_id IN (1, 2, 3, 4, 5)
ORDER BY il.invoice_id, p.name;

-- Test 2: Customer search performance
SELECT 'TESTING_CUSTOMER_SEARCH_PERFORMANCE' as test_name;
EXPLAIN FORMAT=JSON
SELECT id, name, email, phone
FROM customers
WHERE name LIKE '%Smith%' OR email LIKE '%@example.com'
ORDER BY name;

-- Test 3: Product search performance
SELECT 'TESTING_PRODUCT_SEARCH_PERFORMANCE' as test_name;
EXPLAIN FORMAT=JSON
SELECT p.id, p.name, p.code, c.name as category_name
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
WHERE p.name LIKE '%brake%' OR p.code LIKE '%BRK%'
ORDER BY p.name;

-- Test 4: Invoice status filtering
SELECT 'TESTING_INVOICE_STATUS_FILTERING' as test_name;
EXPLAIN FORMAT=JSON
SELECT i.*, c.name as customer_name
FROM invoices i
LEFT JOIN customers c ON c.id = i.customer_id
WHERE i.status = 'unpaid'
  AND i.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
ORDER BY i.created_at DESC;

-- Test 5: Enhanced product stock lookup
SELECT 'TESTING_ENHANCED_STOCK_LOOKUP' as test_name;
EXPLAIN FORMAT=JSON
SELECT ps.*, p.name as product_name, w.name as warehouse_name
FROM product_stocks ps
JOIN products p ON p.id = ps.product_id
JOIN warehouses w ON w.id = ps.warehouse_id
WHERE ps.warehouse_id = 1 
  AND ps.qty_on_hand > 0
ORDER BY p.name;

-- ================================================================
-- INDEX USAGE AND PERFORMANCE MONITORING
-- ================================================================

SELECT 'INDEX_USAGE_MONITORING' as analysis_type;

-- Check index cardinality and usage
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    CASE 
        WHEN CARDINALITY = 0 THEN 'No data or unused'
        WHEN CARDINALITY < 10 THEN 'Low cardinality'
        WHEN CARDINALITY < 100 THEN 'Medium cardinality'
        ELSE 'High cardinality'
    END as cardinality_rating
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, CARDINALITY DESC;

-- Show table sizes after index creation
SELECT 'TABLE_SIZE_ANALYSIS' as analysis_type;
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    ROUND(DATA_LENGTH/1024/1024, 2) as data_mb,
    ROUND(INDEX_LENGTH/1024/1024, 2) as index_mb,
    ROUND((INDEX_LENGTH/DATA_LENGTH)*100, 2) as index_ratio_percent
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('products', 'invoices', 'invoice_items', 'customers', 'product_stocks', 'invoice_payments')
ORDER BY DATA_LENGTH DESC;

-- ================================================================
-- MAINTENANCE RECOMMENDATIONS
-- ================================================================

SELECT 'MAINTENANCE_RECOMMENDATIONS' as analysis_type;

-- Create stored procedure for index maintenance
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS OptimizeApplicationIndexes()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE table_name VARCHAR(255);
    DECLARE cur CURSOR FOR 
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND ENGINE = 'InnoDB'
          AND TABLE_NAME IN ('products', 'invoices', 'invoice_items', 'customers', 'product_stocks', 'invoice_payments');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Log optimization start
    INSERT INTO optimization_log (event_type, message, created_at) 
    VALUES ('INDEX_OPTIMIZATION', 'Starting automated index optimization', NOW())
    ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = VALUES(created_at);
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO table_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Optimize table
        SET @sql = CONCAT('OPTIMIZE TABLE ', table_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Log table optimization
        INSERT INTO optimization_log (event_type, message, created_at) 
        VALUES ('TABLE_OPTIMIZED', CONCAT('Optimized table: ', table_name), NOW())
        ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = VALUES(created_at);
    END LOOP;
    
    CLOSE cur;
    
    -- Update table statistics
    ANALYZE TABLE products, invoices, invoice_items, customers, product_stocks, invoice_payments;
    
    -- Log optimization completion
    INSERT INTO optimization_log (event_type, message, created_at) 
    VALUES ('INDEX_OPTIMIZATION', 'Completed automated index optimization', NOW())
    ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = VALUES(created_at);
END//
DELIMITER ;

-- Create optimization log table if it doesn't exist
CREATE TABLE IF NOT EXISTS optimization_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_type (event_type)
);

-- ================================================================
-- PERFORMANCE MONITORING VIEWS
-- ================================================================

SELECT 'CREATING_MONITORING_VIEWS' as optimization_phase;

-- Create view for index performance monitoring
CREATE OR REPLACE VIEW v_index_performance AS
SELECT 
    s.TABLE_NAME,
    s.INDEX_NAME,
    s.CARDINALITY,
    t.TABLE_ROWS,
    ROUND(s.CARDINALITY / t.TABLE_ROWS * 100, 2) as selectivity_percent,
    CASE 
        WHEN s.CARDINALITY / t.TABLE_ROWS > 0.8 THEN 'High selectivity'
        WHEN s.CARDINALITY / t.TABLE_ROWS > 0.5 THEN 'Medium selectivity'
        WHEN s.CARDINALITY / t.TABLE_ROWS > 0.1 THEN 'Low selectivity'
        ELSE 'Very low selectivity'
    END as selectivity_rating
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t ON t.TABLE_NAME = s.TABLE_NAME AND t.TABLE_SCHEMA = s.TABLE_SCHEMA
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.INDEX_NAME != 'PRIMARY'
  AND t.TABLE_ROWS > 0
ORDER BY s.TABLE_NAME, selectivity_percent DESC;

-- Create view for query performance monitoring
CREATE OR REPLACE VIEW v_table_performance AS
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    ROUND(DATA_LENGTH/1024/1024, 2) as data_mb,
    ROUND(INDEX_LENGTH/1024/1024, 2) as index_mb,
    ROUND(DATA_FREE/1024/1024, 2) as free_mb,
    ROUND((INDEX_LENGTH/DATA_LENGTH)*100, 2) as index_ratio_percent,
    UPDATE_TIME,
    CHECK_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND ENGINE = 'InnoDB'
ORDER BY DATA_LENGTH DESC;

-- ================================================================
-- FINAL VERIFICATION AND RECOMMENDATIONS
-- ================================================================

SELECT 'FINAL_VERIFICATION' as analysis_type;

-- Count total indexes created
SELECT COUNT(*) as total_custom_indexes
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME LIKE 'idx_%';

-- Show all custom indexes
SELECT 'CUSTOM_INDEXES_SUMMARY' as summary_type;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
    INDEX_TYPE,
    NON_UNIQUE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- Performance improvement recommendations
SELECT 'PERFORMANCE_RECOMMENDATIONS' as recommendation_type;
SELECT '1. Run ANALYZE TABLE monthly to update index statistics' as recommendation
UNION ALL
SELECT '2. Monitor query execution times and adjust indexes based on usage patterns'
UNION ALL
SELECT '3. Use EXPLAIN ANALYZE on slow queries to verify index usage'
UNION ALL
SELECT '4. Consider partitioning for tables with >1M rows'
UNION ALL
SELECT '5. Implement query result caching for frequently accessed data'
UNION ALL
SELECT '6. Monitor index selectivity - indexes with <10% selectivity may need review'
UNION ALL
SELECT '7. Use OPTIMIZE TABLE quarterly for heavily modified tables';

SELECT 'T018_ADDITIONAL_INDEX_OPTIMIZATION_COMPLETED' as task, NOW() as completion_time;

-- ================================================================
-- USAGE INSTRUCTIONS
-- ================================================================

/*
USAGE INSTRUCTIONS:

1. Execute this script as database administrator
2. Monitor the EXPLAIN FORMAT=JSON outputs to verify index usage
3. Run the OptimizeApplicationIndexes() procedure monthly:
   CALL OptimizeApplicationIndexes();
4. Monitor performance using the created views:
   SELECT * FROM v_index_performance;
   SELECT * FROM v_table_performance;
5. Check optimization logs:
   SELECT * FROM optimization_log ORDER BY created_at DESC;

MAINTENANCE SCHEDULE:
- Weekly: Check v_index_performance for selectivity issues
- Monthly: Run OptimizeApplicationIndexes() procedure
- Quarterly: Review and optimize based on query patterns
- Annually: Full performance audit and index review

INDEX MONITORING:
- Watch for unused indexes (low cardinality)
- Monitor query execution plans with EXPLAIN ANALYZE
- Track index usage through performance_schema (if enabled)
- Alert on index ratio > 50% (may indicate over-indexing)
*/
