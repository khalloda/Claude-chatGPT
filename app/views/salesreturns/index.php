<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('salesreturns.credit_notes') ?></h2>
  <p><a href="<?= base_url('/invoices') ?>"><?= $t('salesreturns.back_to_invoices') ?></a></p>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('salesreturns.credit_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('salesreturns.invoice_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('salesreturns.customer') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('salesreturns.date') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.total') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach (($items ?? []) as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><a href="<?= base_url('/salesreturns/show?id='.(int)$r['id']) ?>"><?= htmlspecialchars($r['sr_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['inv_no'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['total'] ?? 0),2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><a href="<?= base_url('/salesreturns/print?id='.(int)$r['id']) ?>"><?= $t('common.print') ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items ?? [])): ?><tr><td colspan="6" style="padding:12px;"><?= $t('salesreturns.no_credit_notes') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

