-- Index to improve COGS entries queries by invoice/product/date
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='cogs_entries' AND INDEX_NAME='idx_cogs_entries_invoice_product';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_cogs_entries_invoice_product ON cogs_entries(invoice_id, product_id, created_at)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

