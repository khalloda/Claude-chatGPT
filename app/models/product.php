<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Services\ReferenceDataCache;
use PDO;

final class Product
{
    private static ?array $reserveCols = null; // cache presence of split reserve columns

    private static function loadReserveCols(): void
    {
        if (self::$reserveCols !== null) return;
        try {
            $pdo = DB::conn();
            // Robust detection: check columns individually
            $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_stocks' AND column_name = ?");
            $chk->execute(['qty_reserved']);
            $hasQR = (int)$chk->fetchColumn() > 0;
            $chk->execute(['qty_reserved_quote']);
            $hasQQuote = (int)$chk->fetchColumn() > 0;
            $chk->execute(['qty_reserved_order']);
            $hasQOrder = (int)$chk->fetchColumn() > 0;
            self::$reserveCols = [
                'has_qty_reserved' => $hasQR,
                'has_split' => ($hasQQuote && $hasQOrder),
            ];
        } catch (\Throwable $e) {
            self::$reserveCols = ['has_qty_reserved'=>true,'has_split'=>false];
        }
    }

    private static function hasSplitReserve(): bool
    {
        self::loadReserveCols();
        return (bool)self::$reserveCols['has_split'];
    }

    public static function supportsSplitReserve(): bool
    {
        return self::hasSplitReserve();
    }

    public static function availableQty(int $productId, int $warehouseId): int
    {
        self::loadReserveCols();
        $pdo = DB::conn();
        if (self::hasSplitReserve()) {
            $st = $pdo->prepare('SELECT qty_on_hand, COALESCE(qty_reserved_quote,0) AS rq, COALESCE(qty_reserved_order,0) AS ro FROM product_stocks WHERE product_id=? AND warehouse_id=?');
            $st->execute([$productId, $warehouseId]);
            $r = $st->fetch(PDO::FETCH_ASSOC) ?: ['qty_on_hand'=>0,'rq'=>0,'ro'=>0];
            return max(0, (int)$r['qty_on_hand'] - ((int)$r['rq'] + (int)$r['ro']));
        }
        $st = $pdo->prepare('SELECT qty_on_hand, COALESCE(qty_reserved,0) AS r FROM product_stocks WHERE product_id=? AND warehouse_id=?');
        $st->execute([$productId, $warehouseId]);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: ['qty_on_hand'=>0,'r'=>0];
        return max(0, (int)$r['qty_on_hand'] - (int)$r['r']);
    }

    public static function totalReserved(int $productId, int $warehouseId): int
    {
        self::loadReserveCols();
        $pdo = DB::conn();
        if (self::hasSplitReserve()) {
            $st = $pdo->prepare('SELECT COALESCE(qty_reserved_quote,0) + COALESCE(qty_reserved_order,0) FROM product_stocks WHERE product_id=? AND warehouse_id=?');
            $st->execute([$productId,$warehouseId]);
            return (int)($st->fetchColumn() ?: 0);
        }
        $st = $pdo->prepare('SELECT COALESCE(qty_reserved,0) FROM product_stocks WHERE product_id=? AND warehouse_id=?');
        $st->execute([$productId,$warehouseId]);
        return (int)($st->fetchColumn() ?: 0);
    }
    public static function nextCode(): string
    {
        $st = DB::conn()->query("SELECT LPAD(IFNULL(MAX(CAST(SUBSTRING(code,4) AS UNSIGNED)),0)+1,4,'0') AS seq
                                 FROM products WHERE code REGEXP '^PRD[0-9]+$'");
        $seq = (string)($st->fetchColumn() ?: '0001');
        return 'PRD' . $seq;
    }

    public static function all(?string $q = null, ?int $cat = null, ?int $make = null, ?int $model = null, int $limit = 100, int $offset = 0): array
    {
        // Use optimized query with proper index hints and subquery optimization
        $sql = "SELECT /*+ USE_INDEX(p, idx_products_category_make_model) */
                       p.id, p.code, p.name, p.category_id, p.make_id, p.model_id, p.cost, p.price,
                       c.name AS category_name, 
                       mk.name AS make_name, 
                       vm.name AS model_name,
                       COALESCE(stock_summary.total_on_hand, 0) AS on_hand,
                       COALESCE(stock_summary.total_reserved, 0) AS reserved
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN makes mk ON mk.id = p.make_id
                LEFT JOIN vehicle_models vm ON vm.id = p.model_id
                LEFT JOIN (
                    SELECT /*+ USE_INDEX(ps, idx_product_stocks_warehouse_qty) */
                           product_id,
                           SUM(qty_on_hand) as total_on_hand,
                           SUM(qty_reserved) as total_reserved
                    FROM product_stocks ps
                    GROUP BY product_id
                ) stock_summary ON stock_summary.product_id = p.id
                WHERE 1=1";
        
        $args = [];
        
        // Order WHERE conditions by selectivity (most selective first)
        if ($cat) { 
            $sql .= " AND p.category_id = ?"; 
            $args[] = $cat; 
        }
        if ($make) { 
            $sql .= " AND p.make_id = ?"; 
            $args[] = $make; 
        }
        if ($model) { 
            $sql .= " AND p.model_id = ?"; 
            $args[] = $model; 
        }
        if ($q) { 
            $sql .= " AND (p.name LIKE ? OR p.code LIKE ?)"; 
            $args[] = "%$q%"; 
            $args[] = "%$q%"; 
        }
        
        $sql .= " ORDER BY p.name LIMIT ? OFFSET ?";
        $args[] = $limit;
        $args[] = $offset;

        $st = DB::conn()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        // Use cache for frequently accessed products
        static $cache = [];
        if (isset($cache[$id])) {
            return $cache[$id];
        }
        
        $st = DB::conn()->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        $result = $r ?: null;
        
        // Cache the result
        $cache[$id] = $result;
        
        return $result;
    }
    
    /**
     * Optimized search method with ranking
     */
    public static function search(string $query, int $limit = 20): array
    {
        if (empty(trim($query))) {
            return [];
        }
        
        $sql = "SELECT /*+ USE_INDEX(p, idx_products_name_code) */
                       p.id, p.code, p.name, p.cost, p.price,
                       c.name as category_name,
                       mk.name as make_name,
                       vm.name as model_name,
                       CASE 
                           WHEN p.code = ? THEN 1
                           WHEN p.name = ? THEN 2
                           WHEN p.code LIKE ? THEN 3
                           WHEN p.name LIKE ? THEN 4
                           ELSE 5
                       END as relevance_score
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN makes mk ON mk.id = p.make_id
                LEFT JOIN vehicle_models vm ON vm.id = p.model_id
                WHERE p.name LIKE ? OR p.code LIKE ?
                ORDER BY relevance_score, p.name
                LIMIT ?";
        
        $searchTerm = "%{$query}%";
        $exactMatch = $query;
        $prefixMatch = $query . '%';
        
        $st = DB::conn()->prepare($sql);
        $st->execute([
            $exactMatch, $exactMatch,           // Exact matches
            $prefixMatch, $prefixMatch,         // Prefix matches
            $searchTerm, $searchTerm,           // General matches
            $limit
        ]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get products with low stock
     */
    public static function getLowStock(int $threshold = 5, ?int $warehouseId = null): array
    {
        $sql = "SELECT /*+ USE_INDEX(ps, idx_product_stocks_warehouse_qty) */
                       p.id, p.code, p.name, p.cost, p.price,
                       c.name as category_name,
                       w.name as warehouse_name,
                       ps.qty_on_hand,
                       ps.qty_reserved,
                       (ps.qty_on_hand - ps.qty_reserved) as available_qty
                FROM product_stocks ps
                INNER JOIN products p ON p.id = ps.product_id
                INNER JOIN warehouses w ON w.id = ps.warehouse_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE ps.qty_on_hand <= ?";
        
        $params = [$threshold];
        
        if ($warehouseId !== null) {
            $sql .= " AND ps.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        $sql .= " ORDER BY ps.qty_on_hand ASC, p.name";
        
        $st = DB::conn()->prepare($sql);
        $st->execute($params);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Batch load products by IDs
     */
    public static function findMultiple(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "SELECT p.*, c.name as category_name, mk.name as make_name, vm.name as model_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN makes mk ON mk.id = p.make_id
                LEFT JOIN vehicle_models vm ON vm.id = p.model_id
                WHERE p.id IN ($placeholders)
                ORDER BY p.name";
        
        $st = DB::conn()->prepare($sql);
        $st->execute($productIds);
        
        $results = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Index by product ID for easy lookup
        $indexed = [];
        foreach ($results as $product) {
            $indexed[(int)$product['id']] = $product;
        }
        
        return $indexed;
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare('INSERT INTO products (code, name, category_id, make_id, model_id, cost, price)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $data['code'], $data['name'],
            $data['category_id'] ?: null,
            $data['make_id'] ?: null,
            $data['model_id'] ?: null,
            $data['cost'], $data['price']
        ]);
        return (int)DB::conn()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = DB::conn()->prepare('UPDATE products SET code=?, name=?, category_id=?, make_id=?, model_id=?, cost=?, price=? WHERE id=?');
        $st->execute([
            $data['code'], $data['name'],
            $data['category_id'] ?: null,
            $data['make_id'] ?: null,
            $data['model_id'] ?: null,
            $data['cost'], $data['price'],
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM products WHERE id=?');
        return $st->execute([$id]);
    }

    /** stocks **/

    public static function stocks(int $productId): array
    {
        // Use cached warehouse data to avoid N+1 queries
        $warehouses = ReferenceDataCache::getWarehouses();
        
        // Get stock data for all warehouses at once
        $st = DB::conn()->prepare('SELECT warehouse_id, qty_on_hand, qty_reserved 
                                   FROM product_stocks WHERE product_id=?');
        $st->execute([$productId]);
        $stockData = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Create indexed array for quick lookup
        $stockMap = [];
        foreach ($stockData as $stock) {
            $stockMap[$stock['warehouse_id']] = $stock;
        }
        
        // Combine warehouse data with stock data
        $result = [];
        foreach ($warehouses as $warehouse) {
            $warehouseId = (int)$warehouse['id'];
            $stock = $stockMap[$warehouseId] ?? ['qty_on_hand' => 0, 'qty_reserved' => 0];
            
            $result[] = [
                'id' => $warehouseId,
                'name' => $warehouse['name'],
                'qty_on_hand' => (int)$stock['qty_on_hand'],
                'qty_reserved' => (int)$stock['qty_reserved']
            ];
        }
        
        return $result;
    }

    public static function saveStocks(int $productId, array $rows): void
    {
        $pdo = DB::conn();
        $ins = $pdo->prepare('INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved)
                              VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE qty_on_hand=VALUES(qty_on_hand), qty_reserved=VALUES(qty_reserved)');
        foreach ($rows as $wid => $pair) {
            $on  = max(0, (int)($pair['on'] ?? 0));
            $res = max(0, (int)($pair['res'] ?? 0));
            $ins->execute([$productId, (int)$wid, $on, $res]);
        }
    }
	
	public static function adjustReserved(int $productId, int $warehouseId, int $delta): void
	{
    $pdo = DB::conn();
    // upsert then clamp at >= 0
    $pdo->prepare('INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved)
                   VALUES (?, ?, 0, ?)
                   ON DUPLICATE KEY UPDATE qty_reserved = GREATEST(0, qty_reserved + VALUES(qty_reserved))')
        ->execute([$productId, $warehouseId, $delta]);
    }

    public static function adjustReservedQuote(int $productId, int $warehouseId, int $delta): void
    {
        self::loadReserveCols();
        if (!self::hasSplitReserve()) { self::adjustReserved($productId,$warehouseId,$delta); return; }
        DB::conn()->prepare('INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved_quote, qty_reserved_order)
                             VALUES (?, ?, 0, ?, 0)
                             ON DUPLICATE KEY UPDATE qty_reserved_quote = GREATEST(0, qty_reserved_quote + VALUES(qty_reserved_quote))')
                  ->execute([$productId, $warehouseId, $delta]);
    }

    public static function adjustReservedOrder(int $productId, int $warehouseId, int $delta): void
    {
        self::loadReserveCols();
        if (!self::hasSplitReserve()) { self::adjustReserved($productId,$warehouseId,$delta); return; }
        DB::conn()->prepare('INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved_quote, qty_reserved_order)
                             VALUES (?, ?, 0, 0, ?)
                             ON DUPLICATE KEY UPDATE qty_reserved_order = GREATEST(0, qty_reserved_order + VALUES(qty_reserved_order))')
                  ->execute([$productId, $warehouseId, $delta]);
    }

public static function consumeFromReservation(int $productId, int $warehouseId, int $qty): void
{
    // qty_reserved -= qty, qty_on_hand -= qty (clamped at >=0), single atomic statement
    DB::conn()->prepare("
      INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved)
      VALUES (?, ?, 0, 0)
      ON DUPLICATE KEY UPDATE
        qty_reserved = GREATEST(0, qty_reserved - ?),
        qty_on_hand = GREATEST(0, qty_on_hand - ?)
    ")->execute([$productId, $warehouseId, $qty, $qty]);
}

public static function consumeFromOrderReservation(int $productId, int $warehouseId, int $qty): void
{
    self::loadReserveCols();
    if (!self::hasSplitReserve()) { self::consumeFromReservation($productId,$warehouseId,$qty); return; }
    DB::conn()->prepare("
      INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved_order, qty_reserved_quote)
      VALUES (?, ?, 0, 0, 0)
      ON DUPLICATE KEY UPDATE
        qty_reserved_order = GREATEST(0, qty_reserved_order - ?),
        qty_on_hand = GREATEST(0, qty_on_hand - ?)
    ")->execute([$productId, $warehouseId, $qty, $qty]);
}

public static function transferReserveQuoteToOrder(int $productId, int $warehouseId, int $qty): void
{
    self::loadReserveCols();
    if (!self::hasSplitReserve()) {
        // No split available — keep single reservation
        return;
    }
    DB::conn()->prepare("
      INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved_quote, qty_reserved_order)
      VALUES (?, ?, 0, 0, 0)
      ON DUPLICATE KEY UPDATE
        qty_reserved_quote = GREATEST(0, qty_reserved_quote - ?),
        qty_reserved_order = qty_reserved_order + ?
    ")->execute([$productId, $warehouseId, $qty, $qty]);
}

    public static function reservedOrderQty(int $productId, int $warehouseId): int
    {
        self::loadReserveCols();
        $pdo = DB::conn();
        if (self::hasSplitReserve()) {
            $st = $pdo->prepare('SELECT COALESCE(qty_reserved_order,0) FROM product_stocks WHERE product_id=? AND warehouse_id=?');
            $st->execute([$productId,$warehouseId]);
            return (int)($st->fetchColumn() ?: 0);
        }
        $st = $pdo->prepare('SELECT COALESCE(qty_reserved,0) FROM product_stocks WHERE product_id=? AND warehouse_id=?');
        $st->execute([$productId,$warehouseId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public static function reservedBucketsForProduct(int $productId): array
    {
        self::loadReserveCols();
        $pdo = DB::conn();
        if (self::hasSplitReserve()) {
            $st = $pdo->prepare('SELECT SUM(COALESCE(qty_reserved_quote,0)) AS rq, SUM(COALESCE(qty_reserved_order,0)) AS ro FROM product_stocks WHERE product_id=?');
            $st->execute([$productId]);
            $r = $st->fetch(PDO::FETCH_ASSOC) ?: ['rq'=>0,'ro'=>0];
            return ['rq'=>(int)$r['rq'], 'ro'=>(int)$r['ro']];
        }
        $st = $pdo->prepare('SELECT SUM(COALESCE(qty_reserved,0)) AS r FROM product_stocks WHERE product_id=?');
        $st->execute([$productId]);
        $r = (int)($st->fetchColumn() ?: 0);
        return ['rq'=>0, 'ro'=>$r];
    }
public static function canFulfill(int $productId, int $warehouseId, int $qty): bool
{
    $st = DB::conn()->prepare(
        'SELECT qty_on_hand, qty_reserved FROM product_stocks WHERE product_id=? AND warehouse_id=?'
    );
    $st->execute([$productId, $warehouseId]);
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    $on  = (int)($row['qty_on_hand'] ?? 0);
    // here we check we have enough on-hand to consume the reservation
    return $on >= $qty;
}

}
