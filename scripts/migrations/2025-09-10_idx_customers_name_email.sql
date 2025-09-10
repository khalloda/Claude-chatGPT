-- Index to improve customer search by name/email
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='customers' AND INDEX_NAME='idx_customers_name_email';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_customers_name_email ON customers(name, email)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

