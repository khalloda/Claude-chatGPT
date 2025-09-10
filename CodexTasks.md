# Codex Detailed Tasks

## 1) Fix PurchaseInvoicesController::receive() redeclaration and schema mismatches

- Files:
  - app/controllers/purchaseinvoicescontroller.php:1
  - public/index.php:1
- Actions:
  - Remove the second duplicate `receive()` method block. Keep a single method aligned with live schema: writes into `receipts` table; uses `inventory_ledger.doc_type='receipt'`.
  - Ensure no creation of non‑schema table `purchase_receipts` occurs.
  - Review helper `upsertStock()` to use valid enum values and correct table columns; if it writes to `inventory_ledger`, use `doc_type='receipt'` and existing columns only.
  - In `public/index.php`, make route controller strings consistent (prefer lowercase file/class casing used elsewhere): unify `PurchaseInvoicesController` vs `purchaseinvoicescontroller`, and remove redundant duplicate routes that point to the same action.
- Acceptance:
  - Navigating to `/purchaseinvoices` and submitting a receive form no longer triggers redeclare; receipts and stock updates persist; inventory ledger rows insert successfully.

## 2) Resolve Env::get default TypeError in SessionManager

- Files:
  - app/core/env.php:1
  - app/services/SessionManager.php:1
- Actions (pick one and apply consistently):
  - Preferred: Keep `Env::get(string, ?string)` and change all numeric defaults passed to it into strings, e.g., `Env::get('SESSION_LIFETIME', '7200')`; then cast `(int)` when using.
  - Alternative: Generalize Env::get signature to accept `mixed $default` and cast to string internally before returning; audit for unintended behavioral changes.
- Acceptance:
  - App boots without TypeError; session initializes; no CRITICAL log entries for Env type mismatch.

## 3) Parenthesize nested ternary expressions

- Files:
  - Search across `app/**` for `? ... : ... ? ... : ...` patterns and ambiguous ternaries.
- Actions:
  - Add explicit parentheses following PHP 8+ precedence rules; simplify where possible.
- Acceptance:
  - No error log entries about unparenthesized nested ternaries; static scan finds none.

## 4) Align pending SQL scripts to live schema

- Files:
  - scripts/additional_database_indexes.sql:1
  - scripts/database_index_optimization.sql:1
  - scripts/database_security_setup.sql:1
- Actions:
  - Replace `invoice_lines` → `invoice_items`.
  - Replace `invoice_payments.payment_date` → `invoice_payments.paid_at` and adjust index name accordingly.
  - Parameterize security script to current DB name (`chatgpt2_mi`) or set `SET @db_name = DATABASE();` and use prepared statements; remove default plaintext passwords and document secret injection.
- Acceptance:
  - Scripts execute cleanly on a staging DB cloned from live dump; indexes created; no missing table/column errors.

## 5) Drop duplicate unique indexes on product_stocks

- Files:
  - Live DB (migration step)
- Actions:
  - Generate migration SQL to drop `ux_product_warehouse` and `uq_prod_wh`, keeping only the PK `(product_id, warehouse_id)`.
- Acceptance:
  - `SHOW INDEX FROM product_stocks` shows single PRIMARY on `(product_id,warehouse_id)` and no duplicate uniques.

## 6) Introduce safe composite indexes (off‑peak)

- Files:
  - scripts/database_index_optimization.sql:1 (source)
- Actions:
  - Apply: `idx_products_category_make_model`, `idx_invoices_customer_date_status`, `idx_product_stocks_warehouse_qty`, `idx_inventory_ledger_product_date`, `idx_purchase_invoices_supplier_date_status`, `idx_activity_log_entity_action_date`, `idx_cogs_entries_invoice_product`, `idx_products_name_code`, `idx_customers_name_email`.
- Acceptance:
  - Indexes present; EXPLAIN plans for hot queries utilize them.

## 7) Security hardening and secrets hygiene

- Files:
  - config/.env:1
  - config/.env.example:1
  - config/redis.php:1
  - scripts/database_security_setup.sql:1
- Actions:
  - Set `APP_DEBUG=false` in production; rotate DB credentials and remove weak defaults from committed `.env`.
  - Validate Redis settings (auth, TLS) and restrict command set if feasible; ensure session Redis uses separate DB and prefix.
  - If adopting DB security script, adapt DB name, ensure SSL availability, and supply passwords from secret store; do not commit secrets.
- Acceptance:
  - App runs with debug off; secrets not in repo; Redis authenticated; security script passes validation in staging.

## 8) Minimal migration runner and CI

- Files:
  - New: scripts/migrate.php (or scripts/migrate.sh)
  - .github/workflows: add a CI workflow file
- Actions:
  - Implement a migration runner that applies SQL files in deterministic order with a `schema_migrations` ledger table and pre/post checks.
  - CI: PHP lint, basic unit tests, run migration runner on ephemeral MySQL using the live dump snapshot; fail on missing tables/columns.
- Acceptance:
  - CI runs on PRs/commits; migration job passes; failures indicate exact SQL file/line.

## 9) Tests for critical flows

- Files:
  - tests/: extend with scenario scripts or adopt a framework
- Actions:
  - Add smoke tests for: login, product search, quote→order→invoice flow, PO receive, invoice payment, and printing pages.
- Acceptance:
  - Tests pass locally and in CI; regressions caught before deploy.

## 10) Optional: Foreign keys rollout (phased)

- Files:
  - Migration SQL
- Actions:
  - Pre‑check orphans (invoices→customers, purchase/sales links), clean orphans, then add FKs with appropriate cascade rules.
- Acceptance:
  - FKs added without violations; data integrity enforced at DB.

---

If you want, I can start by implementing Task 1 and Task 2 right away and submit a focused patch.

