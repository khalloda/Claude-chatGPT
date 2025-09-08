<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Note;
use PDO;

use function App\Core\require_auth;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class PurchaseInvoicesController extends Controller
{
    public function index(): void {
        require_auth();
        $this->view('purchaseinvoices/index', ['items' => PurchaseInvoice::all()]);
    }

    public function show(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        $pi = \App\Models\PurchaseInvoice::find($id);
        if (!$pi) { flash_set('error','Invoice not found.'); redirect('/purchaseinvoices'); }

        $items       = \App\Models\PurchaseInvoice::poItems($id); // items from the linked PO
        $receivedMap = \App\Models\PurchaseInvoice::receivedMapByPo((int)$pi['purchase_order_id']);
        $receipts    = \App\Models\PurchaseInvoice::receipts($id);
        $payments    = \App\Models\SupplierPayment::forInvoice($id);
        $credits_total = \App\Models\PurchaseReturn::creditsTotalForInvoice($id);
        $pr_returns    = \App\Models\PurchaseReturn::returnsForInvoice($id);
        $rec_map       = \App\Models\PurchaseReturn::receivedMapByInvoice($id);
        $ret_map       = \App\Models\PurchaseReturn::returnedMapByInvoice($id);

        $this->view('purchaseinvoices/view', [
            'pi'       => $pi,
            'items'    => $items,
            'received' => $receivedMap,
            'receipts' => $receipts,
            'payments' => $payments,
            'credits_total' => $credits_total,
            'pr_returns'    => $pr_returns,
            'rec_map'       => $rec_map,
            'ret_map'       => $ret_map,
            'notes'    => \App\Models\Note::for('purchase_invoice', $id),
        ]);
    }
	
    /**
     * Create a PI from a PO:
     * - PI number mirrors PO number:  POYYYY-#### -> PIYYYY-####
     * - If that number already exists, we try suffixes: -A, -B, ... to keep it readable.
     *   (This handles legacy PIs created earlier with a sequence.)
     */
    public function createfrompo(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/purchaseorders'); }
		
        $poId = (int)($_POST['purchase_order_id'] ?? 0);
        $po = PurchaseOrder::find($poId);
        if (!$po) { flash_set('error','PO not found.'); redirect('/purchaseorders'); }

        // If you want strict 1:1 (only one PI per PO), uncomment the next four lines:
        // $exists = DB::conn()->prepare("SELECT id FROM purchase_invoices WHERE purchase_order_id=? LIMIT 1");
        // $exists->execute([$poId]);
        // if ($row = $exists->fetch(PDO::FETCH_ASSOC)) { flash_set('success','Invoice already exists.'); redirect('/purchaseinvoices/show?id='.(int)$row['id']); return; }

        $pdo = DB::conn(); 
		$pdo->beginTransaction();
        try {
			            // Build PI number based on PO number
            $piNo = PurchaseInvoice::nextNumber();
            $ins = $pdo->prepare("INSERT INTO purchase_invoices
                (pi_no, purchase_order_id, supplier_id, subtotal, tax_rate, tax_amount, total, status, created_at)
                VALUES (?,?,?,?,?,?,?, 'open', NOW())");
            $ins->execute([
                $piNo, $poId, (int)$po['supplier_id'], (float)$po['subtotal'],
                (float)$po['tax_rate'], (float)$po['tax_amount'], (float)$po['total']
            ]);
            $piId = (int)$pdo->lastInsertId();

            $pdo->commit();
            flash_set('success', 'Purchase invoice '.$piNo.' created.');
            redirect('/purchaseinvoices/show?id='.$piId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash_set('error','Create invoice failed: '.$e->getMessage());
            redirect('/purchaseorders/show?id='.$poId);
        }
    }

    /** Receive selected quantities posted from the PI view */
    public function receive(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/purchaseinvoices'); }

        $piId   = (int)($_POST['purchase_invoice_id'] ?? 0);
        $pi     = \App\Models\PurchaseInvoice::find($piId);
        if (!$pi) { flash_set('error','Invoice not found.'); redirect('/purchaseinvoices'); }

        // Arrays from the form
        $poItemIds = $_POST['rec_po_item_id'] ?? [];
        $qtys      = $_POST['rec_qty'] ?? [];

        $pdo = DB::conn(); $pdo->beginTransaction();
        try {
            foreach ($poItemIds as $i => $poItemIdRaw) {
                $poItemId = (int)$poItemIdRaw;
                $recv     = max(0, (int)($qtys[$i] ?? 0));
                if ($poItemId <= 0 || $recv <= 0) { continue; }

                // Load PO item (and lock it)
                $st = $pdo->prepare("
                    SELECT id, purchase_order_id, product_id, warehouse_id, qty, price,
                           COALESCE(received_qty,0) AS received_qty
                    FROM purchase_order_items
                    WHERE id=? FOR UPDATE
                ");
                $st->execute([$poItemId]);
                $it = $st->fetch(\PDO::FETCH_ASSOC);
                if (!$it) { throw new \RuntimeException('PO item not found: '.$poItemId); }
                if ((int)$it['purchase_order_id'] !== (int)$pi['purchase_order_id']) {
                    throw new \RuntimeException('Item does not belong to this PO.');
                }

                $remaining = max(0, (int)$it['qty'] - (int)$it['received_qty']);
                if ($remaining <= 0) { continue; }
                $recv = min($recv, $remaining);

                // Upsert stock with weighted avg cost (lock stock row if exists)
                $this->upsertStock(
                    $pdo,
                    (int)$it['product_id'],
                    (int)$it['warehouse_id'],
                    $recv,
                    (float)$it['price'],
                    (int)$piId
                );

                // Update received_qty (cap at qty)
                $pdo->prepare("
                    UPDATE purchase_order_items
                       SET received_qty = LEAST(qty, COALESCE(received_qty,0) + ?)
                     WHERE id=?")->execute([$recv, $poItemId]);
            }

            // Update PO status
            $this->refreshPoReceiveStatus($pdo, (int)$pi['purchase_order_id']);

            $pdo->commit();
            flash_set('success','Items received.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash_set('error','Receive failed: '.$e->getMessage());
        }
        redirect('/purchaseinvoices/show?id='.$piId);
    }

    /** Receive the remaining qty for every line on the linked PO */
    public function receiveall(): void {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/purchaseinvoices'); }

        $piId   = (int)($_POST['purchase_invoice_id'] ?? 0);
        $pi     = \App\Models\PurchaseInvoice::find($piId);
        if (!$pi) { flash_set('error','Invoice not found.'); redirect('/purchaseinvoices'); }

        $pdo = DB::conn(); $pdo->beginTransaction();
        try {
            $rows = $pdo->prepare("
                SELECT id, product_id, warehouse_id, qty, price,
                       COALESCE(received_qty,0) AS received_qty
                FROM purchase_order_items
                WHERE purchase_order_id=? FOR UPDATE
            ");
            $rows->execute([(int)$pi['purchase_order_id']]);
            while ($it = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $remaining = max(0, (int)$it['qty'] - (int)$it['received_qty']);
                if ($remaining <= 0) { continue; }

                $this->upsertStock(
                    $pdo,
                    (int)$it['product_id'],
                    (int)$it['warehouse_id'],
                    (int)$remaining,
                    (float)$it['price'],
                    (int)$piId
                );

                $pdo->prepare("
                    UPDATE purchase_order_items
                       SET received_qty = qty
                     WHERE id=?")->execute([(int)$it['id']]);
            }

            $this->refreshPoReceiveStatus($pdo, (int)$pi['purchase_order_id']);

            $pdo->commit();
            flash_set('success','All remaining quantities received.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash_set('error','Receive all failed: '.$e->getMessage());
        }
        redirect('/purchaseinvoices/show?id='.$piId);
    }

    public function printpage(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        $includeNotes = isset($_GET['include_notes']) && $_GET['include_notes'] === '1';
        $pi = PurchaseInvoice::find($id);
        if (!$pi) { flash_set('error','Invoice not found.'); redirect('/purchaseinvoices'); }

        $items = PurchaseInvoice::poItems($id);
        $publicNotes = $includeNotes ? Note::publicFor('purchase_invoice', $id) : [];
        $this->view_raw('purchaseinvoices/print', [
            'pi' => $pi,
            'items' => $items,
            'public_notes' => $publicNotes,
            'include_notes' => $includeNotes,
        ]);
    }

    /* ---------- helpers ---------- */

    /** Upsert product_stocks with weighted average and write inventory_ledger */
    private function upsertStock(\PDO $pdo, int $productId, int $warehouseId, int $qty, float $unitCost, int $piId): void
    {
        // Lock existing stock row (if any)
        $sel = $pdo->prepare("SELECT id, qty_on_hand, avg_cost FROM product_stocks WHERE product_id=? AND warehouse_id=? FOR UPDATE");
        $sel->execute([$productId, $warehouseId]);
        $row = $sel->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $oldQty = (float)$row['qty_on_hand'];
            $oldAvg = (float)($row['avg_cost'] ?? 0.0);
            $newQty = $oldQty + $qty;
            $newAvg = $newQty > 0 ? (($oldQty * $oldAvg) + ($qty * $unitCost)) / $newQty : $unitCost;

            $upd = $pdo->prepare("UPDATE product_stocks SET qty_on_hand=?, avg_cost=? WHERE id=?");
            $upd->execute([$newQty, round($newAvg, 4), (int)$row['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved, avg_cost)
                                  VALUES (?,?,?,?,?)");
            $ins->execute([$productId, $warehouseId, $qty, 0, round($unitCost, 4)]);
        }

        // Ledger row (+qty, value at unitCost)
        $pdo->prepare("
            INSERT INTO inventory_ledger
                (product_id, warehouse_id, doc_type, doc_id, qty_delta, unit_cost, value_delta)
            VALUES (?,?,?,?,?,?,?)")->execute([
                $productId, $warehouseId, 'po_receive', $piId, +$qty, $unitCost, $qty * $unitCost
            ]);
    }

    /** Update PO status based on received vs ordered */
    private function refreshPoReceiveStatus(\PDO $pdo, int $poId): void
    {
        $row = $pdo->query("
            SELECT
              SUM(qty) AS total_qty,
              SUM(LEAST(qty, COALESCE(received_qty,0))) AS rec_qty
            FROM purchase_order_items
            WHERE purchase_order_id = ".(int)$poId
        )->fetch(\PDO::FETCH_ASSOC) ?: ['total_qty'=>0,'rec_qty'=>0];

        $total = (float)($row['total_qty'] ?? 0);
        $rec   = (float)($row['rec_qty'] ?? 0);
        $status = ($rec <= 0) ? 'ordered' : (($rec < $total) ? 'partially_received' : 'received');

        $pdo->prepare("UPDATE purchase_orders SET status=? WHERE id=?")->execute([$status, $poId]);
    }

    /**
     * POST /purchaseinvoices/receive  (and legacy alias: /receipts)
     * Consumes arrays: rec_product_id[], rec_warehouse_id[], rec_qty[], rec_price[]
     * - Caps over-receipts to remaining (ordered - already received)
     * - Upserts product_stocks (qty_on_hand, avg_cost) per (product, warehouse)
     * - Logs each receipt row in purchase_receipts for audit
     */
    public function receive(): void
    {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/purchaseinvoices'); }

        $piId = (int)($_POST['invoice_id'] ?? $_POST['id'] ?? 0);
        if ($piId <= 0) { flash_set('error','Missing invoice id.'); redirect('/purchaseinvoices'); }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Ensure receipt log table exists (idempotent)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS purchase_receipts (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  purchase_invoice_id INT NOT NULL,
                  product_id INT NOT NULL,
                  warehouse_id INT NOT NULL,
                  qty INT NOT NULL,
                  unit_cost DECIMAL(12,4) NOT NULL,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  INDEX (purchase_invoice_id),
                  INDEX (product_id, warehouse_id),
                  CONSTRAINT fk_pr_pi FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices(id)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Read posted arrays
            $pids = $_POST['rec_product_id'] ?? [];
            $wids = $_POST['rec_warehouse_id'] ?? [];
            $qtys = $_POST['rec_qty'] ?? [];
            $prices = $_POST['rec_price'] ?? [];

            // Resolve the related PO
            $pi = \App\Models\PurchaseInvoice::find($piId);
            if (!$pi) { throw new \RuntimeException('Invoice not found.'); }
            $poId = (int)$pi['purchase_order_id'];
            if ($poId <= 0) { throw new \RuntimeException('Invoice is not linked to a PO.'); }

            // Prepared lookups
            $qOrdered = $pdo->prepare("
                SELECT qty FROM purchase_order_items
                WHERE purchase_order_id=? AND product_id=? AND warehouse_id=? LIMIT 1
            ");
            $qAlready = $pdo->prepare("
                SELECT COALESCE(SUM(qty),0)
                FROM purchase_receipts
                WHERE purchase_invoice_id IN (
                      SELECT id FROM purchase_invoices WHERE purchase_order_id = ?
                )
                AND product_id=? AND warehouse_id=?
            ");

            // Upsert stock
            $upsertStock = $pdo->prepare("
                INSERT INTO product_stocks (product_id, warehouse_id, qty_on_hand, qty_reserved, avg_cost)
                VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                  qty_on_hand = qty_on_hand + VALUES(qty_on_hand),
                  avg_cost = ROUND(
                      ((qty_on_hand * avg_cost) + (VALUES(qty_on_hand) * VALUES(avg_cost)))
                      / NULLIF(qty_on_hand + VALUES(qty_on_hand), 0), 4
                  )
            ");

            $insReceipt = $pdo->prepare("
                INSERT INTO purchase_receipts (purchase_invoice_id, product_id, warehouse_id, qty, unit_cost)
                VALUES (?,?,?,?,?)
            ");

            $lines = 0;
            foreach ($pids as $i => $pidRaw) {
                $pid = (int)$pidRaw;
                $wid = (int)($wids[$i] ?? 0);
                $qty = (int)($qtys[$i] ?? 0);
                $cost = (float)($prices[$i] ?? 0);
                if ($pid <= 0 || $wid <= 0 || $qty <= 0) { continue; }

                // Cap to remaining
                $qOrdered->execute([$poId, $pid, $wid]);
                $ordered = (int)$qOrdered->fetchColumn();
                if ($ordered <= 0) { continue; } // product/warehouse not on PO

                $qAlready->execute([$poId, $pid, $wid]);
                $already = (int)$qAlready->fetchColumn();
                $remaining = max(0, $ordered - $already);
                if ($remaining <= 0) { continue; }

                $take = min($qty, $remaining);
                if ($take <= 0) { continue; }

                // Stock upsert with moving-average (avg_cost column is per-unit cost)
                $upsertStock->execute([$pid, $wid, $take, 0, $cost]);

                // Audit row
                $insReceipt->execute([$piId, $pid, $wid, $take, $cost]);
                $lines++;
            }

            if ($lines === 0) {
                throw new \RuntimeException('Nothing to receive (check remaining quantities).');
            }

            $pdo->commit();
            flash_set('success', 'Receipt posted.');
            redirect('/purchaseinvoices/show?id=' . $piId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Receive failed: ' . $e->getMessage());
            redirect('/purchaseinvoices/show?id=' . $piId);
        }
    }
}
