<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use PDO;

use function App\Core\require_auth;

final class ReservationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        $pdo = DB::conn();

        // Quote reservations: show quotes that are currently holding reservations (status = sent)
        $qSql = "SELECT q.id, q.quote_no AS doc_no, q.created_at AS doc_date, q.expires_at,
                        c.name AS customer_name,
                        'quote' AS scope,
                        SUM(qi.qty) AS qty_reserved,
                        SUM(qi.qty * qi.price) AS value_reserved
                   FROM quotes q
                   JOIN customers c   ON c.id = q.customer_id
                   JOIN quote_items qi ON qi.quote_id = q.id
                  WHERE q.status = 'sent'
               GROUP BY q.id, q.quote_no, q.created_at, q.expires_at, c.name
               ORDER BY q.created_at DESC";
        $quotes = $pdo->query($qSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Order reservations: show orders likely holding reservations (open/draft)
        $oSql = "SELECT so.id, so.so_no AS doc_no, so.created_at AS doc_date, NULL AS expires_at,
                        c.name AS customer_name,
                        'order' AS scope,
                        SUM(soi.qty) AS qty_reserved,
                        SUM(soi.qty * soi.price) AS value_reserved
                   FROM sales_orders so
                   JOIN customers c     ON c.id = so.customer_id
                   JOIN sales_order_items soi ON soi.sales_order_id = so.id
                  WHERE COALESCE(so.status,'open') IN ('open','draft')
               GROUP BY so.id, so.so_no, so.created_at, c.name
               ORDER BY so.created_at DESC";
        $orders = $pdo->query($oSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Merge and compute totals
        $rows = array_merge($quotes, $orders);
        $tot_qty = 0; $tot_val = 0.0;
        foreach ($rows as $r) { $tot_qty += (int)($r['qty_reserved'] ?? 0); $tot_val += (float)($r['value_reserved'] ?? 0.0); }

        $this->view('reservations/index', [
            'rows' => $rows,
            'tot_qty' => $tot_qty,
            'tot_val' => $tot_val,
        ]);
    }
}

