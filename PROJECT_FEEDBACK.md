# Project Feedback & Recommendations

## Executive Summary

- The codebase is a clean, lightweight PHP MVC app with clear routes and models, good CSRF coverage, centralized validation, and practical logging. The live schema is coherent for a small ERP flow.
- Pending SQL adds strong index coverage and useful monitoring artifacts, but contains naming mismatches and environment assumptions that must be resolved before use.
- Biggest risks: schema drift vs. SQL scripts (naming conflicts), missing FKs for data integrity, lack of CI/migration automation, and security hardening not applied to the actual DB name.

## Top Risks & Gaps

- Schema conflicts: `scripts/additional_database_indexes.sql` references `invoice_lines` and `invoice_payments.payment_date`; live has `invoice_items` and `paid_at`.
- Data integrity: Many logical FKs aren’t declared (e.g., `invoices.customer_id`, AP tables). Orphans possible; integrity enforced at code level only.
- Redundant keys: `product_stocks` defines multiple unique keys over the same columns. Risk of confusion and overhead.
- Migration safety: No established, automated migration workflow. Index builds can lock; procedures/views add permissions. Rollbacks not defined.
- Security hardening: `database_security_setup.sql` targets `spare_parts_db`; not parameterized to `chatgpt2_mi`. Default passwords listed; SSL assumptions; requires review.
- Observability: No metrics/tracing; logs exist but no alerts/dashboards. CI lacks tests/run‑books.

## Quick Wins (1–2 weeks)

- Fix index script mismatches:
  - Replace `invoice_lines` with `invoice_items`.
  - Replace `invoice_payments.payment_date` with `paid_at`.
- Consolidate `product_stocks` unique constraints to a single `PRIMARY KEY (product_id, warehouse_id)`; drop duplicates `ux_product_warehouse`, `uq_prod_wh`.
- Add composite indexes per pending scripts that match current schema: products(category_id,make_id,model_id), invoices(customer_id,created_at,status), product_stocks(warehouse_id,qty_on_hand), inventory_ledger(product_id,created_at), activity_log(entity_type,entity_id,action,created_at), purchase_invoices(supplier_id,created_at,status), cogs_entries(invoice_id,product_id,created_at), products(name,code), customers(name,email).
- Parameterize and dry‑run security script against a dev DB named `chatgpt2_mi`; remove default passwords; do not run GRANTs unattended.
- Add a minimal migration runner script with order, checkpoints, and logging.

## Medium-Term (1–2 months)

- Gradually add foreign keys with pre‑checks and data cleanup (start with safest: `invoice_items.invoice_id`, already present; next add `invoices.customer_id`, AP tables). Add `ON DELETE`/`ON UPDATE` rules consistent with app behavior.
- Introduce an automated CI workflow: syntax checks, static analysis, security scans, and a migration dry‑run job against disposable DB.
- Add Playwright tests for core flows (login, product search, quote→order→invoice, receive PO, payments, returns).
- Add request/DB metrics (simple Prometheus exporter or logs→dashboard) with slow query thresholds and index usage snapshots.
- Implement authorization checks by role per route; centralize middleware.

## Long-Term Roadmap

- Consider introducing a migration framework (Phinx/Laravel migrations or custom with version table) and retire ad‑hoc SQL drop‑ins.
- Implement event/audit logging to `database_audit_log`/`security_audit_log` with rotation (if adopting security script); wire app‑level emitters.
- Add background jobs for nightly index/statistics maintenance and report materialization.
- Evaluate partitioning for high‑volume tables (`inventory_ledger`, `activity_log`) if growth warrants.

## Database Migration Impact Report

### Ordered Migration Plan

1) Pre‑flight checks
- Verify DB: `select database() = 'chatgpt2_mi'`.
- Validate schema matches live dump (column/table presence for `invoice_items`, `invoice_payments.paid_at`).
- Capture backups and enable slow query log for observation.

2) Non‑destructive indexes (safe first)
- Create composite indexes that match current schema:
  - `products(category_id, make_id, model_id)`
  - `invoices(customer_id, created_at, status)`
  - `product_stocks(warehouse_id, qty_on_hand)`
  - `inventory_ledger(product_id, created_at)`
  - `purchase_invoices(supplier_id, created_at, status)`
  - `activity_log(entity_type, entity_id, action, created_at)`
  - `cogs_entries(invoice_id, product_id, created_at)`
  - `products(name, code)`
  - `customers(name, email)`

3) Monitoring tables and views
- Create `optimization_log` (if desired) and views `v_index_performance`, `v_table_performance`.
- Create `performance_test_results` table (optional but useful for benchmarks).

4) Constraint cleanup
- Drop duplicate unique keys on `product_stocks`, keeping only the PK.

5) Optional: Audit/security artifacts
- If adopting, adapt `database_security_setup.sql` to `chatgpt2_mi` and generate strong passwords provided from secret store; apply in a controlled environment. Create audit tables and procedures.

6) FK adoption (phased)
- For each proposed FK: run orphan checks, backfill orphans, then `ALTER TABLE … ADD CONSTRAINT` with appropriate cascade rules. Start with read‑only periods or online DDL.

### Pre‑Checks & Backfills

- Orphan checks example:
  - `SELECT i.customer_id FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE c.id IS NULL;`
  - Similar checks for AP tables and stock movement references.
- Data backfills if performing renames (only if the business wants `invoice_lines` rename): create view aliasing `invoice_items` as `invoice_lines` first; update code; then do table rename.

### Downtime Expectations

- Index creation: Use off‑peak windows; with MySQL 8, many secondary indexes can be created with minimal blocking (`ALGORITHM=INPLACE`), but verify.
- Constraint adds: Short metadata locks; avoid during peak writes.
- Security user changes: No app downtime if not rotating app credentials.

### Rollback Plans & Safeguards

- For indexes: `DROP INDEX IF EXISTS` to revert.
- For schema objects (views/tables): create under feature flag; rollback by dropping objects; do not drop data tables in rollback.
- For FK adoption: keep a feature toggle to ignore FK errors (app level) while validating in staging; rollback by dropping FK constraints.
- Always keep current full backup and be ready to restore.

## Docs/Automation Improvements

- Add `scripts/migrate.sh` (or PHP CLI script) to:
  - Detect current schema, apply pending SQL in order with guards.
  - Validate post‑conditions (indexes exist; objects created).
  - Record a migration ledger table (version, checksum, applied_at).
- Add CI checks to ensure SQL scripts reference existing tables/columns for the target DB (`mysql --no-exec` syntax check, and information_schema validation in a temp DB).
- Generate ERD snapshots from live dump automatically on release.

## Appendix — Concrete SQL Adjustments

- In `scripts/additional_database_indexes.sql`:
  - Replace occurrences of `invoice_lines` with `invoice_items`.
  - Replace `payment_date` with `paid_at` and remove `DESC` in index column list (MySQL ignores per‑column sort direction in BTREE indexes prior to 8.0.13’s functional nuance; keep simple: `(invoice_id, paid_at)`).
- In `scripts/database_security_setup.sql`:
  - Replace `SET @db_name = 'spare_parts_db'; USE spare_parts_db;` with `USE chatgpt2_mi;` or parameterize via `SET @db_name = DATABASE();` then `PREPARE` statements.
- In `product_stocks` schema:
  - Drop duplicates: `DROP INDEX ux_product_warehouse ON product_stocks; DROP INDEX uq_prod_wh ON product_stocks;` (keeping PK).

---

If you’d like, I can prepare a PR to (a) adjust the SQL scripts safely for the current schema, and (b) add a minimal migration runner + CI job to validate migrations on each push.

## Update — 2025-09-10

- Defect fixes:
  - PI numbering corrected (suffix extraction) + retry on duplicate.
  - Receiving flow supports both PO-item and product/warehouse arrays; stock and receipts now update correctly.
  - CSRF/session reliability improved (helpers ensure active session).
- Implementations:
  - File sessions in prod Windows Plesk, `/health` endpoint, dashboard badges.
  - Migration runner & CI, idempotent index migrations, verification report migration.
- Recommendations (next):
  - Add authorization middleware, smoke tests, and a central `doc_sequences` approach for all document numbers to avoid race conditions entirely.
