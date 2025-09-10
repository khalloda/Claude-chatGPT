<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

final class Invoice
{
    /** Next/peek are optional; keep here if you already use centralized numbering */
    public static function nextNumber(): string {
        return \App\Services\DocNumbers::next('inv');
    }
    public static function peekNumber(): string {
        return \App\Services\DocNumbers::peek('inv');
    }

    public static function all(int $limit = 100, int $offset = 0, ?string $status = null, ?int $customerId = null): array
    {
        $sql = "SELECT /*+ USE_INDEX(i, idx_invoices_status_date) */
                       i.id, i.inv_no, i.customer_id, i.total, i.paid_amount, i.status, i.created_at,
                       c.name AS customer_name,
                       COALESCE(line_summary.line_count, 0) as line_count,
                       COALESCE(payment_summary.payment_count, 0) as payment_count
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                LEFT JOIN (
                    SELECT invoice_id, COUNT(*) as line_count
                    FROM invoice_lines
                    GROUP BY invoice_id
                ) line_summary ON line_summary.invoice_id = i.id
                LEFT JOIN (
                    SELECT invoice_id, COUNT(*) as payment_count
                    FROM invoice_payments
                    GROUP BY invoice_id
                ) payment_summary ON payment_summary.invoice_id = i.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status !== null) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        
        if ($customerId !== null) {
            $sql .= " AND i.customer_id = ?";
            $params[] = $customerId;
        }
        
        $sql .= " ORDER BY i.created_at DESC, i.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $st = DB::conn()->prepare($sql);
        $st->execute($params);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get invoices by status with optimized query
     */
    public static function getByStatus(string $status, int $limit = 50): array
    {
        $sql = "SELECT /*+ USE_INDEX(i, idx_invoices_status_date) */
                       i.id, i.inv_no, i.customer_id, i.total, i.paid_amount, i.status, i.created_at,
                       c.name AS customer_name,
                       (i.total - i.paid_amount) as outstanding_amount
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                WHERE i.status = ?
                ORDER BY i.created_at DESC
                LIMIT ?";
        
        $st = DB::conn()->prepare($sql);
        $st->execute([$status, $limit]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get customer invoices with aging information
     */
    public static function getCustomerInvoicesWithAging(int $customerId, int $days = 90): array
    {
        $sql = "SELECT /*+ USE_INDEX(i, idx_invoices_customer_date_status) */
                       i.id, i.inv_no, i.total, i.paid_amount, i.status, i.created_at,
                       (i.total - i.paid_amount) as outstanding_amount,
                       DATEDIFF(NOW(), i.created_at) as days_outstanding,
                       CASE 
                           WHEN DATEDIFF(NOW(), i.created_at) <= 30 THEN '0-30'
                           WHEN DATEDIFF(NOW(), i.created_at) <= 60 THEN '31-60'
                           WHEN DATEDIFF(NOW(), i.created_at) <= 90 THEN '61-90'
                           ELSE '90+'
                       END as aging_bucket
                FROM invoices i
                WHERE i.customer_id = ?
                  AND i.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND i.status IN ('unpaid', 'partial')
                  AND (i.total - i.paid_amount) > 0.01
                ORDER BY i.created_at DESC";
        
        $st = DB::conn()->prepare($sql);
        $st->execute([$customerId, $days]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = DB::conn()->prepare("SELECT * FROM invoices WHERE id=?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function items(int $invoiceId): array
    {
        $pdo = DB::conn();
        [$tbl, $hasWh, $hasLineTotal] = self::detectItemsMeta($pdo);

        // Build select list dynamically with optimized JOINs
        $select = "il.id as line_id, il.product_id, il.qty, il.price";
        if ($hasLineTotal) {
            $select .= ", il.line_total";
        } else {
            $select .= ", (il.qty * il.price) AS line_total";
        }
        if ($hasWh) {
            $select .= ", il.warehouse_id, w.name AS warehouse_name";
        }

        // Use index hints for better performance
        $sql = "SELECT /*+ USE_INDEX(il, idx_invoice_lines_invoice_product) */
                       {$select}, 
                       p.code AS product_code, 
                       p.name AS product_name,
                       p.cost AS product_cost,
                       c.name AS category_name
                FROM {$tbl} il
                INNER JOIN products p ON p.id = il.product_id
                LEFT JOIN categories c ON c.id = p.category_id" .
               ($hasWh ? " LEFT JOIN warehouses w ON w.id = il.warehouse_id" : "") . "
                WHERE il.invoice_id = ?
                ORDER BY il.id";

        $st = $pdo->prepare($sql);
        $st->execute([$invoiceId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Batch load items for multiple invoices
     */
    public static function batchLoadItems(array $invoiceIds): array
    {
        if (empty($invoiceIds)) {
            return [];
        }
        
        $pdo = DB::conn();
        [$tbl, $hasWh, $hasLineTotal] = self::detectItemsMeta($pdo);
        
        $placeholders = str_repeat('?,', count($invoiceIds) - 1) . '?';
        
        $select = "il.invoice_id, il.id as line_id, il.product_id, il.qty, il.price";
        if ($hasLineTotal) {
            $select .= ", il.line_total";
        } else {
            $select .= ", (il.qty * il.price) AS line_total";
        }
        if ($hasWh) {
            $select .= ", il.warehouse_id, w.name AS warehouse_name";
        }
        
        $sql = "SELECT {$select}, 
                       p.code AS product_code, 
                       p.name AS product_name
                FROM {$tbl} il
                INNER JOIN products p ON p.id = il.product_id" .
               ($hasWh ? " LEFT JOIN warehouses w ON w.id = il.warehouse_id" : "") . "
                WHERE il.invoice_id IN ($placeholders)
                ORDER BY il.invoice_id, il.id";
        
        $st = $pdo->prepare($sql);
        $st->execute($invoiceIds);
        $results = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Group by invoice ID
        $grouped = [];
        foreach ($results as $item) {
            $invoiceId = (int)$item['invoice_id'];
            unset($item['invoice_id']); // Remove grouping key
            $grouped[$invoiceId][] = $item;
        }
        
        return $grouped;
    }

    public static function recomputePaidAndStatus(int $id): void
    {
        $pdo = DB::conn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM invoice_payments WHERE invoice_id=?");
        $st->execute([$id]); $paid = (float)$st->fetchColumn();

        $st2 = $pdo->prepare("SELECT total FROM invoices WHERE id=?");
        $st2->execute([$id]); $total = (float)$st2->fetchColumn();

        $status = ($paid <= 0.0) ? 'unpaid' : (($paid + 0.00001 < $total) ? 'partial' : 'paid');
        $upd = $pdo->prepare("UPDATE invoices SET paid_amount=?, status=? WHERE id=?");
        $upd->execute([$paid, $status, $id]);
    }

    /* --------- helpers --------- */

    private static function detectItemsMeta(PDO $pdo): array
    {
        static $cache = null;
        if ($cache) { return $cache; }

        $candidates = ['invoice_lines','invoice_items','sales_invoice_items','invoices_items'];
        $in = implode(',', array_fill(0, count($candidates), '?'));
        $sql = "SELECT table_name FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name IN ($in)
                 LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute($candidates);
        $tbl = (string)$st->fetchColumn();

        if ($tbl === '') {
            // No known items table found — let caller decide or create via migration
            throw new \RuntimeException(
                "No invoice items table found. Expected one of: ".implode(', ', $candidates)
            );
        }

        $hasWh        = self::tableHasColumn($pdo, $tbl, 'warehouse_id');
        $hasLineTotal = self::tableHasColumn($pdo, $tbl, 'line_total');

        $cache = [$tbl, $hasWh, $hasLineTotal];
        return $cache;
    }

    private static function tableHasColumn(PDO $pdo, string $table, string $col): bool
    {
        $st = $pdo->prepare(
            "SELECT 1 FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $st->execute([$table, $col]);
        return (bool)$st->fetchColumn();
    }
}
