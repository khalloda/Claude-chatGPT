-- Drop duplicate unique indexes on product_stocks, keeping only the PRIMARY KEY (product_id, warehouse_id)
-- Safe pattern: check existence in information_schema before dropping

SET @schema := DATABASE();

-- Drop ux_product_warehouse if exists
SELECT COUNT(*) INTO @has_ux
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='product_stocks' AND INDEX_NAME='ux_product_warehouse';

SET @sql := IF(@has_ux>0, 'DROP INDEX ux_product_warehouse ON product_stocks', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop uq_prod_wh if exists
SELECT COUNT(*) INTO @has_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='product_stocks' AND INDEX_NAME='uq_prod_wh';

SET @sql := IF(@has_uq>0, 'DROP INDEX uq_prod_wh ON product_stocks', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verify remaining indexes
SHOW INDEX FROM product_stocks;

