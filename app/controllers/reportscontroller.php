<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

use function App\Core\require_auth;

final class ReportsController extends Controller
{
    /** AP Aging (existing) */
    public function apaging(): void {
        require_auth();
        $asof = $_GET['asof'] ?? date('Y-m-d');

        $pdo = DB::conn();
        $sql = "
        SELECT s.id AS supplier_id, s.name AS supplier_name,
               SUM(CASE WHEN age<=30  THEN outstanding ELSE 0 END) AS bucket_0_30,
               SUM(CASE WHEN age BETWEEN 31 AND 60 THEN outstanding ELSE 0 END) AS bucket_31_60,
               SUM(CASE WHEN age BETWEEN 61 AND 90 THEN outstanding ELSE 0 END) AS bucket_61_90,
               SUM(CASE WHEN age>90  THEN outstanding ELSE 0 END) AS bucket_90_plus,
               SUM(outstanding) AS total
        FROM (
          SELECT pi.id, pi.supplier_id,
                 GREATEST(
                   pi.total
                   - COALESCE((
                       SELECT SUM(sp.amount) FROM supplier_payments sp
                       WHERE sp.purchase_invoice_id=pi.id AND sp.paid_at<=?
                     ),0)
                   - COALESCE((
                       SELECT SUM(pr.total) FROM purchase_returns pr
                       WHERE pr.purchase_invoice_id=pi.id AND pr.created_at<=?
                     ),0)
                 ,0) AS outstanding,
                 DATEDIFF(?, pi.created_at) AS age
          FROM purchase_invoices pi
          WHERE pi.created_at<=?
        ) x
        JOIN suppliers s ON s.id = x.supplier_id
        WHERE outstanding > 0
        GROUP BY s.id, s.name
        ORDER BY s.name";
        $st = $pdo->prepare($sql);
        $st->execute([$asof,$asof,$asof,$asof]);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $totals = ['b0'=>0,'b31'=>0,'b61'=>0,'b90'=>0,'total'=>0];
        foreach ($rows as $r) {
            $totals['b0']   += (float)$r['bucket_0_30'];
            $totals['b31']  += (float)$r['bucket_31_60'];
            $totals['b61']  += (float)$r['bucket_61_90'];
            $totals['b90']  += (float)$r['bucket_90_plus'];
            $totals['total']+= (float)$r['total'];
        }

        $this->view('reports/ap_aging', [
            'asof'   => $asof,
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /** AR Aging (new) */
    public function araging(): void {
        require_auth();
        $asof = $_GET['asof'] ?? date('Y-m-d');

        $pdo = DB::conn();
        $sql = "
        SELECT c.id AS customer_id, c.name AS customer_name,
               SUM(CASE WHEN age<=30  THEN outstanding ELSE 0 END) AS bucket_0_30,
               SUM(CASE WHEN age BETWEEN 31 AND 60 THEN outstanding ELSE 0 END) AS bucket_31_60,
               SUM(CASE WHEN age BETWEEN 61 AND 90 THEN outstanding ELSE 0 END) AS bucket_61_90,
               SUM(CASE WHEN age>90  THEN outstanding ELSE 0 END) AS bucket_90_plus,
               SUM(outstanding) AS total
        FROM (
          SELECT i.id, i.customer_id,
                 GREATEST(
                   i.total
                   - COALESCE((
                       SELECT SUM(ip.amount) FROM invoice_payments ip
                       WHERE ip.invoice_id=i.id AND ip.paid_at<=?
                     ),0)
                   - COALESCE((
                       SELECT SUM(sr.total) FROM sales_returns sr
                       WHERE sr.sales_invoice_id=i.id AND sr.created_at<=?
                     ),0)
                 ,0) AS outstanding,
                 DATEDIFF(?, i.created_at) AS age
          FROM invoices i
          WHERE i.created_at<=?
        ) x
        JOIN customers c ON c.id = x.customer_id
        WHERE outstanding > 0
        GROUP BY c.id, c.name
        ORDER BY c.name";
        $st = $pdo->prepare($sql);
        $st->execute([$asof,$asof,$asof,$asof]);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $totals = ['b0'=>0,'b31'=>0,'b61'=>0,'b90'=>0,'total'=>0];
        foreach ($rows as $r) {
            $totals['b0']   += (float)$r['bucket_0_30'];
            $totals['b31']  += (float)$r['bucket_31_60'];
            $totals['b61']  += (float)$r['bucket_61_90'];
            $totals['b90']  += (float)$r['bucket_90_plus'];
            $totals['total']+= (float)$r['total'];
        }

        $this->view('reports/ar_aging', [
            'asof'   => $asof,
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /** Inventory Valuation (weighted average) */
    public function inventoryvaluation(): void {
        require_auth();
        $pdo = DB::conn();
        $rows = $pdo->query("
            SELECT ps.product_id, ps.warehouse_id, ps.qty_on_hand, ps.avg_cost,
                   (ps.qty_on_hand * ps.avg_cost) AS value,
                   p.code AS product_code, p.name AS product_name,
                   w.name AS warehouse_name
            FROM product_stocks ps
            JOIN products p   ON p.id=ps.product_id
            JOIN warehouses w ON w.id=ps.warehouse_id
            ORDER BY w.name, p.code, p.name
        ")->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $tot = ['qty'=>0,'value'=>0.0];
        foreach ($rows as $r) {
            $tot['qty']   += (int)$r['qty_on_hand'];
            $tot['value'] += (float)$r['value'];
        }

        $this->view('reports/inventory_valuation', ['rows'=>$rows, 'tot'=>$tot]);
    }

    /** Sales summary (invoices, payments, returns) */
    public function sales(): void {
        require_auth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        if ($from > $to) { [$from,$to] = [$to,$from]; }

        $fromStart = $from.' 00:00:00';
        $toExclusive = date('Y-m-d H:i:s', strtotime($to.' 00:00:00 +1 day'));

        $pdo = DB::conn();
        $sql = "
            SELECT * FROM (
                SELECT i.created_at AS txn_date, 'invoice' AS kind,
                       COALESCE(i.inv_no, CAST(i.id AS CHAR)) AS ref_no,
                       c.name AS party, i.total AS debit, 0 AS credit,
                       i.id AS ref_id, i.id AS invoice_id
                FROM invoices i
                JOIN customers c ON c.id=i.customer_id
                WHERE i.created_at >= ? AND i.created_at < ?

                UNION ALL

                SELECT p.paid_at AS txn_date, 'payment' AS kind,
                       COALESCE(NULLIF(p.reference,''), CONCAT('PMT', LPAD(p.id,6,'0'))) AS ref_no,
                       c.name AS party, 0 AS debit, p.amount AS credit,
                       p.id AS ref_id, p.invoice_id AS invoice_id
                FROM invoice_payments p
                JOIN invoices i ON i.id=p.invoice_id
                JOIN customers c ON c.id=i.customer_id
                WHERE p.paid_at >= ? AND p.paid_at < ?

                UNION ALL

                SELECT sr.created_at AS txn_date, 'return' AS kind,
                       sr.sr_no AS ref_no, c.name AS party, 0 AS debit, sr.total AS credit,
                       sr.id AS ref_id, i.id AS invoice_id
                FROM sales_returns sr
                JOIN invoices i ON i.id=sr.sales_invoice_id
                JOIN customers c ON c.id=i.customer_id
                WHERE sr.created_at >= ? AND sr.created_at < ?
            ) t
            ORDER BY txn_date, kind
        ";

        $rows = [];
        try {
            $st = $pdo->prepare($sql);
            $st->execute([$fromStart,$toExclusive, $fromStart,$toExclusive, $fromStart,$toExclusive]);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Reports.sales union query failed', ['from'=>$from,'to'=>$to,'error'=>$e->getMessage()]);
            // Fallback: simple per-table merges with DATE() predicates
            try {
                $merged = [];
                $si = $pdo->prepare("SELECT i.created_at AS txn_date, 'invoice' AS kind, COALESCE(i.inv_no, CAST(i.id AS CHAR)) AS ref_no, c.name AS party, i.total AS debit, 0 AS credit, i.id AS ref_id, i.id AS invoice_id FROM invoices i JOIN customers c ON c.id=i.customer_id WHERE DATE(i.created_at) BETWEEN ? AND ?");
                $si->execute([$from,$to]); $merged = array_merge($merged, $si->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                $sp = $pdo->prepare("SELECT p.paid_at AS txn_date, 'payment' AS kind, COALESCE(NULLIF(p.reference,''), CONCAT('PMT', LPAD(p.id,6,'0'))) AS ref_no, c.name AS party, 0 AS debit, p.amount AS credit, p.id AS ref_id, p.invoice_id AS invoice_id FROM invoice_payments p JOIN invoices i ON i.id=p.invoice_id JOIN customers c ON c.id=i.customer_id WHERE DATE(p.paid_at) BETWEEN ? AND ?");
                $sp->execute([$from,$to]); $merged = array_merge($merged, $sp->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                $sr = $pdo->prepare("SELECT sr.created_at AS txn_date, 'return' AS kind, sr.sr_no AS ref_no, c.name AS party, 0 AS debit, sr.total AS credit, sr.id AS ref_id, i.id AS invoice_id FROM sales_returns sr JOIN invoices i ON i.id=sr.sales_invoice_id JOIN customers c ON c.id=i.customer_id WHERE DATE(sr.created_at) BETWEEN ? AND ?");
                $sr->execute([$from,$to]); $merged = array_merge($merged, $sr->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                usort($merged, function($a,$b){ return strcmp(($a['txn_date'] ?? ''), ($b['txn_date'] ?? '')); });
                $rows = $merged;
            } catch (\Throwable $e2) {
                \App\Core\Logger::error('Reports.sales fallback failed', ['from'=>$from,'to'=>$to,'error'=>$e2->getMessage()]);
                $rows = [];
            }
        }

        $totals = ['invoices'=>0.0,'returns'=>0.0,'payments'=>0.0,'net_sales'=>0.0];
        foreach ($rows as $r) {
            $totals['invoices'] += (float)($r['debit'] ?? 0);
            $totals['returns']  += (float)($r['credit'] ?? 0) * (($r['kind'] ?? '')==='return' ? 1 : 0);
            $totals['payments'] += (float)($r['credit'] ?? 0) * (($r['kind'] ?? '')==='payment' ? 1 : 0);
        }
        $totals['net_sales'] = $totals['invoices'] - $totals['returns'];

        $this->view('reports/sales', [
            'from'=>$from, 'to'=>$to, 'rows'=>$rows, 'totals'=>$totals,
        ]);
    }

    /** Purchasing summary (purchase invoices, supplier payments, purchase returns) */
    public function purchasing(): void {
        require_auth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        if ($from > $to) { [$from,$to] = [$to,$from]; }

        $fromStart = $from.' 00:00:00';
        $toExclusive = date('Y-m-d H:i:s', strtotime($to.' 00:00:00 +1 day'));

        $pdo = DB::conn();
        $sql = "
            SELECT * FROM (
                SELECT pi.created_at AS txn_date, 'invoice' AS kind,
                       COALESCE(pi.pi_no, CAST(pi.id AS CHAR)) AS ref_no,
                       s.name AS party, pi.total AS debit, 0 AS credit,
                       pi.id AS ref_id, pi.id AS invoice_id
                FROM purchase_invoices pi
                JOIN suppliers s ON s.id=pi.supplier_id
                WHERE pi.created_at >= ? AND pi.created_at < ?

                UNION ALL

                SELECT sp.paid_at AS txn_date, 'payment' AS kind,
                       COALESCE(NULLIF(sp.reference,''), CONCAT('SP', LPAD(sp.id,6,'0'))) AS ref_no,
                       s.name AS party, 0 AS debit, sp.amount AS credit,
                       sp.id AS ref_id, sp.purchase_invoice_id AS invoice_id
                FROM supplier_payments sp
                JOIN purchase_invoices pi ON pi.id=sp.purchase_invoice_id
                JOIN suppliers s ON s.id=pi.supplier_id
                WHERE sp.paid_at >= ? AND sp.paid_at < ?

                UNION ALL

                SELECT pr.created_at AS txn_date, 'return' AS kind,
                       pr.pr_no AS ref_no, s.name AS party, 0 AS debit, pr.total AS credit,
                       pr.id AS ref_id, pr.purchase_invoice_id AS invoice_id
                FROM purchase_returns pr
                JOIN suppliers s ON s.id=pr.supplier_id
                WHERE pr.created_at >= ? AND pr.created_at < ?
            ) t
            ORDER BY txn_date, kind
        ";

        $rows = [];
        try {
            $st = $pdo->prepare($sql);
            $st->execute([$fromStart,$toExclusive, $fromStart,$toExclusive, $fromStart,$toExclusive]);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Reports.purchasing union query failed', ['from'=>$from,'to'=>$to,'error'=>$e->getMessage()]);
            try {
                $merged = [];
                $piq = $pdo->prepare("SELECT pi.created_at AS txn_date, 'invoice' AS kind, COALESCE(pi.pi_no, CAST(pi.id AS CHAR)) AS ref_no, s.name AS party, pi.total AS debit, 0 AS credit, pi.id AS ref_id, pi.id AS invoice_id FROM purchase_invoices pi JOIN suppliers s ON s.id=pi.supplier_id WHERE DATE(pi.created_at) BETWEEN ? AND ?");
                $piq->execute([$from,$to]); $merged = array_merge($merged, $piq->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                $spq = $pdo->prepare("SELECT sp.paid_at AS txn_date, 'payment' AS kind, COALESCE(NULLIF(sp.reference,''), CONCAT('SP', LPAD(sp.id,6,'0'))) AS ref_no, s.name AS party, 0 AS debit, sp.amount AS credit, sp.id AS ref_id, sp.purchase_invoice_id AS invoice_id FROM supplier_payments sp JOIN purchase_invoices pi ON pi.id=sp.purchase_invoice_id JOIN suppliers s ON s.id=pi.supplier_id WHERE DATE(sp.paid_at) BETWEEN ? AND ?");
                $spq->execute([$from,$to]); $merged = array_merge($merged, $spq->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                $prq = $pdo->prepare("SELECT pr.created_at AS txn_date, 'return' AS kind, pr.pr_no AS ref_no, s.name AS party, 0 AS debit, pr.total AS credit, pr.id AS ref_id, pr.purchase_invoice_id AS invoice_id FROM purchase_returns pr JOIN suppliers s ON s.id=pr.supplier_id WHERE DATE(pr.created_at) BETWEEN ? AND ?");
                $prq->execute([$from,$to]); $merged = array_merge($merged, $prq->fetchAll(\PDO::FETCH_ASSOC) ?: []);
                usort($merged, function($a,$b){ return strcmp(($a['txn_date'] ?? ''), ($b['txn_date'] ?? '')); });
                $rows = $merged;
            } catch (\Throwable $e2) {
                \App\Core\Logger::error('Reports.purchasing fallback failed', ['from'=>$from,'to'=>$to,'error'=>$e2->getMessage()]);
                $rows = [];
            }
        }

        $totals = ['invoices'=>0.0,'returns'=>0.0,'payments'=>0.0,'net_purchases'=>0.0];
        foreach ($rows as $r) {
            $totals['invoices'] += (float)($r['debit'] ?? 0);
            $totals['returns']  += (float)($r['credit'] ?? 0) * (($r['kind'] ?? '')==='return' ? 1 : 0);
            $totals['payments'] += (float)($r['credit'] ?? 0) * (($r['kind'] ?? '')==='payment' ? 1 : 0);
        }
        $totals['net_purchases'] = $totals['invoices'] - $totals['returns'];

        $this->view('reports/purchasing', [
            'from'=>$from, 'to'=>$to, 'rows'=>$rows, 'totals'=>$totals,
        ]);
    }
}
