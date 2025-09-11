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

        // Filters
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== '' ? (int)$_GET['customer_id'] : null;
        $scope      = isset($_GET['scope']) && in_array($_GET['scope'], ['quote','order'], true) ? $_GET['scope'] : null;
        $from       = trim((string)($_GET['from'] ?? '')) ?: null;
        $to         = trim((string)($_GET['to'] ?? '')) ?: null;

        $whereQ = ['q.status = ?'];
        $argsQ  = ['sent'];
        if ($customerId) { $whereQ[] = 'q.customer_id = ?'; $argsQ[] = $customerId; }
        if ($from) { $whereQ[] = 'q.created_at >= ?'; $argsQ[] = $from.' 00:00:00'; }
        if ($to)   { $whereQ[] = 'q.created_at <= ?'; $argsQ[] = $to.' 23:59:59'; }
        $qSql = "SELECT q.id, q.quote_no AS doc_no, q.created_at AS doc_date, q.expires_at,
                        c.name AS customer_name,
                        'quote' AS scope,
                        SUM(qi.qty) AS qty_reserved,
                        SUM(qi.qty * qi.price) AS value_reserved
                   FROM quotes q
                   JOIN customers c   ON c.id = q.customer_id
                   JOIN quote_items qi ON qi.quote_id = q.id
                  WHERE ".implode(' AND ', $whereQ)."
               GROUP BY q.id, q.quote_no, q.created_at, q.expires_at, c.name
               ORDER BY q.created_at DESC";
        $stQ = $pdo->prepare($qSql); $stQ->execute($argsQ);
        $quotes = $stQ->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $whereO = ["(so.status IN ('open','draft','') OR so.status IS NULL)"];
        $argsO  = [];
        if ($customerId) { $whereO[] = 'so.customer_id = ?'; $argsO[] = $customerId; }
        if ($from) { $whereO[] = 'so.created_at >= ?'; $argsO[] = $from.' 00:00:00'; }
        if ($to)   { $whereO[] = 'so.created_at <= ?'; $argsO[] = $to.' 23:59:59'; }
        $oSql = "SELECT so.id, so.so_no AS doc_no, so.created_at AS doc_date, NULL AS expires_at,
                        c.name AS customer_name,
                        'order' AS scope,
                        SUM(soi.qty) AS qty_reserved,
                        SUM(soi.qty * soi.price) AS value_reserved
                   FROM sales_orders so
                   JOIN customers c     ON c.id = so.customer_id
                   JOIN sales_order_items soi ON soi.sales_order_id = so.id
                  WHERE ".implode(' AND ', $whereO)."
               GROUP BY so.id, so.so_no, so.created_at, c.name
               ORDER BY so.created_at DESC";
        $stO = $pdo->prepare($oSql); $stO->execute($argsO);
        $orders = $stO->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Merge according to scope filter
        if ($scope === 'quote')      { $rows = $quotes; }
        elseif ($scope === 'order')  { $rows = $orders; }
        else                         { $rows = array_merge($quotes, $orders); }
        $tot_qty = 0; $tot_val = 0.0;
        foreach ($rows as $r) { $tot_qty += (int)($r['qty_reserved'] ?? 0); $tot_val += (float)($r['value_reserved'] ?? 0.0); }

        // Customers for filter
        $customers = $pdo->query('SELECT id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->view('reservations/index', [
            'rows' => $rows,
            'tot_qty' => $tot_qty,
            'tot_val' => $tot_val,
            'filters' => [
                'customer_id' => $customerId,
                'scope'       => $scope,
                'from'        => $from,
                'to'          => $to,
                'customers'   => $customers,
            ],
        ]);
    }

    public function detail(): void
    {
        require_auth();
        $pdo = DB::conn();
        $scope = (string)($_GET['scope'] ?? '');
        $id    = (int)($_GET['id'] ?? 0);
        if (!in_array($scope, ['quote','order'], true) || $id <= 0) {
            header('Location: '.\App\Core\base_url('/reservations')); exit;
        }
        if ($scope === 'quote') {
            $hdr = $pdo->prepare('SELECT q.*, c.name AS customer_name FROM quotes q JOIN customers c ON c.id=q.customer_id WHERE q.id=?');
            $hdr->execute([$id]); $doc = $hdr->fetch(PDO::FETCH_ASSOC) ?: null;
            $st  = $pdo->prepare('SELECT qi.*, p.code AS product_code, p.name AS product_name, w.name AS warehouse_name FROM quote_items qi JOIN products p ON p.id=qi.product_id JOIN warehouses w ON w.id=qi.warehouse_id WHERE qi.quote_id=? ORDER BY qi.id');
            $st->execute([$id]); $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $hdr = $pdo->prepare('SELECT so.*, c.name AS customer_name FROM sales_orders so JOIN customers c ON c.id=so.customer_id WHERE so.id=?');
            $hdr->execute([$id]); $doc = $hdr->fetch(PDO::FETCH_ASSOC) ?: null;
            $st  = $pdo->prepare('SELECT soi.*, p.code AS product_code, p.name AS product_name, w.name AS warehouse_name FROM sales_order_items soi JOIN products p ON p.id=soi.product_id JOIN warehouses w ON w.id=soi.warehouse_id WHERE soi.sales_order_id=? ORDER BY soi.id');
            $st->execute([$id]); $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $this->view('reservations/detail', ['scope'=>$scope,'doc'=>$doc,'lines'=>$lines]);
    }
}
