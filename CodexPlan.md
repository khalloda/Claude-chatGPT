# Codex Remediation & Improvement Plan

## Objectives

- Restore application stability by fixing runtime errors from logs.
- Align code with the live database schema to prevent data/enum mismatches.
- Improve performance via safe index additions and query fixes already drafted under `scripts/`.
- Strengthen security (sessions, secrets, DB users) and error handling.
- Establish a lightweight migration and CI process.

## Snapshot Of Key Findings

- Duplicate `receive()` methods in `app/controllers/purchaseinvoicescontroller.php` cause fatal redeclaration errors; one method writes to a non‑existent table `purchase_receipts` and wrong enum value `po_receive`.
- `Env::get()` signature requires `?string` default; several callers (e.g., `SessionManager`) pass numeric defaults → TypeError in logs.
- Ternary precedence issue reported in `healthcontroller.php` (legacy path); review for nested ternaries across code.
- Route/controller naming inconsistencies and duplicates (case sensitivity and redundant definitions) in `public/index.php`.
- Live schema enum `inventory_ledger.doc_type` doesn’t include `po_receive` that controller writes currently.
- Pending SQL scripts reference tables/columns that don’t exist in live DB (`invoice_lines` vs `invoice_items`, `payment_date` vs `paid_at`).
- `product_stocks` has duplicate unique keys over `(product_id, warehouse_id)` in live schema.
- `config/.env` ships with `APP_DEBUG=true` and weak DB credentials.

## Phase 1 — Hotfixes (Stability & Correctness)

1) Purchase Invoices receive flow
- Remove duplicated `receive()`; keep single implementation aligned to live schema: write to `receipts`, use enum `receipt`, and existing columns.
- Validate caps vs ordered qty and received aggregation against `purchase_order_items.received_qty`.

2) Env type compatibility
- Update all numeric default Env::get() usages to pass strings (e.g., `'7200'`) OR generalize `Env::get` to accept `mixed $default` and cast to string internally.
- Adjust `SessionManager` (`app/services/SessionManager.php:48`) to avoid int default; ensure proper casting after read.

3) Routing cleanup
- Normalize controller route strings (consistent casing) and remove duplicated definitions for the same action.

4) Enum mismatch
- Ensure any ledger insert uses `doc_type='receipt'` (not `po_receive`).

5) Error‑prone ternary patterns
- Search and parenthesize nested ternaries; add lints/tests for the patterns.

## Phase 2 — Schema & Migration Alignment

6) Align pending SQL scripts
- In `scripts/additional_database_indexes.sql`: replace `invoice_lines`→`invoice_items`, `payment_date`→`paid_at`.
- Parameterize `scripts/database_security_setup.sql` to current DB (`chatgpt2_mi`) and remove default passwords.
- Drop redundant unique keys from `product_stocks`, keeping only the PK.

7) Safe index rollout (off‑peak)
- Create composite indexes described in TECHNICAL_REFERENCE.md (products, invoices, product_stocks, inventory_ledger, purchase_invoices, activity_log, cogs_entries, products name+code, customers name+email).

## Phase 3 — Security & Observability

8) Secrets & runtime
- Set `APP_DEBUG=false` in production; rotate DB creds; restrict `config/.env` permissions.
- Validate Redis TLS/auth if used; minimize allowed Redis commands in prod.

9) Session hardening
- Ensure cookie flags (Secure/HttpOnly/SameSite) configured; session rotation on privilege change; idle timeout enforced by `SessionManager`.

10) Logging & monitoring
- Add slow query threshold and periodic `ANALYZE TABLE`; enable performance views from scripts.
- Ensure logs don’t capture sensitive payloads.

## Phase 4 — Testing & CI/CD

11) Minimal migration runner
- Add a PHP/SQL runner to apply pending migrations with pre/post checks and a migrations ledger table.

12) CI pipeline
- Add GitHub Actions: syntax checks, basic unit tests, migration dry‑run on ephemeral DB, and static checks for SQL/table naming mismatches.

13) E2E sanity
- Add a few Playwright or headless browser tests: login, product search, PO→PI receive, invoice payment.

## Phase 5 — Medium‑Term Improvements

14) Foreign keys (phased)
- Add FKs with orphan checks for key relations (invoices→customers, purchase/sales flows) to shift integrity to DB.

15) Authorization layer
- Add per‑route authorization based on `users.role`; centralize in middleware.

16) Performance hygiene
- Consider covering indexes for top N queries; cache hot reads; consider summary tables for inventory valuation.

## Success Criteria

- No fatal errors in logs during normal use; receive flow works and persists receipts + inventory changes.
- Session initialization no longer throws TypeError; routes are de‑duplicated and consistent.
- Indexes present and used by plans; performance scripts complete without errors.
- Security posture improved (debug off, creds rotated, environment consistent).
- CI validates migrations and basic flows on every push.

