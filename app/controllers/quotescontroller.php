<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Note;
use App\Services\DocNumbers;
use App\Core\Logger;
use PDO;

use function App\Core\require_auth;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class QuotesController extends Controller
{
    public function index(): void {
        require_auth();
        $this->view('quotes/index', ['items' => Quote::all()]);
    }

    /** GET /quotes/create — preview number; do not allocate */
    public function create(): void {
        require_auth();

        $customers  = DB::conn()->query("SELECT id, name, phone FROM customers ORDER BY name")
                        ->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $products   = DB::conn()->query("SELECT id, code, name, price, CONCAT(code,' — ',name) AS label FROM products ORDER BY code")
                        ->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $warehouses = DB::conn()->query("SELECT id, name FROM warehouses ORDER BY name")
                        ->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $next_hint = DocNumbers::peek('q'); // non-binding preview

        $this->view('quotes/form', [
            'quote_no'   => '(assigned on save)',
            'next_hint'  => $next_hint,
            'customers'  => $customers,
            'products'   => $products,
            'warehouses' => $warehouses,
            'item_rows'  => 5,
        ]);
    }

    /** POST /quotes — allocate number now, then insert */
    public function store(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }

        $pdo = DB::conn();

        try {
            $items = $this->readItems();
            if (!$items) { throw new \RuntimeException('At least one item is required.'); }

            $customerId = (int)($_POST['customer_id'] ?? 0);
            $taxRate    = (float)($_POST['tax_rate'] ?? 0);
            $expiresAt  = trim((string)($_POST['expires_at'] ?? '')) ?: null;

            // Validate requested quantities against available stock per warehouse
            // Aggregate quantities for same (product_id, warehouse_id)
            $demands = [];
            foreach ($items as $r) {
                $pid = (int)$r['product_id'];
                $wid = (int)$r['warehouse_id'];
                $qty = max(1, (int)$r['qty']);
                $key = $pid.'@'.$wid;
                $demands[$key] = ($demands[$key] ?? ['pid'=>$pid,'wid'=>$wid,'qty'=>0]);
                $demands[$key]['qty'] += $qty;
            }

            // Check availability: available = GREATEST(qty_on_hand - qty_reserved, 0)
            $violations = [];
            $check = $pdo->prepare("\n                SELECT p.code, p.name AS product_name, w.name AS warehouse_name,\n                       COALESCE(ps.qty_on_hand,0) AS qty_on_hand,\n                       COALESCE(ps.qty_reserved,0) AS qty_reserved\n                  FROM products p\n                  CROSS JOIN warehouses w\n                  LEFT JOIN product_stocks ps\n                    ON ps.product_id = p.id AND ps.warehouse_id = w.id\n                 WHERE p.id = ? AND w.id = ?\n                 LIMIT 1\n            ");
            foreach ($demands as $d) {
                $check->execute([$d['pid'], $d['wid']]);
                $row = $check->fetch(\PDO::FETCH_ASSOC) ?: null;
                $on  = (int)($row['qty_on_hand'] ?? 0);
                $res = (int)($row['qty_reserved'] ?? 0);
                $available = max(0, $on - $res);
                if ($d['qty'] > $available) {
                    $violations[] = [
                        'product_id'    => $d['pid'],
                        'warehouse_id'  => $d['wid'],
                        'product_code'  => (string)($row['code'] ?? ''),
                        'product_name'  => (string)($row['product_name'] ?? ''),
                        'warehouse_name'=> (string)($row['warehouse_name'] ?? ''),
                        'requested'     => $d['qty'],
                        'available'     => $available,
                    ];
                }
            }

            if ($violations) {
                // Build a concise error message
                $parts = [];
                foreach ($violations as $v) {
                    $labelP = trim(($v['product_code'] ? ($v['product_code'].' ') : '').$v['product_name']);
                    $labelP = $labelP !== '' ? $labelP : ('Product #'.$v['product_id']);
                    $labelW = $v['warehouse_name'] !== '' ? $v['warehouse_name'] : ('Warehouse #'.$v['warehouse_id']);
                    $parts[] = sprintf('%s in %s: requested %d, available %d', $labelP, $labelW, $v['requested'], $v['available']);
                }
                throw new \RuntimeException('Insufficient stock for: '.implode('; ', $parts));
            }

            $subtotal = 0.0;
            foreach ($items as &$it) {
                $it['qty']        = max(1, (int)$it['qty']);
                $it['price']      = (float)$it['price'];
                $it['line_total'] = $it['qty'] * $it['price'];
                $subtotal        += $it['line_total'];
            }
            unset($it);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total     = $subtotal + $taxAmount;

            $quoteNo = DocNumbers::next('q');
            $initialStatus = 'draft'; // start in draft

            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO quotes (quote_no, customer_id, status, tax_rate, subtotal, tax_amount, total, expires_at, created_at)
                VALUES (?,?,?,?,?,?,?,?, NOW())
            ")->execute([$quoteNo, $customerId, $initialStatus, $taxRate, $subtotal, $taxAmount, $total, $expiresAt]);

            $qid = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare("
                INSERT INTO quote_items (quote_id, product_id, warehouse_id, qty, price, line_total)
                VALUES (?,?,?,?,?,?)
            ");
            foreach ($items as $row) {
                $insItem->execute([
                    $qid,
                    (int)$row['product_id'],
                    (int)$row['warehouse_id'],
                    (int)$row['qty'],
                    (float)$row['price'],
                    (float)$row['line_total'],
                ]);
            }

            $pdo->commit();
            flash_set('success', 'Quote created: '.$quoteNo);
            redirect('/quotes/show?id='.$qid);

        } catch (\Throwable $e) {
            try { if ($pdo->inTransaction()) { $pdo->rollBack(); } } catch (\Throwable $ignore) {}
            flash_set('error', 'Save failed: '.$e->getMessage());
            redirect('/quotes/create');
        }
    }

    /** GET /quotes/show?id=.. — pass flags for buttons */
    public function show(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }

        $status  = (string)($q['status'] ?? 'draft');
        $today   = date('Y-m-d');
        $expired = !empty($q['expires_at']) && $q['expires_at'] < $today;

        $can_mark_sent = ($status === 'draft');
        $can_convert   = in_array($status, ['draft','sent','accepted'], true);
        $can_cancel    = in_array($status, ['draft','sent'], true);
        $can_expire    = ($status !== 'expired');

        $items = Quote::items($id);
        // Derive totals defensively in case persisted totals are missing/zero
        $computedSubtotal = 0.0;
        foreach ($items as $it) { $computedSubtotal += (float)($it['line_total'] ?? 0); }
        $storedSubtotal = (float)($q['subtotal'] ?? 0);
        $taxRate = (float)($q['tax_rate'] ?? 0);
        $subtotal = $storedSubtotal > 0 ? $storedSubtotal : $computedSubtotal;
        $storedTax = (float)($q['tax_amount'] ?? 0);
        $taxAmount = $storedTax > 0 ? $storedTax : round($subtotal * ($taxRate/100), 2);
        $storedTotal = (float)($q['total'] ?? 0);
        $total = $storedTotal > 0 ? $storedTotal : ($subtotal + $taxAmount);

        $this->view('quotes/view', [
            'q'             => $q,
            'items'         => $items,
            'summary'       => ['subtotal'=>$subtotal,'tax_rate'=>$taxRate,'tax_amount'=>$taxAmount,'total'=>$total],
            'notes'         => Note::for('quote', $id),
            'can_mark_sent' => $can_mark_sent,
            'can_convert'   => $can_convert,
            'can_cancel'    => $can_cancel,
            'can_expire'    => $can_expire,
            'expired'       => $expired,
        ]);
    }

    /** POST /quotes/marksent — draft → sent */
    public function marksent(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }

        $id = (int)($_POST['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }

        if (($q['status'] ?? '') !== 'draft') {
            flash_set('error','Only draft quotes can be marked as sent.');
            redirect('/quotes/show?id='.$id);
        }
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $items = Quote::items($id);
            $demands = $this->aggregateDemands($items);
            // Ensure availability considering existing reservations
            $check = $pdo->prepare('SELECT qty_on_hand, qty_reserved FROM product_stocks WHERE product_id=? AND warehouse_id=?');
            $viol = [];
            foreach ($demands as $d) {
                $check->execute([$d['product_id'], $d['warehouse_id']]);
                $row = $check->fetch(\PDO::FETCH_ASSOC) ?: ['qty_on_hand'=>0,'qty_reserved'=>0];
                $available = max(0, (int)$row['qty_on_hand'] - (int)$row['qty_reserved']);
                if ($d['qty'] > $available) {
                    $viol[] = sprintf('P#%d W#%d need %d, available %d', $d['product_id'], $d['warehouse_id'], $d['qty'], $available);
                }
            }
            if ($viol) {
                $pdo->rollBack();
                flash_set('error', 'Cannot mark sent. Insufficient stock to reserve: '.implode('; ', $viol));
                redirect('/quotes/show?id='.$id);
            }

            // Reserve quantities
            foreach ($demands as $d) {
                Product::adjustReserved((int)$d['product_id'], (int)$d['warehouse_id'], (int)$d['qty']);
            }
            $pdo->prepare("UPDATE quotes SET status='sent' WHERE id=?")->execute([$id]);
            $pdo->commit();
            Logger::info('Quote reservations booked', ['quote_id'=>$id, 'items'=>$demands]);
            flash_set('success','Quote marked as sent.');
            redirect('/quotes/show?id='.$id);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            Logger::error('Mark sent failed', ['quote_id'=>$id, 'error'=>$e->getMessage()]);
            flash_set('error','Mark sent failed: '.$e->getMessage());
            redirect('/quotes/show?id='.$id);
        }
    }

    /** POST /quotes/createorder — Q→SO */
public function createorder(): void {
    require_auth();
    if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }

    // Accept both names in case the view posts either one
    $quoteId = (int)($_POST['quote_id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);
    if ($quoteId <= 0) { flash_set('error','Missing quote id.'); redirect('/quotes'); }

    $q = \App\Models\Quote::find($quoteId);
    if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }

    $pdo = \App\Core\DB::conn();
    $pdo->beginTransaction();
    try {
        // Mirror number QYYYY-#### → SOYYYY-#### with suffix fallback if taken
        $baseSoNo = $this->swapPrefix((string)$q['quote_no'], 'Q', 'SO');
        $soNo     = $this->uniqueNo($baseSoNo, 'sales_orders', 'so_no', $pdo);

        // IMPORTANT: include quote_id to satisfy FK fk_so_quote
        $pdo->prepare("
            INSERT INTO sales_orders
                (quote_id, so_no, customer_id, status, tax_rate, subtotal, tax_amount, total, created_at)
            VALUES
                (?,?,?,?,?,?,?, ?, NOW())
        ")->execute([
            (int)$quoteId,
            $soNo,
            (int)$q['customer_id'],
            'draft',                                  // initial SO status; adjust if needed
            (float)$q['tax_rate'],
            (float)$q['subtotal'],
            (float)$q['tax_amount'],
            (float)$q['total'],
        ]);

        $soId = (int)$pdo->lastInsertId();

        // Copy lines from quote → sales order
        $copy = $pdo->prepare("
            SELECT product_id, warehouse_id, qty, price, line_total
            FROM quote_items WHERE quote_id=?
        ");
        $ins  = $pdo->prepare("
            INSERT INTO sales_order_items
                (sales_order_id, product_id, warehouse_id, qty, price, line_total)
            VALUES (?,?,?,?,?,?)
        ");
        $copy->execute([$quoteId]);
        while ($row = $copy->fetch(\PDO::FETCH_ASSOC)) {
            $ins->execute([
                $soId,
                (int)$row['product_id'],
                (int)$row['warehouse_id'],
                (int)$row['qty'],
                (float)$row['price'],
                (float)$row['line_total'],
            ]);
        }

        // (Optional) reflect acceptance on the quote after conversion
        $pdo->prepare("
            UPDATE quotes
               SET status = CASE WHEN status IN ('draft','sent') THEN 'accepted' ELSE status END
             WHERE id = ?
        ")->execute([$quoteId]);

        $pdo->commit();
        \App\Core\flash_set('success','Sales Order created: '.$soNo);
        \App\Core\redirect('/orders/show?id='.$soId);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        \App\Core\flash_set('error','Create order failed: '.$e->getMessage());
        \App\Core\redirect('/quotes/show?id='.$quoteId);
    }
}


    /** POST /quotes/cancel */
    public function cancel(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }
        $id = (int)($_POST['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }
        if (!in_array(($q['status'] ?? ''), ['draft','sent'], true)) {
            flash_set('error','Only draft/sent quotes can be cancelled.');
            redirect('/quotes/show?id='.$id);
        }
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Release reservations only if it was previously reserved (sent)
            if (($q['status'] ?? '') === 'sent') {
                $items = Quote::items($id);
                $demands = $this->aggregateDemands($items);
                foreach ($demands as $d) {
                    Product::adjustReserved((int)$d['product_id'], (int)$d['warehouse_id'], - (int)$d['qty']);
                }
                Logger::info('Quote reservations released on cancel', ['quote_id'=>$id, 'items'=>$demands]);
            }
            $pdo->prepare("UPDATE quotes SET status='cancelled' WHERE id=?")->execute([$id]);
            $pdo->commit();
            flash_set('success','Quote cancelled.');
            redirect('/quotes/show?id='.$id);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            Logger::error('Cancel quote failed', ['quote_id'=>$id, 'error'=>$e->getMessage()]);
            flash_set('error','Cancel failed: '.$e->getMessage());
            redirect('/quotes/show?id='.$id);
        }
    }

    /** POST /quotes/markexpired */
    public function markexpired(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }
        $id = (int)($_POST['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }
        if (($q['status'] ?? '') === 'expired') {
            flash_set('error','Quote already expired.');
            redirect('/quotes/show?id='.$id);
        }
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            if (($q['status'] ?? '') === 'sent') {
                $items = Quote::items($id);
                $demands = $this->aggregateDemands($items);
                foreach ($demands as $d) {
                    Product::adjustReserved((int)$d['product_id'], (int)$d['warehouse_id'], - (int)$d['qty']);
                }
                Logger::info('Quote reservations released on expire', ['quote_id'=>$id, 'items'=>$demands]);
            }
            $pdo->prepare("UPDATE quotes SET status='expired' WHERE id=?")->execute([$id]);
            $pdo->commit();
            flash_set('success','Quote marked as expired.');
            redirect('/quotes/show?id='.$id);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            Logger::error('Mark expired failed', ['quote_id'=>$id, 'error'=>$e->getMessage()]);
            flash_set('error','Mark expired failed: '.$e->getMessage());
            redirect('/quotes/show?id='.$id);
        }
    }

    /** POST /quotes/delete — delete only if status is draft */
    public function destroy(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/quotes'); }

        $id = (int)($_POST['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }
        if (($q['status'] ?? '') !== 'draft') {
            flash_set('error','Only draft quotes can be deleted.');
            redirect('/quotes/show?id='.$id);
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // quote_items are ON DELETE CASCADE via fk_qi_quote
            $del = $pdo->prepare('DELETE FROM quotes WHERE id=?');
            $del->execute([$id]);
            // Clean up associated notes (soft relationship)
            $pdo->prepare('DELETE FROM notes WHERE entity_type=\'quote\' AND entity_id=?')->execute([$id]);
            $pdo->commit();
            \App\Core\flash_set('success','Quote deleted.');
            \App\Core\redirect('/quotes');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            \App\Core\flash_set('error','Delete failed: '.$e->getMessage());
            \App\Core\redirect('/quotes/show?id='.$id);
        }
    }

    /** Print */
    public function printpage(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        $q  = Quote::find($id);
        if (!$q) { flash_set('error','Quote not found.'); redirect('/quotes'); }
        $items = Quote::items($id);
        // Derive totals defensively
        $computedSubtotal = 0.0; foreach ($items as $it) { $computedSubtotal += (float)($it['line_total'] ?? 0); }
        $taxRate = (float)($q['tax_rate'] ?? 0);
        $subtotal = max((float)($q['subtotal'] ?? 0), $computedSubtotal);
        $taxAmount = max((float)($q['tax_amount'] ?? 0), round($subtotal * ($taxRate/100), 2));
        $total = max((float)($q['total'] ?? 0), $subtotal + $taxAmount);
        $include = isset($_GET['include_notes']) && $_GET['include_notes'] === '1';
        $publicNotes = $include ? Note::publicFor('quote', $id) : [];
        $this->view_raw('quotes/print', [
            'q' => $q,
            'items' => $items,
            'summary' => ['subtotal'=>$subtotal,'tax_rate'=>$taxRate,'tax_amount'=>$taxAmount,'total'=>$total],
            'public_notes' => $publicNotes,
            'include_notes' => $include,
        ]);
    }

    /* ------------ helpers ------------ */

    private function readItems(): array {
        $rows=[]; $pids=$_POST['product_id']??[]; $wids=$_POST['warehouse_id']??[]; $qtys=$_POST['qty']??[]; $prices=$_POST['price']??[];
        $n = max(count($pids),count($wids),count($qtys),count($prices));
        for ($i=0;$i<$n;$i++) {
            $pid=(int)($pids[$i]??0); $wid=(int)($wids[$i]??0); $q=(int)($qtys[$i]??0); $pr=(float)($prices[$i]??0);
            if ($pid>0 && $wid>0 && $q>0 && $pr>=0) $rows[]=['product_id'=>$pid,'warehouse_id'=>$wid,'qty'=>$q,'price'=>$pr];
        }
        return $rows;
    }

    private function swapPrefix(string $src, string $from, string $to): string {
        if (preg_match('/^'.preg_quote($from,'/').'(?P<yr>\d{4})-(?P<seq>\d{4})$/i', $src, $m)) {
            return sprintf('%s%s-%s', $to, $m['yr'], $m['seq']);
        }
        if (stripos($src, $from) === 0) return $to.substr($src, strlen($from));
        return $to.$src;
    }

    private function uniqueNo(string $base, string $table, string $col, PDO $pdo): string {
        $chk=$pdo->prepare("SELECT 1 FROM {$table} WHERE {$col}=? LIMIT 1");
        $try=$base; $chk->execute([$try]);
        if (!$chk->fetchColumn()) return $try;
        foreach (range('A','Z') as $ch) { $try=$base.'-'.$ch; $chk->execute([$try]); if(!$chk->fetchColumn()) return $try; }
        for ($i=2;$i<100;$i++) { $try=$base.'-'.$i; $chk->execute([$try]); if(!$chk->fetchColumn()) return $try; }
        return $base.'-'.date('His');
    }

    /**
     * Aggregate rows by (product_id, warehouse_id)
     * @param array $items rows from Quote::items()
     * @return array [['product_id'=>..,'warehouse_id'=>..,'qty'=>..], ...]
     */
    private function aggregateDemands(array $items): array {
        $demands = [];
        foreach ($items as $r) {
            $pid = (int)($r['product_id'] ?? 0);
            $wid = (int)($r['warehouse_id'] ?? 0);
            $qty = max(1, (int)($r['qty'] ?? 0));
            if ($pid<=0 || $wid<=0 || $qty<=0) continue;
            $key = $pid.'@'.$wid;
            if (!isset($demands[$key])) $demands[$key] = ['product_id'=>$pid,'warehouse_id'=>$wid,'qty'=>0];
            $demands[$key]['qty'] += $qty;
        }
        return array_values($demands);
    }
}
