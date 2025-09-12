<?php
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('lowstock.low_stock') ?></h2>
  <form method="get" action="<?= base_url('/lowstock') ?>" style="display:flex;gap:8px;align-items:center;margin:8px 0;flex-wrap:wrap;">
    <label><?= $t('lowstock.threshold') ?>
      <input type="number" min="0" name="threshold" value="<?= (int)($threshold ?? 5) ?>" style="padding:6px;border:1px solid #ddd;border-radius:6px;width:100px;">
    </label>
    <label><?= $t('lowstock.warehouse') ?>
      <select name="warehouse_id" style="padding:6px;border:1px solid #ddd;border-radius:6px;">
        <option value=""><?= $t('common.all') ?></option>
        <?php foreach (($warehouses ?? []) as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= ((int)($warehouse_id ?? 0) === (int)$w['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($w['name'] ?? '',ENT_QUOTES,'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;"><?= $t('common.apply') ?></button>
  </form>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('lowstock.product') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('lowstock.warehouse') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('lowstock.on_hand') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('lowstock.reserved') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('lowstock.available') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars(($r['product_code'] ?? $r['code'] ?? '').' — '.($r['product_name'] ?? $r['name'] ?? ''),ENT_QUOTES,'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($r['warehouse_name'] ?? ($r['warehouse_id'] ?? ''),ENT_QUOTES,'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)($r['qty_on_hand'] ?? 0) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)($r['qty_reserved'] ?? 0) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)(($r['available_qty'] ?? (($r['qty_on_hand'] ?? 0) - ($r['qty_reserved'] ?? 0)))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows ?? [])): ?><tr><td colspan="5" style="padding:12px;">No low stock items.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

