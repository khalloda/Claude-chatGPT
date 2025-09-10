-- Verification report: expected custom indexes present and in correct column order
-- This script is read-only. It produces a report of missing or mismatched indexes.

WITH expected AS (
  SELECT 'products' AS table_name, 'idx_products_category_make_model' AS index_name, 'category_id,make_id,model_id' AS columns UNION ALL
  SELECT 'invoices', 'idx_invoices_customer_date_status', 'customer_id,created_at,status' UNION ALL
  SELECT 'product_stocks', 'idx_product_stocks_warehouse_qty', 'warehouse_id,qty_on_hand' UNION ALL
  SELECT 'inventory_ledger', 'idx_inventory_ledger_product_date', 'product_id,created_at' UNION ALL
  SELECT 'purchase_invoices', 'idx_purchase_invoices_supplier_date_status', 'supplier_id,created_at,status' UNION ALL
  SELECT 'activity_log', 'idx_activity_log_entity_action_date', 'entity_type,entity_id,action,created_at' UNION ALL
  SELECT 'cogs_entries', 'idx_cogs_entries_invoice_product', 'invoice_id,product_id,created_at' UNION ALL
  SELECT 'products', 'idx_products_name_code', 'name,code' UNION ALL
  SELECT 'customers', 'idx_customers_name_email', 'name,email'
),
actual AS (
  SELECT TABLE_NAME AS table_name,
         INDEX_NAME AS index_name,
         GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS columns
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
  GROUP BY TABLE_NAME, INDEX_NAME
)
SELECT e.table_name,
       e.index_name,
       e.columns          AS expected_columns,
       a.columns          AS actual_columns,
       CASE WHEN a.table_name IS NULL THEN 'MISSING'
            WHEN a.columns = e.columns THEN 'OK'
            ELSE 'MISMATCH' END AS status
FROM expected e
LEFT JOIN actual a
  ON a.table_name = e.table_name AND a.index_name = e.index_name
ORDER BY (status <> 'OK') DESC, e.table_name, e.index_name;

-- Summary counts
SELECT status, COUNT(*) AS count
FROM (
  WITH expected AS (
    SELECT 'products' AS table_name, 'idx_products_category_make_model' AS index_name, 'category_id,make_id,model_id' AS columns UNION ALL
    SELECT 'invoices', 'idx_invoices_customer_date_status', 'customer_id,created_at,status' UNION ALL
    SELECT 'product_stocks', 'idx_product_stocks_warehouse_qty', 'warehouse_id,qty_on_hand' UNION ALL
    SELECT 'inventory_ledger', 'idx_inventory_ledger_product_date', 'product_id,created_at' UNION ALL
    SELECT 'purchase_invoices', 'idx_purchase_invoices_supplier_date_status', 'supplier_id,created_at,status' UNION ALL
    SELECT 'activity_log', 'idx_activity_log_entity_action_date', 'entity_type,entity_id,action,created_at' UNION ALL
    SELECT 'cogs_entries', 'idx_cogs_entries_invoice_product', 'invoice_id,product_id,created_at' UNION ALL
    SELECT 'products', 'idx_products_name_code', 'name,code' UNION ALL
    SELECT 'customers', 'idx_customers_name_email', 'name,email'
  ),
  actual AS (
    SELECT TABLE_NAME AS table_name,
           INDEX_NAME AS index_name,
           GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS columns
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    GROUP BY TABLE_NAME, INDEX_NAME
  )
  SELECT CASE WHEN a.table_name IS NULL THEN 'MISSING'
              WHEN a.columns = e.columns THEN 'OK'
              ELSE 'MISMATCH' END AS status
  FROM expected e
  LEFT JOIN actual a
    ON a.table_name = e.table_name AND a.index_name = e.index_name
) t
GROUP BY status
ORDER BY FIELD(status,'MISSING','MISMATCH','OK');

