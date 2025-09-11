-- Pending Migration: Split reservations into quote/order buckets
-- Baseline: chatgpt2_mi.sql (do not edit directly). Apply this migration to add new columns.

ALTER TABLE product_stocks
  ADD COLUMN qty_reserved_quote INT UNSIGNED NOT NULL DEFAULT 0 AFTER qty_reserved,
  ADD COLUMN qty_reserved_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER qty_reserved_quote;

-- Optional data migration: move existing legacy reservations into order bucket
-- Uncomment if you want to migrate legacy qty_reserved into qty_reserved_order and reset legacy to 0
-- UPDATE product_stocks SET qty_reserved_order = qty_reserved, qty_reserved = 0;

-- Note: Application computes available as qty_on_hand - (qty_reserved_quote + qty_reserved_order) when these columns exist.
-- Legacy deployments without this migration will continue to use qty_reserved.

