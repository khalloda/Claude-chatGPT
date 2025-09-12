<?php
use function App\Core\base_url;
use function App\Core\csrf_field;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('orders.sales_order') ?> <?= $h($o['so_no']) ?></h2>
  <p><?= $t('common.status') ?>: <strong><?= $h($o['status']) ?></strong></p>
  <p><?= $t('common.total') ?>: <strong><?= number_format((float)$o['total'], 2) ?></strong></p>

  <!-- Actions toolbar -->
  <div style="margin:6px 0 12px 0; display:flex; gap:8px; flex-wrap:wrap;">
    <form method="post" action="<?= base_url('/invoices/create-from-order') ?>" style="display:inline-block;">
      <?= csrf_field() ?>
      <input type="hidden" name="sales_order_id" value="<?= (int)$o['id'] ?>">
      <button type="submit"
              style="border:1px solid #111;background:#111;color:#fff;border-radius:8px;padding:6px 10px;cursor:pointer;">
        <?= $t('orders.create_invoice') ?>
      </button>
    </form>

    <a class="no-print"
       href="<?= base_url('/orders/print?id='.(int)$o['id']) ?>"
       style="border:1px solid #ddd;border-radius:8px;padding:6px 10px;background:#f9f9fb;text-decoration:none;display:inline-block;">
      <?= $t('orders.print') ?>
    </a>

    <a href="<?= base_url('/orders') ?>"
       style="border:1px solid #ddd;border-radius:8px;padding:6px 10px;background:#fff;text-decoration:none;display:inline-block;">
      <?= $t('orders.back_to_orders') ?>
    </a>
  </div>

  <table style="width:100%;border-collapse:collapse;margin-top:10px;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.product') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.warehouse') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.quantity') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.unit_price') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('orders.line_total') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($it['product_code'].' — '.$it['product_name'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($it['warehouse_name'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;">
            <?= (int)$it['qty'] ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;">
            <?= number_format((float)$it['price'], 2) ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;">
            <?= number_format((float)$it['line_total'], 2) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php
    // Notes (sales_order)
    $entity_type = 'sales_order';
    $entity_id   = (int)$o['id'];
    $notes       = $notes ?? [];
    include __DIR__ . '/../partials/notes.php';
  ?>
</section>
