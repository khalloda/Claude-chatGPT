<?php
/**
 * File: app/lang/en.php
 * Purpose: English language strings (UI labels, messages, table headers, statuses)
 * Notes:
 *  - Keep keys stable; other locales (e.g., ar.php) must mirror this structure.
 *  - Use t('key.path') in PHP views/controllers.
 *  - Do NOT include   ffg secrets here.
 */
return [

    /* =========================
     * App
     * ========================= */
    'app.title'        => 'MI Spare Parts',
    'app.tagline'      => 'Inventory, Sales & Purchasing Management',
    'app.version'      => 'Version',
    'app.loading'      => 'Loading…',
    'app.search'       => 'Search',
    'app.clear'        => 'Clear',
    'app.close'        => 'Close',

    /* =========================
     * Navigation (sidebar / header)
     * ========================= */
    'nav.dashboard'    => 'Dashboard',
    'nav.sales'        => 'Sales',
    'nav.purchasing'   => 'Purchasing',
    'nav.inventory'    => 'Inventory',
    'nav.crm'          => 'CRM',
    'nav.reports'      => 'Reports',
    'nav.settings'     => 'Settings',
    'nav.tools'        => 'Tools',

    // Sales sub
    'nav.quotes'           => 'Quotes',
    'nav.sales_orders'     => 'Sales Orders',
    'nav.invoices'         => 'Invoices',
    'nav.sales_returns'    => 'Sales Returns',
    'nav.payments_in'      => 'Payments In (AR)',

    // Purchasing sub
    'nav.suppliers'        => 'Suppliers',
    'nav.purchase_orders'  => 'Purchase Orders',
    'nav.purchase_invoices'=> 'Purchase Invoices',
    'nav.goods_receipts'   => 'Goods Receipts',
    'nav.purchase_returns' => 'Purchase Returns',

    // Inventory sub
    'nav.products'         => 'Products',
    'nav.categories'       => 'Categories',
    'nav.makes'            => 'Makes',
    'nav.models'           => 'Models',
    'nav.warehouses'       => 'Warehouses',
    'nav.transfers'        => 'Transfers',
    'nav.adjustments'      => 'Adjustments',
    'nav.reservations'     => 'Reservations',
    'nav.low_stock'        => 'Low Stock',

    // CRM sub
    'nav.clients'          => 'Clients',
    'nav.contacts'         => 'Contacts',

    // Reports sub
    'nav.reports_sales'        => 'Sales Reports',
    'nav.reports_purchasing'   => 'Purchasing Reports',
    'nav.reports_inventory'    => 'Inventory Reports',
    'nav.reports_ar'           => 'Accounts Receivable',
    'nav.reports_ap'           => 'Accounts Payable',

    // Settings sub
    'nav.users_roles'      => 'Users & Roles',
    'nav.taxes_currency'   => 'Taxes & Currency',
    'nav.units_sequences'  => 'Units & Sequences',
    'nav.translations'     => 'Translations',
    'nav.notifications'    => 'Notifications',
    'nav.integrations'     => 'Integrations',

    // Tools sub
    'nav.import_export'    => 'Import/Export',
    'nav.backups'          => 'Backups',
    'nav.audit_log'        => 'Audit Log',
    'nav.system_health'    => 'System Health',

    /* =========================
     * Actions / Buttons
     * ========================= */
    'action.create'        => 'Create',
    'action.add'           => 'Add',
    'action.edit'          => 'Edit',
    'action.view'          => 'View',
    'action.delete'        => 'Delete',
    'action.save'          => 'Save',
    'action.cancel'        => 'Cancel',
    'action.update'        => 'Update',
    'action.export'        => 'Export',
    'action.print'         => 'Print',
    'action.download'      => 'Download',
    'action.back'          => 'Back',
    'action.filter'        => 'Filter',
    'action.reset_filters' => 'Reset Filters',
    'action.show_columns'  => 'Show/Hide Columns',
    'action.more'          => 'More',
    'action.apply'         => 'Apply',
    'action.confirm'       => 'Confirm',
    'action.yes'           => 'Yes',
    'action.no'            => 'No',

    /* =========================
     * Dashboard KPIs
     * ========================= */
    'kpi.total_stock_value'   => 'Total Stock Value',
    'kpi.out_of_stock'        => 'Out of Stock',
    'kpi.low_stock'           => 'Low Stock',
    'kpi.quotes_week'         => 'Quotes (This Week)',
    'kpi.orders_week'         => 'Sales Orders (This Week)',
    'kpi.invoices_week'       => 'Invoices (This Week)',
    'kpi.overdue_ar'          => 'Overdue AR',
    'kpi.best_seller_week'    => 'Best Selling (This Week)',

    /* =========================
     * Table - generic strings & controls
     * ========================= */
    'table.search_placeholder' => 'Search…',
    'table.rows_per_page'      => 'Rows per page',
    'table.total'              => 'Total',
    'table.of'                 => 'of',
    'table.showing'            => 'Showing',
    'table.to'                 => 'to',
    'table.entries'            => 'entries',
    'table.no_data'            => 'No data available',
    'table.export_csv'         => 'Export CSV',
    'table.export_xlsx'        => 'Export XLSX',
    'table.export_pdf'         => 'Export PDF',
    'table.refresh'            => 'Refresh',
    'table.actions'            => 'Actions',
    'table.pinned_left'        => 'Pinned Left',
    'table.pinned_right'       => 'Pinned Right',
    'table.pin'                => 'Pin',
    'table.unpin'              => 'Unpin',
    'table.density'            => 'Density',
    'table.density_compact'    => 'Compact',
    'table.density_cozy'       => 'Cozy',
    'table.show_core_on_mobile'=> 'Show core columns on mobile',
    'table.truncated_tooltip'  => 'Hover to see full text',
    'table.numeric_hint'       => 'Right-aligned numeric column',

    /* =========================
     * Products (index & forms)
     * ========================= */
    'products.title'           => 'Products',
    'products.code'            => 'Code/SKU',
    'products.name'            => 'Name',
    'products.make_model'      => 'Make • Model',
    'products.stock_avail'     => 'Avail',
    'products.stock_resvd'     => 'Resvd',
    'products.stock_compact'   => 'Avail / Resvd',
    'products.price_sell'      => 'Price',
    'products.status'          => 'Status',
    'products.category'        => 'Category',
    'products.subcategory'     => 'Subcategory',
    'products.category_compact'=> 'Category → Subcategory',
    'products.supplier'        => 'Supplier',
    'products.cost'            => 'Cost',
    'products.warehouse_breakdown' => 'Warehouses',
    'products.updated_at'      => 'Updated',
    'products.create'          => 'Create Product',
    'products.edit'            => 'Edit Product',
    'products.view'            => 'View Product',
    'products.delete_confirm'  => 'Delete this product?',
    'products.placeholder_code'=> 'Enter SKU…',
    'products.placeholder_name'=> 'Enter product name…',
    'products.placeholder_price'=> 'Enter selling price…',

    /* =========================
     * Customers (index & forms)
     * ========================= */
    'customers.title'          => 'Clients',
    'customers.code'           => 'Customer Code',
    'customers.name'           => 'Name',
    'customers.company'        => 'Company',
    'customers.person'         => 'Person',
    'customers.phone'          => 'Phone',
    'customers.email'          => 'Email',
    'customers.city'           => 'City',
    'customers.vat_id'         => 'VAT/Tax ID',
    'customers.salesperson'    => 'Salesperson',
    'customers.payment_terms'  => 'Payment Terms',
    'customers.outstanding'    => 'Outstanding Balance',
    'customers.last_activity'  => 'Last Activity',
    'customers.status'         => 'Status',
    'customers.create'         => 'Create Client',
    'customers.edit'           => 'Edit Client',
    'customers.view'           => 'View Client',
    'customers.delete_confirm' => 'Delete this client?',

    /* =========================
     * Sales Invoices (index & forms)
     * ========================= */
    'invoices.title'           => 'Invoices',
    'invoices.number'          => 'Invoice #',
    'invoices.date'            => 'Date',
    'invoices.customer'        => 'Customer',
    'invoices.total'           => 'Total',
    'invoices.paid'            => 'Paid',
    'invoices.due'             => 'Due',
    'invoices.paid_due_compact'=> 'Paid / Due',
    'invoices.currency'        => 'Currency',
    'invoices.method'          => 'Method',
    'invoices.salesperson'     => 'Salesperson',
    'invoices.linked_so_quote' => 'Linked SO/Quote',
    'invoices.due_date'        => 'Due Date',
    'invoices.created_by'      => 'Created by',
    'invoices.status'          => 'Status',
    'invoices.create'          => 'Create Invoice',
    'invoices.view'            => 'View Invoice',
    'invoices.edit'            => 'Edit Invoice',
    'invoices.delete_confirm'  => 'Delete this invoice?',

    /* =========================
     * Purchase Orders
     * ========================= */
    'pos.title'                => 'Purchase Orders',
    'pos.number'               => 'PO #',
    'pos.date'                 => 'Date',
    'pos.supplier'             => 'Supplier',
    'pos.total'                => 'Total',
    'pos.received_pct'         => 'Received %',
    'pos.status'               => 'Status',
    'pos.expected_date'        => 'Expected Date',
    'pos.warehouse'            => 'Warehouse',
    'pos.created_by'           => 'Created by',
    'pos.payment_terms'        => 'Payment Terms',
    'pos.create'               => 'Create Purchase Order',
    'pos.view'                 => 'View Purchase Order',
    'pos.edit'                 => 'Edit Purchase Order',
    'pos.delete_confirm'       => 'Delete this purchase order?',

    /* =========================
     * Purchase Invoices
     * ========================= */
    'pis.title'                => 'Purchase Invoices',
    'pis.number'               => 'PI #',
    'pis.date'                 => 'Date',
    'pis.supplier'             => 'Supplier',
    'pis.total'                => 'Total',
    'pis.paid_due'             => 'Paid / Due',
    'pis.status'               => 'Status',
    'pis.linked_po'            => 'Linked PO #',
    'pis.currency'             => 'Currency',
    'pis.grn_ref'              => 'GRN Ref',
    'pis.created_by'           => 'Created by',
    'pis.create'               => 'Create Purchase Invoice',
    'pis.view'                 => 'View Purchase Invoice',
    'pis.edit'                 => 'Edit Purchase Invoice',
    'pis.delete_confirm'       => 'Delete this purchase invoice?',

    /* =========================
     * Statuses / Badges
     * ========================= */
    'status.active'                => 'Active',
    'status.inactive'              => 'Inactive',
    'status.low_stock'             => 'Low',
    'status.ok'                    => 'OK',

    'status.draft'                 => 'Draft',
    'status.issued'                => 'Issued',
    'status.paid'                  => 'Paid',
    'status.part_paid'             => 'Part-paid',
    'status.overdue'               => 'Overdue',

    'status.open'                  => 'Open',
    'status.closed'                => 'Closed',
    'status.cancelled'             => 'Cancelled',
    'status.partially_received'    => 'Partially received',
    'status.posted'                => 'Posted',

    /* =========================
     * Forms / Validation
     * ========================= */
    'form.required'            => 'This field is required',
    'form.invalid_email'       => 'Invalid email address',
    'form.invalid_phone'       => 'Invalid phone number',
    'form.duplicate_sku'       => 'SKU already exists',
    'form.min_length'          => 'Too short',
    'form.max_length'          => 'Too long',
    'form.must_be_number'      => 'Must be a number',
    'form.must_be_integer'     => 'Must be an integer',
    'form.must_be_positive'    => 'Must be positive',
    'form.select_option'       => 'Select an option…',

    /* =========================
     * Flash / Toasts
     * ========================= */
    'flash.saved'              => 'Saved successfully.',
    'flash.updated'            => 'Updated successfully.',
    'flash.deleted'            => 'Deleted successfully.',
    'flash.error'              => 'Something went wrong.',
    'flash.invalid_session'    => 'Invalid session. Please try again.',
    'flash.not_found'          => 'Record not found.',
    'flash.confirm_delete'     => 'Are you sure you want to delete this item?',

    /* =========================
     * Print / Documents
     * ========================= */
    'print.title'              => 'Print',
    'print.company'            => 'Company',
    'print.client'             => 'Client',
    'print.supplier'           => 'Supplier',
    'print.date'               => 'Date',
    'print.subtotal'           => 'Subtotal',
    'print.tax'                => 'Tax',
    'print.discount'           => 'Discount',
    'print.total'              => 'Total',
    'print.page'               => 'Page',
    'print.of'                 => 'of',

    /* =========================
     * Dates / Time
     * ========================= */
    'date.today'               => 'Today',
    'date.this_week'           => 'This week',
    'date.this_month'          => 'This month',

    /* =========================
     * Currency labels (display only)
     * ========================= */
    'currency.egp'             => 'EGP',
    'currency.usd'             => 'USD',

    /* =========================
     * Accessibility / Hints
     * ========================= */
    'a11y.skip_to_content'     => 'Skip to content',
    'a11y.navigation'          => 'Main navigation',
    'hint.truncate'            => 'Text truncated; hover to see more',
    'hint.numeric_right'       => 'Numeric values are right-aligned',

    /* =========================
     * Errors (generic)
     * ========================= */
    'error.title'              => 'Error',
    'error.404'                => 'Page not found',
    'error.500'                => 'Server error',
    'error.permission'         => 'You do not have permission to perform this action',

    /* =========================
     * Translations & Localization
     * ========================= */
    'translations.title'       => 'Translation Management',
    'translations.description' => 'Manage system translations and language settings',
    'translations.current_language' => 'Current Language',
    'translations.switch_language' => 'Switch Language',
    'translations.translation_keys' => 'Translation Keys',
    'translations.total_keys'  => 'Total Translation Keys',
    'translations.translated_keys' => 'Translated Keys',
    'translations.missing_keys' => 'Missing Translations',
    'translations.progress'    => 'Translation Progress',
    'translations.search'      => 'Search translation keys...',
    'translations.key'         => 'Key',
    'translations.english'     => 'English',
    'translations.arabic'      => 'Arabic',
    'translations.status'      => 'Status',
    'translations.translated'  => 'Translated',
    'translations.not_translated' => 'Not translated',
    'translations.export'      => 'Export for Translation',
    'translations.stats'       => 'Translation Statistics',
    'translations.coverage'    => 'Translation Coverage',
    'translations.language_info' => 'Language Information',
    'translations.default_language' => 'Default Language',
    'translations.secondary_language' => 'Secondary Language',
    'translations.completion_rate' => 'Completion Rate',
    'translations.missing_categories' => 'Missing Translation Categories',
    'translations.direction_ltr' => 'Left-to-Right (LTR)',
    'translations.direction_rtl' => 'Right-to-Left (RTL)',
    'translations.text_direction' => 'Text direction',

    /* =========================
     * User Management & RBAC
     * ========================= */
    'users.title'              => 'User Management',
    'users.list'               => 'Users',
    'users.create'             => 'Create User',
    'users.edit'               => 'Edit User',
    'users.view'               => 'View User',
    'users.delete'             => 'Delete User',
    'users.status'             => 'Status',
    'users.roles'              => 'Roles',
    'users.permissions'        => 'Permissions',
    'users.last_login'         => 'Last Login',
    'users.search_placeholder' => 'Search users by email or ID...',
    'users.no_users'           => 'No users found',
    'users.profile'            => 'User Profile',
    'users.activity'           => 'User Activity',

    'roles.title'              => 'Role Management',
    'roles.create'             => 'Create Role',
    'roles.edit'               => 'Edit Role',
    'roles.permissions'        => 'Role Permissions',

    'permissions.title'        => 'Permission Management',
    'permissions.category'     => 'Category',
    'permissions.description'  => 'Description',

    /* =========================
     * Settings & Configuration
     * ========================= */
    'settings.title'           => 'Settings',
    'settings.tax_currency'    => 'Tax & Currency Settings',
    'settings.tax_rates'       => 'Tax Rate Management',
    'settings.currencies'      => 'Currency Management',
    'settings.company_info'    => 'Company Information',
    'settings.currency_display' => 'Currency Display',
    'settings.tax_configuration' => 'Tax Configuration',
    'settings.manage_rates'    => 'Manage Tax Rates',
    'settings.manage_currencies' => 'Manage Currencies',
    'settings.current_rates'   => 'Current Tax Rates',
    'settings.current_currencies' => 'Current Currencies',
    'settings.base_currency'   => 'Base Currency',
    'settings.exchange_rate'   => 'Exchange Rate',
    'settings.currency_converter' => 'Currency Converter',

    /* =========================
     * Dashboard
     * ========================= */
    'dashboard.total_stock_value' => 'Total Stock Value',
    'dashboard.out_of_stock'    => 'Out of Stock',
    'dashboard.low_stock'       => 'Low Stock',
    'dashboard.quotes_this_week' => 'Quotes (This Week)',
    'dashboard.orders_this_week' => 'Sales Orders (This Week)',
    'dashboard.invoices_this_week' => 'Invoices (This Week)',
    'dashboard.best_seller_week' => 'Best Selling (This Week)',
    'dashboard.activity'        => 'Activity',
    'dashboard.last_30_days'    => 'Last 30 days',
    'dashboard.charts_placeholder' => 'Charts will appear here (Sales vs. Purchases, Top Products, etc.).',
    'dashboard.connect_metrics' => 'Connect real metrics to replace this placeholder.',
    'dashboard.alerts'          => 'Alerts',
    'dashboard.low_stock_items' => 'Low Stock Items',
    'dashboard.products_below_threshold' => 'products below threshold',
    'dashboard.units'           => 'units',
    'dashboard.overdue_ar'      => 'Overdue AR',
    'dashboard.invoices_overdue' => 'invoices overdue',
    'dashboard.system_status'   => 'System Status',
    'dashboard.system_wired'    => 'Router, controller, view, layout are wired.',
    'dashboard.database_connection' => 'Database connection:',
    'dashboard.health_check'    => 'Health check:',

    /* =========================
     * Navigation & Common UI
     * ========================= */
    'nav.dashboard'            => 'Dashboard',
    'nav.sales'                => 'Sales',
    'nav.quotes'               => 'Quotes',
    'nav.orders'               => 'Sales Orders',
    'nav.invoices'             => 'Invoices',
    'nav.sales_returns'        => 'Sales Returns',
    'nav.payments'             => 'Payments In (AR)',
    'nav.purchasing'           => 'Purchasing',
    'nav.suppliers'            => 'Suppliers',
    'nav.purchase_orders'      => 'Purchase Orders',
    'nav.purchase_invoices'    => 'Purchase Invoices',
    'nav.goods_receipts'       => 'Goods Receipts',
    'nav.purchase_returns'     => 'Purchase Returns',
    'nav.supplier_payments'    => 'Payments Out (AP)',
    'nav.inventory'            => 'Inventory',
    'nav.products'             => 'Products',
    'nav.categories'           => 'Categories',
    'nav.makes'                => 'Makes',
    'nav.models'               => 'Models',
    'nav.warehouses'           => 'Warehouses',
    'nav.transfers'            => 'Transfers',
    'nav.adjustments'          => 'Adjustments',
    'nav.reservations'         => 'Reservations',
    'nav.low_stock'            => 'Low Stock',
    'nav.crm'                  => 'CRM',
    'nav.customers'            => 'Clients',
    'nav.contacts'             => 'Contacts',
    'nav.reports'              => 'Reports',
    'nav.sales_reports'        => 'Sales',
    'nav.purchasing_reports'   => 'Purchasing',
    'nav.inventory_reports'    => 'Inventory',
    'nav.ar_reports'           => 'AR',
    'nav.ap_reports'           => 'AP',
    'nav.settings'             => 'Settings',
    'nav.users'                => 'Users & Roles',
    'nav.tax_currency'         => 'Taxes & Currency',
    'nav.units_sequences'      => 'Units & Sequences',
    'nav.translations'         => 'Translations',
    'nav.notifications'        => 'Notifications',
    'nav.integrations'         => 'Integrations',
    'nav.tools'                => 'Tools',
    'nav.import_export'        => 'Import/Export',
    'nav.backups'              => 'Backups',
    'nav.audit_log'            => 'Audit Log',
    'nav.system_health'        => 'System Health',
    'nav.logout'               => 'Logout',
    'nav.language'             => 'Language',

    'table.search_placeholder' => 'Search…',
    
    'common.ok'                => 'OK',
    'common.failed'            => 'FAILED',
    'common.email'             => 'Email',
    'common.password'          => 'Password',
    'common.name'              => 'Name',
    'common.phone'             => 'Phone',
    'common.address'           => 'Address',
    'common.actions'           => 'Actions',
    'common.status'            => 'Status',
    'common.total'             => 'Total',
    'common.view'              => 'View',
    'common.edit'              => 'Edit',
    'common.delete'            => 'Delete',
    'common.search'            => 'Search',
    'common.create'            => 'Create',
    'common.save'              => 'Save',
    'common.save_changes'      => 'Save Changes',
    'common.return_home'       => 'Return Home',
    'common.back'              => 'Back',
    'common.close'             => 'Close',
    'common.cancel'            => 'Cancel',
    'common.confirm'           => 'Confirm',
    'common.update'            => 'Update',

    /* =========================
     * Authentication
     * ========================= */
    'auth.login'               => 'Login',
    'auth.enter_credentials'   => 'Enter your credentials.',
    'auth.sign_in'             => 'Sign in',
    'auth.logout'              => 'Logout',
    
    /* =========================
     * Error Messages
     * ========================= */
    'errors.404_title'         => '404 — Not Found',
    'errors.404_message'       => 'The page you requested could not be found.',
    'errors.access_denied'     => 'Access Denied',
    'errors.access_denied_message' => 'You don\'t have permission to access this resource. This could be due to:',
    'errors.common_reasons'    => 'Common reasons:',
    'errors.not_logged_in'     => 'You\'re not logged in',
    'errors.insufficient_permissions' => 'You don\'t have the required permissions',
    'errors.session_expired'   => 'Your session has expired',
    'errors.authentication_required' => 'The resource requires authentication',
    'errors.contact_admin_message' => 'If you believe this is an error, please <a href="/login">sign in</a> or contact your administrator.',
    'common.save'              => 'Save',
    'common.export'            => 'Export',
    'common.import'            => 'Import',
    'common.filter'            => 'Filter',
    'common.reset'             => 'Reset',

    /* =========================
     * Products
     * ========================= */
    'products.search_placeholder' => 'Search name or code',
    'products.all_categories'  => 'All categories',
    'products.all_makes'       => 'All makes',
    'products.all_models'      => 'All models',
    'products.new_product'     => '+ New Product',
    'products.code'            => 'Code',
    'products.category'        => 'Category',
    'products.make_model'      => 'Make / Model',
    'products.cost'            => 'Cost',
    'products.price'           => 'Price',
    'products.availability'    => 'Avail / Resv',
    'products.stock'           => 'Stock',
    'products.delete_confirm'  => 'Delete this product?',
    'products.no_products'     => 'No products yet.',

    /* =========================
     * Customers
     * ========================= */
    'customers.new_customer'   => '+ New Customer',
    'customers.edit_customer'  => 'Edit Customer',
    'customers.customer'       => 'Customer',
    'customers.statement'      => 'Statement',
    'customers.delete_confirm' => 'Delete this customer?',
    'customers.no_customers'   => 'No customers yet.',
    'customers.ar_totals'      => 'AR Totals',
    'customers.invoices'       => 'Invoices',
    'customers.payments'       => 'Payments',
    'customers.credits'        => 'Credits',
    'customers.balance'        => 'Balance',
    'customers.view_statement' => 'View Statement',
    'customers.back_to_customers' => 'Back to Customers',
    'customers.sales_orders'   => 'Sales Orders',

    /* =========================
     * Invoices
     * ========================= */
    'invoices.invoice_number'  => 'Invoice #',
    'invoices.customer'        => 'Customer',
    'invoices.paid'           => 'Paid',
    'invoices.no_invoices'    => 'No invoices yet.',
    'invoices.invoice'        => 'Invoice',
    'invoices.credits'        => 'Credits',
    'invoices.balance'        => 'Balance',
    'invoices.confirm_delivered' => 'Confirm Delivered',
    'invoices.print'          => 'Print',
    'invoices.product'        => 'Product',
    'invoices.warehouse'      => 'Warehouse',
    'invoices.quantity'       => 'Qty',
    'invoices.unit_price'     => 'Unit Price',
    'invoices.line_total'     => 'Line Total',

    /* =========================
     * Orders
     * ========================= */
    'orders.order_number'     => 'Order #',
    'orders.customer'         => 'Customer',
    'orders.no_orders'        => 'No orders yet.',
    'orders.sales_order'      => 'Sales Order',
    'orders.create_invoice'   => 'Create Invoice',
    'orders.print'            => 'Print',
    'orders.back_to_orders'   => 'Back to Orders',
    'orders.product'          => 'Product',
    'orders.warehouse'        => 'Warehouse',
    'orders.quantity'         => 'Qty',
    'orders.unit_price'       => 'Unit Price',
    'orders.line_total'       => 'Line Total',

    /* =========================
     * Quotes
     * ========================= */
    'quotes.new_quote'        => '+ New Quote',
    'quotes.quote_number'     => 'Quote #',
    'quotes.customer'         => 'Customer',
    'quotes.no_quotes'        => 'No quotes yet.',
    'quotes.quote'            => 'Quote',
    'quotes.subtotal'         => 'Subtotal',
    'quotes.tax'              => 'Tax',
    'quotes.expires_at'       => 'Expires at',
    'quotes.product'          => 'Product',
    'quotes.warehouse'        => 'Warehouse',
    'quotes.quantity'         => 'Qty',
    'quotes.price'            => 'Price',
    'quotes.line_total'       => 'Line total',

    /* =========================
     * Suppliers
     * ========================= */
    'suppliers.back_to_purchase_orders' => 'Back to Purchase Orders',
    'suppliers.balance'       => 'Balance',
    'suppliers.statement'     => 'Statement',
    'suppliers.no_suppliers'  => 'No suppliers found.',
    'suppliers.new_supplier'  => 'New Supplier',
    'suppliers.edit_supplier' => 'Edit Supplier',
    'suppliers.supplier'      => 'Supplier',
    'suppliers.ap_totals'     => 'AP Totals',
    'suppliers.invoices'      => 'Invoices',
    'suppliers.payments'      => 'Payments',
    'suppliers.credits'       => 'Credits',
    'suppliers.view_statement' => 'View Statement',
    'suppliers.back_to_suppliers' => 'Back to Suppliers',
    'suppliers.purchase_orders' => 'Purchase Orders',
    'suppliers.delivered_items' => 'Delivered Items (Receipts)',
    'suppliers.purchase_invoices' => 'Purchase Invoices',
    'suppliers.payments_ap'   => 'Payments (AP)',

    /* =========================
     * Warehouses
     * ========================= */
    'warehouses.new_warehouse' => '+ New Warehouse',
    'warehouses.edit_warehouse' => 'Edit Warehouse',
    'warehouses.code'         => 'Code',
    'warehouses.location'     => 'Location',
    'warehouses.location_optional' => 'Location (optional)',
    'warehouses.on_hand'      => 'On hand',
    'warehouses.reserved'     => 'Reserved',
    'warehouses.value'        => 'Value',

    /* =========================
     * Payments
     * ========================= */
    'payments.back_to_invoices' => 'Back to Invoices',
    'payments.date'           => 'Date',
    'payments.payment_number' => 'Payment #',
    'payments.invoice'        => 'Invoice',
    'payments.customer'       => 'Customer',
    'payments.method'         => 'Method',
    'payments.reference'      => 'Ref',
    'payments.amount'         => 'Amount',
    'payments.new_for_invoice' => 'New for this invoice',
    'payments.no_payments'    => 'No payments yet.',
    'payments.new_payment'    => 'New Payment',
    'payments.paid'           => 'Paid',
    'payments.note'           => 'Note',
    'payments.save_payment'   => 'Save Payment',
    'payments.back_to_invoice' => 'Back to Invoice',
    'payments.payments_list'  => 'Payments list',

    /* =========================
     * Reports
     * ========================= */
    'reports.sales_report'    => 'Sales Report',
    'reports.ar_aging'        => 'AR Aging',
    'reports.from'            => 'From',
    'reports.to'              => 'To',
    'reports.as_of'           => 'As of',
    'reports.apply'           => 'Apply',
    'reports.print'           => 'Print',
    'reports.total_invoices'  => 'Total Invoices',
    'reports.total_returns'   => 'Total Returns',
    'reports.net_sales'       => 'Net Sales',
    'reports.payments_received' => 'Payments Received',
    'reports.date'            => 'Date',
    'reports.type'            => 'Type',
    'reports.reference'       => 'Ref',
    'reports.customer'        => 'Customer',
    'reports.debit'           => 'Debit',
    'reports.credit'          => 'Credit',
    'reports.age_0_30'        => '0–30',
    'reports.age_31_60'       => '31–60',
    'reports.age_61_90'       => '61–90',
    'reports.age_90_plus'     => '90+',

    /* =========================
     * Sales Returns
     * ========================= */
    'salesreturns.credit_notes' => 'Credit Notes (Sales Returns)',
    'salesreturns.back_to_invoices' => 'Back to Invoices',
    'salesreturns.credit_number' => 'Credit #',
    'salesreturns.invoice_number' => 'Invoice #',
    'salesreturns.customer'   => 'Customer',
    'salesreturns.date'       => 'Date',
    'salesreturns.no_credit_notes' => 'No credit notes yet.',

    /* =========================
     * Purchase Orders
     * ========================= */
    'purchaseorders.purchase_orders' => 'Purchase Orders',
    'purchaseorders.new_po'   => 'New PO',
    'purchaseorders.po_number' => 'PO #',
    'purchaseorders.supplier' => 'Supplier',
    'purchaseorders.no_purchase_orders' => 'No purchase orders yet.',

    /* =========================
     * Categories
     * ========================= */
    'categories.categories'   => 'Categories',
    'categories.new_category' => '+ New Category',
    'categories.slug'         => 'Slug',
    'categories.parent'       => 'Parent',

    /* =========================
     * User Management
     * ========================= */
    'users.roles'             => 'Roles',
    'users.new_role'          => 'New Role',
    'users.slug'              => 'Slug',
    'users.delete_role_confirm' => 'Delete this role?',
    'users.no_roles'          => 'No roles found.',

    /* =========================
     * Purchase Invoices
     * ========================= */
    'purchaseinvoices.purchase_invoices' => 'Purchase Invoices',
    'purchaseinvoices.back_to_purchase_orders' => 'Back to Purchase Orders',
    'purchaseinvoices.pi_number' => 'PI #',
    'purchaseinvoices.supplier' => 'Supplier',
    'purchaseinvoices.po_number' => 'PO #',
    'purchaseinvoices.no_purchase_invoices' => 'No purchase invoices yet.',

    /* =========================
     * Makes & Models
     * ========================= */
    'makes.makes'             => 'Makes',
    'makes.new_make'          => '+ New Make',
    'makes.slug'              => 'Slug',
    'models.models'           => 'Models',
    'models.filter_by_make'   => 'Filter by make',
    'models.new_model'        => '+ New Model',

    /* =========================
     * Stock Transfers
     * ========================= */
    'transfers.stock_transfers' => 'Stock Transfers',
    'transfers.new_transfer'  => 'New Transfer',
    'transfers.tr_number'     => 'TR #',
    'transfers.date'          => 'Date',
    'transfers.from'          => 'From',
    'transfers.to'            => 'To',
    'transfers.open'          => 'Open',
    'transfers.no_transfers'  => 'No transfers yet.',

    /* =========================
     * Goods Receipts
     * ========================= */
    'receipts.goods_receipts' => 'Goods Receipts (GRN)',
    'receipts.most_recent_receipts' => 'Most recent receipts across all Purchase Invoices.',
    'receipts.date'          => 'Date',
    'receipts.pi_number'     => 'PI #',
    'receipts.product'       => 'Product',
    'receipts.warehouse'     => 'Warehouse',
    'receipts.quantity'      => 'Qty',
    'receipts.unit_cost'     => 'Unit Cost',

    /* =========================
     * Purchase Returns
     * ========================= */
    'purchasereturns.purchase_returns' => 'Purchase Returns (Debit Notes)',
    'purchasereturns.most_recent_returns' => 'Most recent purchase returns across all PIs.',
    'purchasereturns.date'   => 'Date',
    'purchasereturns.pr_number' => 'PR #',
    'purchasereturns.pi_number' => 'PI #',
    'purchasereturns.supplier' => 'Supplier',

    /* =========================
     * Supplier Payments
     * ========================= */
    'supplierpayments.supplier_payments' => 'Supplier Payments',
    'supplierpayments.back_to_purchase_invoices' => 'Back to Purchase Invoices',
    'supplierpayments.date'  => 'Date',
    'supplierpayments.supplier' => 'Supplier',
    'supplierpayments.pi_number' => 'PI #',
    'supplierpayments.method' => 'Method',
    'supplierpayments.reference' => 'Ref',
    'supplierpayments.amount' => 'Amount',
    'supplierpayments.no_supplier_payments' => 'No supplier payments yet.',

    /* =========================
     * Stock Adjustments
     * ========================= */
    'adjustments.stock_adjustments' => 'Stock Adjustments',
    'adjustments.new_adjustment' => 'New Adjustment',
    'adjustments.ad_number'  => 'AD #',
    'adjustments.date'       => 'Date',
    'adjustments.warehouse'  => 'Warehouse',
    'adjustments.reason'     => 'Reason',
    'adjustments.open'       => 'Open',
    'adjustments.no_adjustments' => 'No adjustments yet.',

    /* =========================
     * Reservations
     * ========================= */
    'reservations.reservations' => 'Reservations',
    'reservations.description' => 'Shows documents currently holding stock reservations. Quotes reserve when status is Sent. Orders reserve after converting from quotes. Delivery confirmation on invoice releases order reservations.',
    'reservations.all_customers' => 'All customers',
    'reservations.all_types' => 'All types',

    /* =========================
     * Low Stock
     * ========================= */
    'lowstock.low_stock' => 'Low Stock',
    'lowstock.threshold' => 'Threshold',
    'lowstock.warehouse' => 'Warehouse',
    'lowstock.product' => 'Product',
    'lowstock.on_hand' => 'On Hand',
    'lowstock.reserved' => 'Reserved',
    'lowstock.available' => 'Available',

    /* =========================
     * Contacts
     * ========================= */
    'contacts.contacts' => 'Contacts',
    'contacts.search_placeholder' => 'Search name/email/phone',
    'contacts.all_customers' => 'All customers',
    'contacts.new_contact' => '+ New Contact',
    'contacts.title' => 'Title',
    'contacts.client' => 'Client',

    /* =========================
     * Additional Reports
     * ========================= */
    'reports.purchasing_report' => 'Purchasing Report',
    'reports.from' => 'From',
    'reports.to' => 'To',
    'reports.total_purchases' => 'Total Purchases',
    'reports.total_purchase_returns' => 'Total Purchase Returns',
    'reports.net_purchases' => 'Net Purchases',
    'reports.supplier_payments' => 'Supplier Payments',
    'reports.date' => 'Date',
    'reports.type' => 'Type',
    'reports.reference' => 'Ref',
    'reports.supplier' => 'Supplier',
    'reports.debit' => 'Debit',
    'reports.credit' => 'Credit',
    'reports.inventory_valuation' => 'Inventory Valuation (Weighted Avg)',
    'reports.warehouse' => 'Warehouse',
    'reports.product' => 'Product',
    'reports.on_hand' => 'On hand',
    'reports.avg_cost' => 'Avg cost',
    'reports.value' => 'Value',
    'reports.ap_aging' => 'AP Aging',
    'reports.as_of' => 'As of',
    'reports.0_30_days' => '0–30',
    'reports.31_60_days' => '31–60',
    'reports.61_90_days' => '61–90',
    'reports.90_plus_days' => '90+',

    'common.all'               => 'All',
    'common.none'              => 'None',
    'common.loading'           => 'Loading...',
    'common.no_data'           => 'No data available',
    'common.error'             => 'Error',
    'common.success'           => 'Success',
    'common.warning'           => 'Warning',
    'common.info'              => 'Information',
];
