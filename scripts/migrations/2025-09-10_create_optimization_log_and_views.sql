-- Optional monitoring objects: optimization_log table and performance views

-- Create optimization_log table if missing
CREATE TABLE IF NOT EXISTS optimization_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(50) NOT NULL,
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_event_type (event_type)
) ENGINE=InnoDB;

-- View: index performance (safe to replace)
CREATE OR REPLACE VIEW v_index_performance AS
SELECT 
  s.TABLE_NAME,
  s.INDEX_NAME,
  s.CARDINALITY,
  t.TABLE_ROWS,
  ROUND(CASE WHEN t.TABLE_ROWS > 0 THEN s.CARDINALITY / t.TABLE_ROWS * 100 ELSE 0 END, 2) AS selectivity_percent,
  CASE 
    WHEN t.TABLE_ROWS = 0 THEN 'No rows'
    WHEN s.CARDINALITY / t.TABLE_ROWS > 0.8 THEN 'High selectivity'
    WHEN s.CARDINALITY / t.TABLE_ROWS > 0.5 THEN 'Medium selectivity'
    WHEN s.CARDINALITY / t.TABLE_ROWS > 0.1 THEN 'Low selectivity'
    ELSE 'Very low selectivity'
  END AS selectivity_rating
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t ON t.TABLE_NAME = s.TABLE_NAME AND t.TABLE_SCHEMA = s.TABLE_SCHEMA
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.INDEX_NAME <> 'PRIMARY'
ORDER BY s.TABLE_NAME, selectivity_percent DESC;

-- View: table performance (safe to replace)
CREATE OR REPLACE VIEW v_table_performance AS
SELECT 
  TABLE_NAME,
  ENGINE,
  TABLE_ROWS,
  ROUND(DATA_LENGTH/1024/1024, 2) AS data_mb,
  ROUND(INDEX_LENGTH/1024/1024, 2) AS index_mb,
  ROUND(DATA_FREE/1024/1024, 2) AS free_mb,
  ROUND(CASE WHEN DATA_LENGTH > 0 THEN (INDEX_LENGTH/DATA_LENGTH)*100 ELSE 0 END, 2) AS index_ratio_percent,
  UPDATE_TIME,
  CHECK_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND ENGINE = 'InnoDB'
ORDER BY DATA_LENGTH DESC;

