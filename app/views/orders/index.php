<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('nav.orders') ?></h2>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.order_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.customer') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.total') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.status') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $o): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($o['so_no'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($o['customer_name'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$o['total'],2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($o['status'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><a href="<?= base_url('/orders/show?id='.(int)$o['id']) ?>"><?= $t('common.view') ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5" style="padding:12px;"><?= $t('orders.no_orders') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
