-- Index to improve activity_log filtering and reporting
SET @schema := DATABASE();
SELECT COUNT(*) INTO @exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='activity_log' AND INDEX_NAME='idx_activity_log_entity_action_date';
SET @sql := IF(@exists=0,
  'CREATE INDEX idx_activity_log_entity_action_date ON activity_log(entity_type, entity_id, action, created_at)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

