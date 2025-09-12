# Current Database ERD

```mermaid
erDiagram
  activity_log {
    int unsigned id PK
    varchar(191) actor
    varchar(64) action
    varchar(64) entity_type
    int unsigned entity_id
    text meta
    timestamp created_at
  }

  categories {
    int unsigned id PK
    int unsigned parent_id FK
    varchar(191) name
    varchar(191) slug
    timestamp created_at
    timestamp updated_at
  }

  cogs_entries {
    int unsigned id PK
    int unsigned invoice_id
    int unsigned product_id
    int unsigned warehouse_id
    int qty
    decimal(12,4) unit_cost
    decimal(14,4) line_cost
    timestamp created_at
  }

  customers {
    int unsigned id PK
    varchar(191) name
    varchar(50) phone
    varchar(191) email
    text address
    timestamp created_at
    timestamp updated_at
  }

  doc_sequences {
    varchar(10) prefix PK
    int y PK
    int last_no
  }

  inventory_ledger {
    int unsigned id PK
    int unsigned product_id
    int unsigned warehouse_id
    enum('receipt','transfer_out','transfer_in','adjustment','sale','sales_return','purchase_return') doc_type
    int unsigned doc_id
    int qty_delta
    decimal(12,4) unit_cost
    decimal(14,4) value_delta
    timestamp created_at
  }

  invoice_items {
    int unsigned id PK
    int unsigned invoice_id FK
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  invoice_payments {
    int unsigned id PK
    int unsigned invoice_id FK
    datetime paid_at
    varchar(50) method
    varchar(191) reference
    decimal(12,2) amount
    varchar(255) note
    timestamp created_at
  }

  invoices {
    int unsigned id PK
    varchar(32) inv_no
    int unsigned sales_order_id
    int unsigned customer_id
    decimal(5,2) tax_rate
    decimal(12,2) subtotal
    decimal(12,2) tax_amount
    decimal(12,2) total
    decimal(12,2) paid_amount
    enum('unpaid','partial','paid','void') status
    timestamp created_at
    timestamp updated_at
    decimal(12,2) cogs_total
  }

  makes {
    int unsigned id PK
    varchar(191) name
    varchar(191) slug
    timestamp created_at
    timestamp updated_at
  }

  notes {
    int unsigned id PK
    enum('quote','sales_order','sales_invoice','purchase_order','purchase_invoice','customer','category','warehouse','product') entity_type
    int unsigned entity_id
    tinyint(1) is_public
    text body
    varchar(191) created_by
    int created_by_id
    timestamp created_at
  }

  product_stocks {
    int unsigned product_id PK FK
    int unsigned warehouse_id PK FK
    int unsigned qty_on_hand
    int unsigned qty_reserved
    decimal(12,4) avg_cost
  }

  products {
    int unsigned id PK
    varchar(20) code
    varchar(191) name
    int unsigned category_id FK
    int unsigned make_id FK
    int unsigned model_id FK
    decimal(12,2) cost
    decimal(12,2) price
    timestamp created_at
    timestamp updated_at
  }

  purchase_invoices {
    int unsigned id PK
    varchar(32) pi_no
    int unsigned purchase_order_id
    int unsigned supplier_id
    decimal(12,2) subtotal
    decimal(5,2) tax_rate
    decimal(12,2) tax_amount
    decimal(12,2) total
    timestamp created_at
    decimal(12,2) paid_amount
    enum('unpaid','partial','paid') status
  }

  purchase_order_items {
    int unsigned id PK
    int unsigned purchase_order_id
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(14,4) received_qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  purchase_orders {
    int unsigned id PK
    varchar(32) po_no
    int unsigned supplier_id
    enum('draft','ordered','received','closed') status
    decimal(5,2) tax_rate
    decimal(12,2) subtotal
    decimal(12,2) tax_amount
    decimal(12,2) total
    timestamp created_at
  }

  purchase_return_items {
    int unsigned id PK
    int unsigned purchase_return_id
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  purchase_returns {
    int unsigned id PK
    varchar(32) pr_no
    int unsigned purchase_invoice_id
    int unsigned supplier_id
    decimal(12,2) subtotal
    decimal(5,2) tax_rate
    decimal(12,2) tax_amount
    decimal(12,2) total
    timestamp created_at
  }

  quote_items {
    int unsigned id PK
    int unsigned quote_id FK
    int unsigned product_id FK
    int unsigned warehouse_id FK
    int unsigned qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  quotes {
    int unsigned id PK
    varchar(32) quote_no
    int unsigned customer_id FK
    varchar(20) status
    decimal(5,2) tax_rate
    decimal(12,2) subtotal
    decimal(12,2) tax_amount
    decimal(12,2) total
    date expires_at
    timestamp created_at
    timestamp updated_at
  }

  receipts {
    int unsigned id PK
    int unsigned purchase_invoice_id
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(12,2) price
    timestamp created_at
  }

  sales_order_items {
    int unsigned id PK
    int unsigned sales_order_id FK
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  sales_orders {
    int unsigned id PK
    varchar(32) so_no
    int unsigned quote_id FK
    int unsigned customer_id
    enum('open','closed','cancelled') status
    decimal(5,2) tax_rate
    decimal(12,2) subtotal
    decimal(12,2) tax_amount
    decimal(12,2) total
    timestamp created_at
    timestamp updated_at
  }

  sales_return_items {
    int unsigned id PK
    int unsigned sales_return_id
    int unsigned product_id
    int unsigned warehouse_id
    int unsigned qty
    decimal(12,2) price
    decimal(12,2) line_total
  }

  sales_returns {
    int unsigned id PK
    varchar(32) sr_no
    int unsigned sales_invoice_id
    int unsigned client_id
    decimal(12,2) subtotal
    decimal(5,2) tax_rate
    decimal(12,2) tax_amount
    decimal(12,2) total
    timestamp created_at
  }

  stock_adjustment_items {
    int unsigned id PK
    int unsigned stock_adjustment_id
    int unsigned product_id
    int qty_change
    timestamp created_at
  }

  stock_adjustments {
    int unsigned id PK
    varchar(32) adj_no
    int unsigned warehouse_id
    enum('count','damage','shrink','other') reason
    text note
    timestamp created_at
  }

  stock_transfer_items {
    int unsigned id PK
    int unsigned stock_transfer_id
    int unsigned product_id
    int unsigned qty
    timestamp created_at
  }

  stock_transfers {
    int unsigned id PK
    varchar(32) tr_no
    int unsigned from_warehouse_id
    int unsigned to_warehouse_id
    text note
    timestamp created_at
  }

  supplier_payments {
    int unsigned id PK
    int unsigned supplier_id
    int unsigned purchase_invoice_id
    datetime paid_at
    varchar(50) method
    varchar(64) reference
    decimal(12,2) amount
    text note
    timestamp created_at
  }

  suppliers {
    int unsigned id PK
    varchar(191) name
    varchar(50) phone
    varchar(191) email
    varchar(255) address
  }

  users {
    int unsigned id PK
    varchar(191) email
    varchar(255) password_hash
    varchar(20) role
    enum('active','inactive','suspended','pending') status
    datetime last_login_at
    timestamp updated_at
    timestamp created_at
  }

  vehicle_models {
    int unsigned id PK
    int unsigned make_id FK
    varchar(191) name
    varchar(191) slug
    timestamp created_at
    timestamp updated_at
  }

  warehouses {
    int unsigned id PK
    varchar(20) code
    varchar(191) name
    varchar(191) location
    timestamp created_at
    timestamp updated_at
  }

  %% NEW: User Management & RBAC Tables (September 2025)
  roles {
    int unsigned id PK
    varchar(100) name
    text description
    tinyint(1) is_active
    timestamp created_at
    timestamp updated_at
  }

  permissions {
    int unsigned id PK
    varchar(100) name
    varchar(100) slug
    text description
    varchar(50) category
    timestamp created_at
    timestamp updated_at
  }

  role_permissions {
    int unsigned role_id PK FK
    int unsigned permission_id PK FK
  }

  user_roles {
    int unsigned user_id PK FK
    int unsigned role_id PK FK
    int unsigned assigned_by
    datetime assigned_at
  }

  %% NEW: Settings & Tax/Currency Management Tables (September 2025)
  system_settings {
    int unsigned id PK
    varchar(100) setting_key
    text setting_value
    varchar(50) category
    text description
    timestamp created_at
    timestamp updated_at
  }

  tax_rates {
    int unsigned id PK
    varchar(100) name
    decimal(5,2) rate
    enum('sales','purchase','vat','service','import','export') type
    tinyint(1) is_default
    tinyint(1) is_active
    text description
    date effective_from
    date effective_to
    timestamp created_at
    timestamp updated_at
  }

  currencies {
    int unsigned id PK
    varchar(3) code
    varchar(100) name
    varchar(10) symbol
    decimal(12,6) exchange_rate
    tinyint decimal_places
    tinyint(1) is_base
    tinyint(1) is_active
    timestamp created_at
    timestamp updated_at
  }

  exchange_rate_history {
    int unsigned id PK
    int unsigned currency_id FK
    decimal(12,6) old_rate
    decimal(12,6) new_rate
    int unsigned changed_by
    datetime changed_at
    varchar(50) source
  }

  %% Declared foreign keys per live schema
  categories ||--o{ categories : "parent_id -> id"

  invoice_items }o--|| invoices : "invoice_id -> id"
  invoice_payments }o--|| invoices : "invoice_id -> id"

  products }o--o| categories : "category_id -> id"
  products }o--|| makes : "make_id -> id"
  products }o--|| vehicle_models : "model_id -> id"

  vehicle_models }o--|| makes : "make_id -> id"

  product_stocks }o--|| products : "product_id -> id"
  product_stocks }o--|| warehouses : "warehouse_id -> id"

  quotes }o--|| customers : "customer_id -> id"
  quote_items }o--|| quotes : "quote_id -> id"
  quote_items }o--|| products : "product_id -> id"
  quote_items }o--|| warehouses : "warehouse_id -> id"

  sales_order_items }o--|| sales_orders : "sales_order_id -> id"
  sales_orders }o--|| quotes : "quote_id -> id"
  
  %% NEW: RBAC Relationships (September 2025)
  users ||--o{ user_roles : "id -> user_id"
  roles ||--o{ user_roles : "id -> role_id"
  roles ||--o{ role_permissions : "id -> role_id"
  permissions ||--o{ role_permissions : "id -> permission_id"
  
  %% NEW: Settings & Currency Relationships (September 2025)
  currencies ||--o{ exchange_rate_history : "id -> currency_id"
```

