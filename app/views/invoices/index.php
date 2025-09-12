<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('nav.invoices') ?></h2>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('invoices.invoice_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('invoices.customer') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.total') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('invoices.paid') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.status') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $i): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($i['inv_no'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($i['customer_name'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$i['total'],2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$i['paid_amount'],2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($i['status'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/invoices/show?id='.(int)$i['id']) ?>"><?= $t('common.view') ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="6" style="padding:12px;"><?= $t('invoices.no_invoices') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
