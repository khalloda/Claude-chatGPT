<?php declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';

use App\Core\DB;

$pdo = DB::conn();

$expected = [
    ['products','idx_products_category_make_model','category_id,make_id,model_id'],
    ['invoices','idx_invoices_customer_date_status','customer_id,created_at,status'],
    ['product_stocks','idx_product_stocks_warehouse_qty','warehouse_id,qty_on_hand'],
    ['inventory_ledger','idx_inventory_ledger_product_date','product_id,created_at'],
    ['purchase_invoices','idx_purchase_invoices_supplier_date_status','supplier_id,created_at,status'],
    ['activity_log','idx_activity_log_entity_action_date','entity_type,entity_id,action,created_at'],
    ['cogs_entries','idx_cogs_entries_invoice_product','invoice_id,product_id,created_at'],
    ['products','idx_products_name_code','name,code'],
    ['customers','idx_customers_name_email','name,email'],
];

// Load actual indexes
$sql = "SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS COLUMNS
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
        GROUP BY TABLE_NAME, INDEX_NAME";
$actual = [];
foreach ($pdo->query($sql) as $row) {
    $actual[$row['TABLE_NAME']][$row['INDEX_NAME']] = $row['COLUMNS'];
}

$missing = [];
$mismatch = [];

foreach ($expected as [$table,$index,$cols]) {
    if (!isset($actual[$table][$index])) {
        $missing[] = [$table,$index,$cols];
    } elseif (strcasecmp($actual[$table][$index], $cols) !== 0) {
        $mismatch[] = [$table,$index,$cols,$actual[$table][$index]];
    }
}

if ($missing || $mismatch) {
    fwrite(STDERR, "Index verification failed\n");
    if ($missing) {
        fwrite(STDERR, "Missing indexes:\n");
        foreach ($missing as $m) {
            fwrite(STDERR, sprintf(" - %s.%s (%s)\n", $m[0], $m[1], $m[2]));
        }
    }
    if ($mismatch) {
        fwrite(STDERR, "Mismatched indexes (expected vs actual):\n");
        foreach ($mismatch as $mm) {
            fwrite(STDERR, sprintf(" - %s.%s: expected(%s) actual(%s)\n", $mm[0], $mm[1], $mm[2], $mm[3]));
        }
    }
    exit(1);
}

echo "Index verification OK\n";
exit(0);

