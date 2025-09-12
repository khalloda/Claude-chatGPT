# Bug Fix Tasks Tracker

This document tracks issues, analysis, and fixes discovered during testing. The authoritative live database schema is `chatgpt2_mi.sql`; all database impact notes reference it as the baseline.

## Table of Contents

- [Pending Issues](#pending-issues)
- [ISSUE-0001: Quote allows quantity exceeding stock](#issue-0001-quote-allows-quantity-exceeding-stock)
- [ISSUE-0002: Sales Order lacks stock validation (Suspicion)](#issue-0002-sales-order-lacks-stock-validation-suspicion)
- [ISSUE-0003: Available column shows 0 in Quote form](#issue-0003-available-column-shows-0-in-quote-form)
- [ISSUE-0007: Customer Statement — empty payment ref, no links, date filter ignored](#issue-0007-customer-statement-—-empty-payment-ref-no-links-date-filter-ignored)
- [ISSUE-0008: Supplier Statement — empty payment ref, no links, date filter parity](#issue-0008-supplier-statement-—-empty-payment-ref-no-links-date-filter-parity)
 - [ISSUE-0009: Reports routes 404 — sales and purchasing](#issue-0009-reports-routes-404-—-sales-and-purchasing)

## Pending Issues

No open issues without active work. See logged items below.

### ISSUE-0001: Quote allows quantity exceeding stock

- Issue ID & Title: ISSUE-0001 â€” Quote allows quantity exceeding stock
- Description: Creating Quote `Q2025-0010` accepted an item where requested quantity exceeds available stock. Example: Product "Coil" (product_id 2), Warehouse 2 had low stock but the quote line accepted quantity 18.
- Reproduction Steps:
  - Go to Create Quote, add item for product "Coil" in Warehouse 2.
  - Enter quantity 18.
  - Save. Quote is created without validation error.
- Suspected Location: `app/controllers/quotescontroller.php:49` in `store()` â€” items inserted without stock check.
- Severity: Major
- Status: Needs Verification
- Root Cause Analysis:
  - Server-side validation is missing in `QuotesController::store()` to verify per-warehouse availability before inserting `quote_items`.
  - Data model provides `product_stocks (qty_on_hand, qty_reserved)` but quote creation does not reference it.
  - Baseline evidence in DB: `quote_items` for `quote_id=14` contains `(product_id=2, warehouse_id=2, qty=18)` while `product_stocks` shows low on-hand for the same pair; this mismatch demonstrates the missing guard.
- Proposed Fix:
  - Add a stock availability check in `QuotesController::store()` that aggregates requested quantities by `(product_id, warehouse_id)` and compares to `available = GREATEST(qty_on_hand - qty_reserved, 0)` from `product_stocks`. If any shortfall, abort save with a clear, human-readable error per offending line including product and warehouse names.
  - Implemented changes:
    - Server-side validation with names via join on `products` and `warehouses` â€” see `app/controllers/quotescontroller.php` (validation block around line ~63).
    - UI enhancement on quote form: added an â€œAvailableâ€ column that live-updates when product or warehouse is selected, and highlights the row if `Qty > Available`. Endpoint `GET /stock/available` returns JSON. Files: `app/views/quotes/form.php`, `app/controllers/stockcontroller.php`, and route in `public/index.php`.
- Database/Migration Impact:
  - No schema changes required. The authoritative baseline `chatgpt2_mi.sql` already defines `product_stocks (qty_on_hand, qty_reserved)`.
  - Note: MySQL cannot enforce cross-table availability constraints with a native FK/CHECK, so application-level validation is appropriate. Pending migrations (if any) that alter stock semantics should be reviewed before enabling reservations on quotes. TODO: Revisit reserving stock upon quote acceptance as a future enhancement.
- Related Tests:
  - Unit/Integration: Controller flow test that simulates POST `/quotes` with `(product_id=2, warehouse_id=2, qty=18)` and expects failure flash message and redirect.
  - E2E (Playwright): Fill the quote form with the above parameters; assert validation error appears and the quote is not created.
  - Boundary: Test when `qty` equals available (passes) and `available=0` (fails).
  - UI: Playwright test that selects a product+warehouse and asserts the â€œAvailableâ€ column populates; then set quantity above available and assert row gets highlighted.

Related Issues: TODO

### ISSUE-0002: Sales Order lacks stock validation (Suspicion)

- Issue ID & Title: ISSUE-0002 â€” Sales Order lacks stock validation
- Description: Sales order creation (`/orders` store) and conversion from quote copy line items without checking available stock per warehouse. This may allow over-commit beyond inventory.
- Suspected Location: `app/controllers/orderscontroller.php:35` (`store()`), `app/controllers/orderscontroller.php:72` (`createfromquote()`), and `app/controllers/quotescontroller.php:201` (`createorder()` path also copies without checks).
- Severity: Major
- Status: Suspicion
- Root Cause Analysis: Similar to quotes, insertion of `sales_order_items` is done without referencing `product_stocks (qty_on_hand, qty_reserved)`.
- Proposed Fix: Mirror the availability check added for quotes; optionally adjust `qty_reserved` upon SO creation or acceptance to hold stock.
- Database/Migration Impact: None on baseline (`chatgpt2_mi.sql`). Future enhancement could define a business rule for when to reserve (on SO creation or approval) and consistently update `qty_reserved`.
- Related Tests: Add controller/integration tests and E2E ensuring SO creation fails when quantities exceed available per warehouse.

### ISSUE-0003: Available column shows 0 in Quote form

- Issue ID & Title: ISSUE-0003 â€” Available column shows 0 in Quote form
- Description: The live â€œAvailableâ€ column in the quote form always shows 0 even when stock exists.
- Suspected Location: Frontend JS in `app/views/quotes/form.php` fetching `/stock/available`; API in `app/controllers/stockcontroller.php`.
- Severity: Minor
- Status: Closed
- Root Cause Analysis:
  - Two issues combined:
    1) The route registration for `/stock/available` was appended after router dispatch in `public/index.php`, so the route wasnâ€™t active at runtime, leading to 404 for the endpoint.
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
- ### ISSUE-0004: Quote tax not showing in view/print

- Issue ID & Title: ISSUE-0004 â€” Quote tax not showing in view/print
- Description: After creating a quote with a non-zero Tax %, the quote view/print shows tax as zero or not visible.
- Reproduction Steps:
  - Create a quote with `Tax % = 10` and at least one item.
  - Open quote view and print page.
  - Observed: tax not displayed correctly.
- Suspected Location:
  - `app/controllers/quotescontroller.php` (show/printpage totals preparation)
  - `app/views/quotes/view.php` and `app/views/quotes/print.php` (totals rendering)
- Severity: Minor
- Status: Needs Verification
- Root Cause Analysis:
  - Totals were rendered directly from persisted columns. In edge cases where totals were not persisted or zero, rendering displayed zeros.
  - Visibility/UX: Totals were inline text; easier to miss.
- Proposed Fix:
  - Compute derived totals in controller from line items when persisted totals are zero/missing, then pass a `summary` array to views.
  - Update both view and print templates to use `summary` and render a clear 3-row totals block.
- Implemented Fix:
  - Controller: Added defensive computation in `QuotesController::show()` and `printpage()`.
  - View: Updated `app/views/quotes/view.php` and `app/views/quotes/print.php` to show prominent totals using derived `summary`.
- Database/Migration Impact: None (read-only computation against `quote_items`). Baseline `chatgpt2_mi.sql` unchanged.
- Related Tests:
  - Integration: Create a quote with `Tax %=10` and verify view/print show correct tax/total even if DB totals are zero.
  - Unit (optional): Helper to compute totals from a list of line items.
- ### ISSUE-0005: Quote does not reserve stock when marked Sent

- Issue ID & Title: ISSUE-0005 â€” Quote does not reserve stock on Sent
- Description: Creating a quote for product "Sparks Plug" with Qty 3 and marking it as Sent leaves `product_stocks.qty_reserved` unchanged (Avail stays 8, Reserved 0).
- Suspected Location: `app/controllers/quotescontroller.php` for status transitions; reservation utilities in `app/models/product.php` (`adjustReserved`, `consumeFromReservation`).
- Severity: Major
- Status: Needs Verification
- Root Cause Analysis:
  - No reservation adjustments were performed when marking quotes as Sent, Cancelled, or Expired. Status changed without touching `product_stocks`.
  - Existing helpers in `Product` model were unused by quote flows.
- Implemented Fix:
  - `quotescontroller@marksent`: now rechecks availability vs (on_hand - reserved) and increments `qty_reserved` per `(product_id, warehouse_id)` atomically via `Product::adjustReserved(.., +qty)`. Wrapped in a DB transaction.
  - `quotescontroller@cancel`: if previous status was `sent`, releases reservations via `adjustReserved(.., -qty)`.
  - `quotescontroller@markexpired`: similarly releases reservations if previous status was `sent`.
  - Added logging (info/error) for reservation booking/release.
- Database/Migration Impact:
  - None; uses existing `product_stocks (qty_reserved)` from baseline `chatgpt2_mi.sql`.
- Related Tests:
  - Integration: Create quote with Qty N, mark as Sent â†’ assert `qty_reserved` increased by N. Then cancel/expire â†’ assert release.
  - Negative: Try to mark Sent when available < demand â†’ expect failure and no status change.
  - Conversion flow (Future): When converting to Sales Order, consider consuming reservation or transferring to SO; currently tracked separately (see ISSUE-0002 suspicion).

### ISSUE-0006: Split reservations (quote vs order) and delivery confirmation

- Issue ID & Title: ISSUE-0006 — Split reservations (quote vs order) with delivery confirmation
- Description: Business asks to track reservations separately for Quotes and Orders. When converting a Quote to Order, move the reserved quantity from “for quotes” to “for order”. On delivery confirmation from the related Invoice, release the “for order” reservation and reduce stock on hand.
- Suspected Location: app/controllers/quotescontroller.php, app/controllers/invoicescontroller.php, app/models/product.php, stock API.
- Severity: Major
- Status: Needs Verification
- Root Cause Analysis:
  - Prior model only had a single qty_reserved column and did not change reservations on conversion or delivery.
- Proposed/Implemented Fix:
  - DB Migration (pending): scripts/2025-09-11_split_reservations.sql adds qty_reserved_quote and qty_reserved_order. Optional step migrates legacy qty_reserved to order bucket.
  - Model: detection + helpers (availableQty, adjustReservedQuote, adjustReservedOrder, transferReserveQuoteToOrder, consumeFromOrderReservation, reservedOrderQty) with legacy fallback.
  - Controllers: Quotes reserve/release quote bucket; convert transfers quote?order; Invoices confirm-delivery consumes order reservations.
  - Stock API: includes split fields when present and computes available accordingly.
- Database/Migration Impact: Requires applying the migration; without it, app falls back to legacy behavior using qty_reserved.
- Related Tests:
  - Mark Sent reserves quote bucket; Cancel/Expire releases it.
  - Convert Quote?Order transfers to order bucket.
  - Confirm Delivered from Invoice reduces order bucket and on-hand.

### ISSUE-0007: Customer Statement — empty payment ref, no links, date filter ignored

- Issue ID & Title: ISSUE-0007 — Customer Statement: empty payment ref, no linkable refs, and date filter ignored
- Description: On the Customer Statement page, the Payment Ref column renders blank for payments with no manual reference, the Ref values are not clickable, and transactions outside the selected date range are shown. Screenshot evidence shows rows from 2025-08-29 and 2025-08-30 when filtering 2025-09-01 to 2025-09-12, with empty Payment Ref.
- Suspected Location: `app/controllers/customerscontroller.php:statement()` — the "Final safety" fallback builds an unbounded list without date filters and omits `ref_id`/`invoice_id` and payment ref fallback; upstream exceptions from `App\Services\CustomerAging::getCustomerStatement()` may trigger this path.
- Severity: Major
- Status: Closed
- Root Cause Analysis:
  - The controller’s final fallback intentionally ignored date filters and selected recent transactions for the customer. It also selected payment `ref_no` as `p.reference` directly (which can be empty) and did not include `ref_id`/`invoice_id`, preventing links in the view.
  - When the optimized service throws or returns no rows (e.g., environment/date driver quirks), the controller enters this fallback, yielding the observed behavior: out-of-range rows, blank payment refs, and non-clickable refs.
- Proposed Fix:
  - Update the controller fallback to still respect the selected date range and to normalize columns to match the service output: `txn_date, kind, ref_no, debit, credit, ref_id, invoice_id`.
  - For payments, use `COALESCE(NULLIF(p.reference,''), CONCAT('PMT', LPAD(p.id,6,'0')))` so Payment Ref always shows a value when user reference is absent.
  - Keep links working in the view via existing logic using `ref_id`/`invoice_id`.
- Implemented Fix:
  - Patched `app/controllers/customerscontroller.php` final safety block to:
    - Apply `from/to` filters (`[from 00:00:00, to +1 day)`),
    - Include `ref_id` and `invoice_id` for linkability,
    - Use the payment ref fallback expression for non-empty display,
    - Sort by `txn_date` and compute running balance.
  - No change required in the view; it already renders links when IDs are present and uses `ref_no`.
- Database/Migration Impact: None. The fix only adjusts controller queries and output normalization; `chatgpt2_mi.sql` remains authoritative and unchanged.
- Related Tests:
  - Integration: GET `/customers/statement?id={id}&from=2025-09-01&to=2025-09-12` returns only transactions with `txn_date` in range.
  - Integration: Ensure a payment without `reference` renders `PMT{ID}` in Ref.
  - UI/E2E: Verify Invoice, Payment (links to its invoice), and Return refs are clickable; verify no August rows appear for a September-only filter.
  - Regression: Simulate a thrown exception in `CustomerAging::getCustomerStatement` and assert the controller fallback still respects date range and linkability.

### ISSUE-0008: Supplier Statement — empty payment ref, no links, date filter parity

- Issue ID & Title: ISSUE-0008 — Supplier Statement: add linkable refs, non-empty payment refs, and ensure date-respecting behavior consistent with customer statement
- Description: Supplier statement mirrored the earlier customer statement issues: payment ref blank when user reference is missing, Ref column not clickable, and potential inconsistencies across sources. Needs parity with the fixed customer statement.
- Suspected Location: `app/models/supplier.php::apMovements()` and `app/views/suppliers/statement.php`.
- Severity: Major
- Status: Needs Verification
- Root Cause Analysis:
  - `apMovements()` returned `ref_no` directly from `reference` for supplier payments, which can be empty, and did not include `invoice_id`, preventing link context in the view.
  - The supplier statement view rendered plain text for Ref with no conditional anchors.
- Implemented Fix:
  - Model: Normalized `apMovements()` to return consistent columns (`txn_date, kind, ref_no, debit, credit, ref_id, invoice_id`).
    - Invoices: `COALESCE(pi_no, CAST(id AS CHAR))` and include `invoice_id = id`.
    - Payments: `COALESCE(NULLIF(reference,''), CONCAT('SP', LPAD(id,6,'0')))` and include `purchase_invoice_id AS invoice_id`.
    - Returns: include `purchase_invoice_id AS invoice_id`.
  - View: `app/views/suppliers/statement.php` now links:
    - Invoice refs to `/purchaseinvoices/show?id={ref_id}`.
    - Payment refs to `/purchaseinvoices/show?id={invoice_id}`.
    - Return refs to `/purchasereturns/print?id={ref_id}` (no show route available).
- Update 2025-09-12:
  - User reported error page when visiting `/suppliers/statement?id=1&from=2025-09-01&to=2025-09-12`.
  - Root cause: Accidental backslashes introduced at the start/end of SQL lines in `Supplier::apMovements()` caused a MySQL syntax error.
  - Patch applied: cleaned multi-line SQL strings (removed stray `\` characters) in `app/models/supplier.php`. Re-test pending.
- Database/Migration Impact: None. Read-only queries against existing tables (`purchase_invoices`, `supplier_payments`, `purchase_returns`). Baseline `chatgpt2_mi.sql` unchanged.
- Related Tests:
  - Integration: GET `/suppliers/statement?id={id}&from=YYYY-MM-DD&to=YYYY-MM-DD` returns rows with `txn_date` within the range.
  - Integration: A supplier payment with empty `reference` renders `SP{ID}`.
  - UI/E2E: Clicking Invoice, Payment, and Return refs navigates appropriately.
  - Regression: Ensure statement still computes running balance correctly with mixed transaction types.

### ISSUE-0009: Reports routes 404 — sales and purchasing

- Issue ID & Title: ISSUE-0009 — Reports endpoints return 404: `/reports/sales`, `/reports/purchasing`
- Description: Navigating to the Sales and Purchasing report URLs results in the 404 page. User provided failing URLs.
- Suspected Location: `public/index.php` (routes missing), `app/controllers/reportscontroller.php` (no corresponding actions), and absent view templates.
- Severity: Minor (navigation-level), elevates to Major if business requires these reports.
- Status: Closed
- Root Cause Analysis:
  - Router only registered `ap-aging`, `ar-aging`, and `inventory-valuation` report routes. There were no `sales` or `purchasing` routes or actions, hence 404.
- Implemented Fix:
  - Router: Added routes `GET /reports/sales` and `GET /reports/purchasing` in `public/index.php`.
  - Controller: Implemented `ReportsController::sales()` and `::purchasing()` that:
    - Accept `from`/`to` dates, apply `[from 00:00:00, to +1 day)` filtering.
    - Build a normalized, unioned transaction list with party names and linkable refs:
      - Sales: invoices, invoice payments, sales returns.
      - Purchasing: purchase invoices, supplier payments, purchase returns.
    - Compute simple totals (invoices, returns, payments) and net values.
  - Views: Added `app/views/reports/sales.php` and `app/views/reports/purchasing.php` with filters, totals, and linkable refs.
  - Resilience: Wrapped union queries in try/catch with logged errors and a DATE()-based fallback per table to avoid 500s if SQL compatibility issues arise. See `reportscontroller@sales()` and `@purchasing()`.
  - 2025-09-12: User confirmed both reports now load — marking Closed.
- Database/Migration Impact: None. Read-only queries against baseline tables. `chatgpt2_mi.sql` unchanged.
- Related Tests:
  - Integration: Open `/reports/sales?from=YYYY-MM-01&to=YYYY-MM-DD` and `/reports/purchasing?...`; assert 200 OK and presence of totals.
  - UI/E2E: Click refs to verify navigation: sales → invoices/show, payments → invoices/show, returns → salesreturns/show; purchasing → purchaseinvoices/show or purchasereturns/print.
  - Data check: Totals equal sums of listed rows (debit, credit) per kind; net = invoices - returns.

