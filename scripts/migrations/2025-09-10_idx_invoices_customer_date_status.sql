-- Index to accelerate customer invoice lookups by date and status
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='invoices' AND INDEX_NAME='idx_invoices_customer_date_status';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_invoices_customer_date_status ON invoices(customer_id, created_at, status)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

