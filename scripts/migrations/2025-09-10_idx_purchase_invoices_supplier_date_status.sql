-- Index to accelerate supplier invoice reports by date and status
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='purchase_invoices' AND INDEX_NAME='idx_purchase_invoices_supplier_date_status';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_purchase_invoices_supplier_date_status ON purchase_invoices(supplier_id, created_at, status)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

