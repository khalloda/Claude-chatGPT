-- Index to accelerate inventory ledger history lookups by product and date
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='inventory_ledger' AND INDEX_NAME='idx_inventory_ledger_product_date';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_inventory_ledger_product_date ON inventory_ledger(product_id, created_at)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

