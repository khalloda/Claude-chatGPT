<?php declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

/**
 * Customer Aging Service
 * 
 * Optimizes customer aging calculations by using single queries
 * instead of multiple N+1 queries for AR balance computations.
 */
final class CustomerAging
{
    /**
     * Get customer aging data with single optimized query
     */
    public static function getCustomerAging(int $customerId): array
    {
        $pdo = DB::conn();
        
        // Single query to get all customer financial data
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.phone,
                c.email,
                c.address,
                COALESCE(totals.invoice_total, 0) as invoice_total,
                COALESCE(totals.payment_total, 0) as payment_total,
                COALESCE(totals.return_total, 0) as return_total,
                (COALESCE(totals.invoice_total, 0) - 
                 COALESCE(totals.payment_total, 0) - 
                 COALESCE(totals.return_total, 0)) as ar_balance
            FROM customers c
            LEFT JOIN (
                SELECT 
                    i.customer_id,
                    SUM(i.total) as invoice_total,
                    SUM(COALESCE(p.amount, 0)) as payment_total,
                    SUM(COALESCE(sr.total, 0)) as return_total
                FROM invoices i
                LEFT JOIN invoice_payments p ON p.invoice_id = i.id
                LEFT JOIN sales_returns sr ON sr.sales_invoice_id = i.id
                WHERE i.customer_id = ?
                GROUP BY i.customer_id
            ) totals ON totals.customer_id = c.id
            WHERE c.id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId, $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: [];
    }
    
    /**
     * Get customer aging for statement with date range
     */
    public static function getCustomerStatement(int $customerId, string $fromDate, string $toDate): array
    {
        $pdo = DB::conn();
        
        // Opening balance calculation
        $openingSql = "
            SELECT 
                COALESCE(SUM(i.total), 0) - 
                COALESCE(SUM(p.amount), 0) - 
                COALESCE(SUM(sr.total), 0) as opening_balance
            FROM invoices i
            LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.paid_at < ?
            LEFT JOIN sales_returns sr ON sr.sales_invoice_id = i.id AND sr.created_at < ?
            WHERE i.customer_id = ? AND i.created_at < ?
        ";
        
        $stmt = $pdo->prepare($openingSql);
        $stmt->execute([$fromDate, $fromDate, $customerId, $fromDate]);
        $opening = (float)$stmt->fetchColumn();
        
        // Get customer info
        $custSql = "SELECT * FROM customers WHERE id = ?";
        $stmt = $pdo->prepare($custSql);
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get transactions within date range in single query
        $transactionsSql = "
            SELECT 
                txn_date,
                kind,
                ref_no,
                debit,
                credit,
                ref_id
            FROM (
                SELECT 
                    i.created_at as txn_date,
                    'invoice' as kind,
                    COALESCE(i.inv_no, CAST(i.id AS CHAR)) as ref_no,
                    i.total as debit,
                    0 as credit,
                    i.id as ref_id
                FROM invoices i
                WHERE i.customer_id = ? 
                  AND DATE(i.created_at) BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    p.paid_at as txn_date,
                    'payment' as kind,
                    p.reference as ref_no,
                    0 as debit,
                    p.amount as credit,
                    p.id as ref_id
                FROM invoice_payments p
                JOIN invoices i ON i.id = p.invoice_id
                WHERE i.customer_id = ?
                  AND DATE(p.paid_at) BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    sr.created_at as txn_date,
                    'return' as kind,
                    sr.sr_no as ref_no,
                    0 as debit,
                    sr.total as credit,
                    sr.id as ref_id
                FROM sales_returns sr
                JOIN invoices i ON i.id = sr.sales_invoice_id
                WHERE i.customer_id = ?
                  AND DATE(sr.created_at) BETWEEN ? AND ?
            ) transactions
            ORDER BY txn_date, kind
        ";
        
        $stmt = $pdo->prepare($transactionsSql);
        $stmt->execute([
            $customerId, $fromDate, $toDate,
            $customerId, $fromDate, $toDate,
            $customerId, $fromDate, $toDate
        ]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Calculate running balances
        $running = $opening;
        foreach ($transactions as &$txn) {
            $running += (float)$txn['debit'] - (float)$txn['credit'];
            $txn['running'] = $running;
        }
        unset($txn);
        
        return [
            'customer' => $customer,
            'opening' => $opening,
            'transactions' => $transactions,
            'closing' => $running
        ];
    }
    
    /**
     * Get aging summary for all customers
     */
    public static function getAllCustomersAging(): array
    {
        $pdo = DB::conn();
        
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.phone,
                c.email,
                COALESCE(aging.invoice_total, 0) as invoice_total,
                COALESCE(aging.payment_total, 0) as payment_total,
                COALESCE(aging.return_total, 0) as return_total,
                (COALESCE(aging.invoice_total, 0) - 
                 COALESCE(aging.payment_total, 0) - 
                 COALESCE(aging.return_total, 0)) as ar_balance,
                aging.current_30,
                aging.days_31_60,
                aging.days_61_90,
                aging.days_over_90
            FROM customers c
            LEFT JOIN (
                SELECT 
                    i.customer_id,
                    SUM(i.total) as invoice_total,
                    SUM(COALESCE(p.amount, 0)) as payment_total,
                    SUM(COALESCE(sr.total, 0)) as return_total,
                    SUM(CASE 
                        WHEN DATEDIFF(CURDATE(), i.created_at) <= 30 
                        THEN i.total - COALESCE(p.amount, 0) - COALESCE(sr.total, 0)
                        ELSE 0 
                    END) as current_30,
                    SUM(CASE 
                        WHEN DATEDIFF(CURDATE(), i.created_at) BETWEEN 31 AND 60 
                        THEN i.total - COALESCE(p.amount, 0) - COALESCE(sr.total, 0)
                        ELSE 0 
                    END) as days_31_60,
                    SUM(CASE 
                        WHEN DATEDIFF(CURDATE(), i.created_at) BETWEEN 61 AND 90 
                        THEN i.total - COALESCE(p.amount, 0) - COALESCE(sr.total, 0)
                        ELSE 0 
                    END) as days_61_90,
                    SUM(CASE 
                        WHEN DATEDIFF(CURDATE(), i.created_at) > 90 
                        THEN i.total - COALESCE(p.amount, 0) - COALESCE(sr.total, 0)
                        ELSE 0 
                    END) as days_over_90
                FROM invoices i
                LEFT JOIN invoice_payments p ON p.invoice_id = i.id
                LEFT JOIN sales_returns sr ON sr.sales_invoice_id = i.id
                GROUP BY i.customer_id
            ) aging ON aging.customer_id = c.id
            WHERE aging.invoice_total > 0 OR aging.payment_total > 0 OR aging.return_total > 0
            ORDER BY ar_balance DESC, c.name
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get invoice aging details for a customer
     */
    public static function getInvoiceAging(int $customerId): array
    {
        $pdo = DB::conn();
        
        $sql = "
            SELECT 
                i.id,
                COALESCE(i.inv_no, CAST(i.id AS CHAR)) as inv_no,
                i.created_at,
                i.total,
                COALESCE(payments.paid_amount, 0) as paid_amount,
                COALESCE(returns.return_amount, 0) as return_amount,
                (i.total - COALESCE(payments.paid_amount, 0) - COALESCE(returns.return_amount, 0)) as balance,
                DATEDIFF(CURDATE(), i.created_at) as days_old,
                i.status
            FROM invoices i
            LEFT JOIN (
                SELECT 
                    invoice_id,
                    SUM(amount) as paid_amount
                FROM invoice_payments
                GROUP BY invoice_id
            ) payments ON payments.invoice_id = i.id
            LEFT JOIN (
                SELECT 
                    sales_invoice_id,
                    SUM(total) as return_amount
                FROM sales_returns
                GROUP BY sales_invoice_id
            ) returns ON returns.sales_invoice_id = i.id
            WHERE i.customer_id = ?
            HAVING balance > 0
            ORDER BY i.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Cache customer aging data for performance
     */
    public static function getCachedCustomerAging(int $customerId): array
    {
        $cacheKey = "customer_aging_{$customerId}";
        $cacheFile = self::getCacheFile($cacheKey);
        
        // Check cache validity (5 minutes)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cached = @unserialize(file_get_contents($cacheFile));
            if ($cached && is_array($cached)) {
                return $cached;
            }
        }
        
        // Generate fresh data
        $data = self::getCustomerAging($customerId);
        
        // Cache the result
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, serialize($data), LOCK_EX);
        
        return $data;
    }
    
    /**
     * Clear customer aging cache
     */
    public static function clearCache(?int $customerId = null): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        
        if ($customerId) {
            $cacheFile = self::getCacheFile("customer_aging_{$customerId}");
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        } else {
            $files = glob($cacheDir . '/customer_aging_*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Get cache file path
     */
    private static function getCacheFile(string $key): string
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        return $cacheDir . '/' . md5($key) . '.cache';
    }
}