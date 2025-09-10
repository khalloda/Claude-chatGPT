<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

final class Customer
{
    public static function all(int $limit = 100, int $offset = 0): array {
        $sql = "SELECT /*+ USE_INDEX(c, idx_customers_name_email) */
                       id, name, phone, email, address,
                       CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 ELSE 0 END as has_phone,
                       CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END as has_email
                FROM customers 
                ORDER BY name 
                LIMIT ? OFFSET ?";
        
        $st = DB::conn()->prepare($sql);
        $st->execute([$limit, $offset]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Optimized search with ranking
     */
    public static function search(string $query, int $limit = 20): array {
        if (empty(trim($query))) {
            return [];
        }
        
        $sql = "SELECT /*+ USE_INDEX(c, idx_customers_name_email) */
                       id, name, phone, email,
                       CASE 
                           WHEN name = ? THEN 1
                           WHEN email = ? THEN 2
                           WHEN name LIKE ? THEN 3
                           WHEN email LIKE ? THEN 4
                           WHEN phone LIKE ? THEN 5
                           ELSE 6
                       END as relevance_score
                FROM customers
                WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY relevance_score, name
                LIMIT ?";
        
        $exactMatch = $query;
        $prefixMatch = $query . '%';
        $searchTerm = "%{$query}%";
        
        $st = DB::conn()->prepare($sql);
        $st->execute([
            $exactMatch, $exactMatch,           // Exact matches
            $prefixMatch, $prefixMatch,         // Prefix matches  
            $searchTerm,                        // Phone match
            $searchTerm, $searchTerm, $searchTerm, // General matches
            $limit
        ]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get customers with outstanding balances
     */
    public static function getWithOutstandingBalances(float $minimumAmount = 0.01): array {
        $sql = "SELECT c.id, c.name, c.phone, c.email,
                       SUM(i.total - i.paid_amount) as outstanding_balance,
                       COUNT(i.id) as outstanding_invoice_count,
                       MAX(i.created_at) as last_invoice_date
                FROM customers c
                INNER JOIN invoices i ON i.customer_id = c.id
                WHERE i.status IN ('unpaid', 'partial')
                  AND (i.total - i.paid_amount) >= ?
                GROUP BY c.id, c.name, c.phone, c.email
                HAVING outstanding_balance >= ?
                ORDER BY outstanding_balance DESC";
        
        $st = DB::conn()->prepare($sql);
        $st->execute([$minimumAmount, $minimumAmount]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function find(int $id): ?array {
        $st = DB::conn()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
    public static function create(array $d): int {
        $st = DB::conn()->prepare('INSERT INTO customers (name, phone, email, address) VALUES (?,?,?,?)');
        $st->execute([$d['name'], $d['phone'], $d['email'], $d['address']]);
        return (int)DB::conn()->lastInsertId();
    }
    public static function update(int $id, array $d): void {
        $st = DB::conn()->prepare('UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?');
        $st->execute([$d['name'], $d['phone'], $d['email'], $d['address'], $id]);
    }
    public static function delete(int $id): bool {
        // allow delete if no quotes
        $q = DB::conn()->prepare('SELECT COUNT(*) FROM quotes WHERE customer_id=?');
        $q->execute([$id]);
        if ((int)$q->fetchColumn() > 0) return false;
        $st = DB::conn()->prepare('DELETE FROM customers WHERE id=?');
        return $st->execute([$id]);
    }
    public static function options(): array {
        $st = DB::conn()->query('SELECT id, name FROM customers ORDER BY name');
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
