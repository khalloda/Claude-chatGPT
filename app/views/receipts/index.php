<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('receipts.goods_receipts') ?></h2>
  <p class="text-muted"><?= $t('receipts.most_recent_receipts') ?></p>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('receipts.date') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('receipts.pi_number') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('receipts.product') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('receipts.warehouse') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;"><?= $t('receipts.quantity') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;"><?= $t('receipts.unit_cost') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/purchaseinvoices/show?id='.(int)($r['purchase_invoice_id'] ?? 0)) ?>">
              <?= htmlspecialchars($r['pi_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars(($r['product_code'] ?? '').' — '.($r['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['warehouse_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)($r['qty'] ?? 0) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['price'] ?? 0), 2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/receipts/print?invoice_id='.(int)($r['purchase_invoice_id'] ?? 0)) ?>" target="_blank">Print GRN</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="padding:12px;">No receipts found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

