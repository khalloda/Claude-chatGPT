<?php
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** @var array $rows,$totals; @var string $from,$to */
?>
<section>
  <h2><?= $t('reports.sales_report') ?></h2>

  <form class="no-print" method="get" action="<?= base_url('/reports/sales') ?>" style="display:flex;gap:8px;align-items:end;margin:8px 0;">
    <label><div><?= $t('reports.from') ?></div><input type="date" name="from" value="<?= $h($from) ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;"></label>
    <label><div><?= $t('reports.to') ?></div><input type="date" name="to" value="<?= $h($to) ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;"></label>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer;"><?= $t('reports.apply') ?></button>
    <button type="button" onclick="window.print()" style="padding:8px 12px;border:1px solid #111;border-radius:8px;background:#fff;color:#111;cursor:pointer;"><?= $t('reports.print') ?></button>
    <a href="<?= base_url('/') ?>" style="margin-left:8px;"><?= $t('common.back') ?></a>
  </form>

  <div style="display:flex;gap:24px;margin:8px 0;">
    <div><?= $t('reports.total_invoices') ?>: <strong><?= number_format((float)$totals['invoices'],2) ?></strong></div>
    <div><?= $t('reports.total_returns') ?>: <strong><?= number_format((float)$totals['returns'],2) ?></strong></div>
    <div><?= $t('reports.net_sales') ?>: <strong><?= number_format((float)$totals['net_sales'],2) ?></strong></div>
    <div><?= $t('reports.payments_received') ?>: <strong><?= number_format((float)$totals['payments'],2) ?></strong></div>
  </div>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('reports.date') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('reports.type') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('reports.reference') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('reports.customer') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;"><?= $t('reports.debit') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;"><?= $t('reports.credit') ?></th>
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
            $inv  = (int)($r['invoice_id'] ?? 0);
            if ($kind === 'invoice' && $rid) {
              echo '<a href="'.base_url('/invoices/show?id='.$rid).'">'.$ref.'</a>';
            } elseif ($kind === 'payment' && $inv) {
              echo '<a href="'.base_url('/invoices/show?id='.$inv).'">'.$ref.'</a>';
            } elseif ($kind === 'return' && $rid) {
              echo '<a href="'.base_url('/salesreturns/show?id='.$rid).'">'.$ref.'</a>';
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

