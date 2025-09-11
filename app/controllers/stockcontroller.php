<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Logger;

use function App\Core\require_auth;

final class StockController extends Controller
{
    /** GET /stock/available?product_id=&warehouse_id= — returns JSON */
    public function available(): void
    {
        require_auth();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $pid = (int)($_GET['product_id'] ?? 0);
        $wid = (int)($_GET['warehouse_id'] ?? 0);
        if ($pid <= 0 || $wid <= 0) {
            Logger::warning('Stock available: invalid parameters', [
                'product_id' => $pid,
                'warehouse_id' => $wid,
            ]);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product or warehouse']);
            return;
        }
        try {
            $st = DB::conn()->prepare("\n                SELECT p.id AS product_id, p.code, p.name AS product_name,\n                       w.id AS warehouse_id, w.name AS warehouse_name,\n                       COALESCE(ps.qty_on_hand,0) AS qty_on_hand,\n                       COALESCE(ps.qty_reserved,0) AS qty_reserved\n                  FROM products p\n                  CROSS JOIN warehouses w\n                  LEFT JOIN product_stocks ps\n                    ON ps.product_id = p.id AND ps.warehouse_id = w.id\n                 WHERE p.id = ? AND w.id = ?\n                 LIMIT 1\n            ");
            $st->execute([$pid, $wid]);
            $row = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                Logger::warning('Stock available: no row for given product/warehouse', [
                    'product_id' => $pid,
                    'warehouse_id' => $wid,
                ]);
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
                return;
            }
            $on  = (int)($row['qty_on_hand'] ?? 0);
            $res = (int)($row['qty_reserved'] ?? 0);
            $available = max(0, $on - $res);
            Logger::debug('Stock available: success', [
                'product_id' => $pid,
                'warehouse_id' => $wid,
                'qty_on_hand' => $on,
                'qty_reserved' => $res,
                'available' => $available,
            ]);
            echo json_encode([
                'product_id'     => (int)$row['product_id'],
                'product_code'   => (string)($row['code'] ?? ''),
                'product_name'   => (string)($row['product_name'] ?? ''),
                'warehouse_id'   => (int)$row['warehouse_id'],
                'warehouse_name' => (string)($row['warehouse_name'] ?? ''),
                'qty_on_hand'    => $on,
                'qty_reserved'   => $res,
                'available'      => $available,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Logger::error('Stock available: exception', [
                'product_id' => $pid,
                'warehouse_id' => $wid,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
}
