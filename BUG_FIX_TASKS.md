# Bug Fix Tasks Tracker

This document tracks issues, analysis, and fixes discovered during testing. The authoritative live database schema is `chatgpt2_mi.sql`; all database impact notes reference it as the baseline.

## Table of Contents

- [Pending Issues](#pending-issues)
- [ISSUE-0001: Quote allows quantity exceeding stock](#issue-0001-quote-allows-quantity-exceeding-stock)
- [ISSUE-0002: Sales Order lacks stock validation (Suspicion)](#issue-0002-sales-order-lacks-stock-validation-suspicion)
- [ISSUE-0003: Available column shows 0 in Quote form](#issue-0003-available-column-shows-0-in-quote-form)

## Pending Issues

No open issues without active work. See logged items below.

### ISSUE-0001: Quote allows quantity exceeding stock

- Issue ID & Title: ISSUE-0001 — Quote allows quantity exceeding stock
- Description: Creating Quote `Q2025-0010` accepted an item where requested quantity exceeds available stock. Example: Product "Coil" (product_id 2), Warehouse 2 had low stock but the quote line accepted quantity 18.
- Reproduction Steps:
  - Go to Create Quote, add item for product "Coil" in Warehouse 2.
  - Enter quantity 18.
  - Save. Quote is created without validation error.
- Suspected Location: `app/controllers/quotescontroller.php:49` in `store()` — items inserted without stock check.
- Severity: Major
- Status: Needs Verification
- Root Cause Analysis:
  - Server-side validation is missing in `QuotesController::store()` to verify per-warehouse availability before inserting `quote_items`.
  - Data model provides `product_stocks (qty_on_hand, qty_reserved)` but quote creation does not reference it.
  - Baseline evidence in DB: `quote_items` for `quote_id=14` contains `(product_id=2, warehouse_id=2, qty=18)` while `product_stocks` shows low on-hand for the same pair; this mismatch demonstrates the missing guard.
- Proposed Fix:
  - Add a stock availability check in `QuotesController::store()` that aggregates requested quantities by `(product_id, warehouse_id)` and compares to `available = GREATEST(qty_on_hand - qty_reserved, 0)` from `product_stocks`. If any shortfall, abort save with a clear, human-readable error per offending line including product and warehouse names.
  - Implemented changes:
    - Server-side validation with names via join on `products` and `warehouses` — see `app/controllers/quotescontroller.php` (validation block around line ~63).
    - UI enhancement on quote form: added an “Available” column that live-updates when product or warehouse is selected, and highlights the row if `Qty > Available`. Endpoint `GET /stock/available` returns JSON. Files: `app/views/quotes/form.php`, `app/controllers/stockcontroller.php`, and route in `public/index.php`.
- Database/Migration Impact:
  - No schema changes required. The authoritative baseline `chatgpt2_mi.sql` already defines `product_stocks (qty_on_hand, qty_reserved)`.
  - Note: MySQL cannot enforce cross-table availability constraints with a native FK/CHECK, so application-level validation is appropriate. Pending migrations (if any) that alter stock semantics should be reviewed before enabling reservations on quotes. TODO: Revisit reserving stock upon quote acceptance as a future enhancement.
- Related Tests:
  - Unit/Integration: Controller flow test that simulates POST `/quotes` with `(product_id=2, warehouse_id=2, qty=18)` and expects failure flash message and redirect.
  - E2E (Playwright): Fill the quote form with the above parameters; assert validation error appears and the quote is not created.
  - Boundary: Test when `qty` equals available (passes) and `available=0` (fails).
  - UI: Playwright test that selects a product+warehouse and asserts the “Available” column populates; then set quantity above available and assert row gets highlighted.

Related Issues: TODO

### ISSUE-0002: Sales Order lacks stock validation (Suspicion)

- Issue ID & Title: ISSUE-0002 — Sales Order lacks stock validation
- Description: Sales order creation (`/orders` store) and conversion from quote copy line items without checking available stock per warehouse. This may allow over-commit beyond inventory.
- Suspected Location: `app/controllers/orderscontroller.php:35` (`store()`), `app/controllers/orderscontroller.php:72` (`createfromquote()`), and `app/controllers/quotescontroller.php:201` (`createorder()` path also copies without checks).
- Severity: Major
- Status: Suspicion
- Root Cause Analysis: Similar to quotes, insertion of `sales_order_items` is done without referencing `product_stocks (qty_on_hand, qty_reserved)`.
- Proposed Fix: Mirror the availability check added for quotes; optionally adjust `qty_reserved` upon SO creation or acceptance to hold stock.
- Database/Migration Impact: None on baseline (`chatgpt2_mi.sql`). Future enhancement could define a business rule for when to reserve (on SO creation or approval) and consistently update `qty_reserved`.
- Related Tests: Add controller/integration tests and E2E ensuring SO creation fails when quantities exceed available per warehouse.

### ISSUE-0003: Available column shows 0 in Quote form

- Issue ID & Title: ISSUE-0003 — Available column shows 0 in Quote form
- Description: The live “Available” column in the quote form always shows 0 even when stock exists.
- Suspected Location: Frontend JS in `app/views/quotes/form.php` fetching `/stock/available`; API in `app/controllers/stockcontroller.php`.
- Severity: Minor
- Status: Closed
- Root Cause Analysis:
  - Two issues combined:
    1) The route registration for `/stock/available` was appended after router dispatch in `public/index.php`, so the route wasn’t active at runtime, leading to 404 for the endpoint.
    2) Frontend fetch originally lacked credentials and cache handling, which could cause silent fallback. This was fixed earlier.
- Proposed Fix:
  - Add `credentials: 'same-origin'`, `cache: 'no-store'`, and content-type guard in fetch to ensure proper cookie sending and JSON handling.
  - Add `Cache-Control: no-store` to the endpoint; add a cache-busting query param.
  - Keep UI resilient; if fetch fails, UI shows 0 but server-side validation still prevents invalid saves.
- Implemented Changes:
  - Router: moved `GET /stock/available` registration before dispatch in `public/index.php` (route now active).
  - JS updated in `app/views/quotes/form.php` to include credentials, no-store cache, and JSON content-type check; added cache-busting.
  - API updated `app/controllers/stockcontroller.php` to disable caching and log warnings/errors; also logs DEBUG on successful responses to aid troubleshooting.
- Database/Migration Impact: None (reads from `product_stocks`). Baseline `chatgpt2_mi.sql` intact.
- Related Tests:
  - E2E: Select product+warehouse and assert Available > 0 for known stocked items; verify row highlight when exceeding available.
  - Integration: Hit `/stock/available?product_id=2&warehouse_id=1` while authenticated and assert JSON `{available: >0}`.
