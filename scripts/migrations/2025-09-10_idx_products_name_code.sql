-- Index to improve product search by name/code
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='products' AND INDEX_NAME='idx_products_name_code';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_products_name_code ON products(name, code)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

