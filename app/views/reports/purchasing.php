<?php
use function App\Core\base_url;
/** @var array $rows,$totals; @var string $from,$to */
?>
<section>
  <h2>Purchasing Report</h2>

  <form class="no-print" method="get" action="<?= base_url('/reports/purchasing') ?>" style="display:flex;gap:8px;align-items:end;margin:8px 0;">
    <label><div>From</div><input type="date" name="from" value="<?= htmlspecialchars($from,ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;"></label>
    <label><div>To</div><input type="date" name="to" value="<?= htmlspecialchars($to,ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;"></label>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer;">Apply</button>
    <button type="button" onclick="window.print()" style="padding:8px 12px;border:1px solid #111;border-radius:8px;background:#fff;color:#111;cursor:pointer;">Print</button>
    <a href="<?= base_url('/') ?>" style="margin-left:8px;">Back</a>
  </form>

  <div style="display:flex;gap:24px;margin:8px 0;">
    <div>Total Purchases: <strong><?= number_format((float)$totals['invoices'],2) ?></strong></div>
    <div>Total Purchase Returns: <strong><?= number_format((float)$totals['returns'],2) ?></strong></div>
    <div>Net Purchases: <strong><?= number_format((float)$totals['net_purchases'],2) ?></strong></div>
    <div>Supplier Payments: <strong><?= number_format((float)$totals['payments'],2) ?></strong></div>
  </div>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;">Date</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Type</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Ref</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Supplier</th>
      <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;">Debit</th>
      <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;">Credit</th>
    </tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['txn_date'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars(ucfirst($r['kind'] ?? ''),ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <?php
            $kind = $r['kind'] ?? '';
            $ref  = htmlspecialchars($r['ref_no'] ?? '',ENT_QUOTES,'UTF-8');
            $rid  = (int)($r['ref_id'] ?? 0);
            $pi   = (int)($r['invoice_id'] ?? 0);
            if ($kind === 'invoice' && $rid) {
              echo '<a href="'.base_url('/purchaseinvoices/show?id='.$rid).'">'.$ref.'</a>';
            } elseif ($kind === 'payment' && $pi) {
              echo '<a href="'.base_url('/purchaseinvoices/show?id='.$pi).'">'.$ref.'</a>';
            } elseif ($kind === 'return' && $rid) {
              echo '<a href="'.base_url('/purchasereturns/print?id='.$rid).'">'.$ref.'</a>';
            } else { echo $ref; }
          ?>
        </td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['party'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['debit'] ?? 0),2) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['credit'] ?? 0),2) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="6" style="padding:12px;">No transactions in this period.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

