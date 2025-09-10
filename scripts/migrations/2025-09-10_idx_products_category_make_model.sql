-- Create composite index for product filtering by category/make/model
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='products' AND INDEX_NAME='idx_products_category_make_model';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_products_category_make_model ON products(category_id, make_id, model_id)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

