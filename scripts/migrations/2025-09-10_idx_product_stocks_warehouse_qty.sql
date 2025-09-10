-- Index to speed stock lookup by warehouse and quantity
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='product_stocks' AND INDEX_NAME='idx_product_stocks_warehouse_qty';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_product_stocks_warehouse_qty ON product_stocks(warehouse_id, qty_on_hand)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

