# Project Status Report

Date: 2025-09-10

## Summary

Stability and schema alignment fixes applied to Purchase Invoices receive flow and routes. Invalid status usage and duplicate controller file removed. Started Task 2 to address Env::get default type mismatches.

## Changes Completed

1) Routes normalization (public/index.php)
- Removed duplicate route for creating PIs: kept `POST /purchaseinvoices/create-from-po` → `purchaseinvoicescontroller@createfrompo`.
- Standardized receive route: `POST /purchaseinvoices/receive` → `purchaseinvoicescontroller@receive`.
- Removed redundant alias `POST /receipts` that pointed to the same method (canonical is `/purchaseinvoices/receive`).

2) PurchaseInvoicesController fixes (app/controllers/purchaseinvoicescontroller.php)
- Eliminated duplicate receive() implementation and unified on one schema-aligned method.
- Insert receipts into live table `receipts` instead of non-existent `purchase_receipts`.
- Inventory ledger doc_type set to `'receipt'` (valid live enum) instead of `'po_receive'`.
- Updated PO status logic to use only live enum values (`ordered` or `received`).
- Fixed PI creation: initial status set to `'unpaid'` (valid live enum) instead of `'open'`.

3) Removed stray duplicate file
- Deleted `app/controllers/purchaseinvoicescontroller - Copy.php` to avoid confusion.

## Impact

- Resolves fatal redeclaration error for receive() and prevents invalid ledger doc_type writes.
- Ensures new PIs adhere to live enum for status.
- Reduces route duplication and potential confusion in clients/links.

## Next Steps (Task 2: Env defaults)

- Update SessionManager to pass string defaults to `Env::get` and cast afterward (prevents TypeError in logs).
- Scan for other numeric defaults across the app and align.

## Risks / Mitigations

- Route alias removal: If external clients post to `/receipts`, they must switch to `/purchaseinvoices/receive`. If keeping compatibility is required, we can add a lightweight redirect handler instead of removal.
- PO status logic simplified: If business wants granular partial status, the DB enum must be extended first.

