<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('purchaseorders.purchase_orders') ?></h2>
  <p><a href="<?= base_url('/purchaseorders/create') ?>"><?= $t('purchaseorders.new_po') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('purchaseorders.po_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('purchaseorders.supplier') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.status') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.total') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $po): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($po['po_no'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($po['supplier_name'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($po['status'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$po['total'],2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/purchaseorders/show?id='.(int)$po['id']) ?>"><?= $t('common.view') ?></a>
            <?php if (($po['status'] ?? '') === 'draft'): ?>
              · <a href="<?= base_url('/purchaseorders/edit?id='.(int)$po['id']) ?>"><?= $t('common.edit') ?></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5" style="padding:12px;"><?= $t('purchaseorders.no_purchase_orders') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
