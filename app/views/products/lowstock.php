<?php
use function App\Core\base_url;
?>
<section>
  <h2>Low Stock</h2>
  <form method="get" action="<?= base_url('/lowstock') ?>" style="display:flex;gap:8px;align-items:center;margin:8px 0;flex-wrap:wrap;">
    <label>Threshold
      <input type="number" min="0" name="threshold" value="<?= (int)($threshold ?? 5) ?>" style="padding:6px;border:1px solid #ddd;border-radius:6px;width:100px;">
    </label>
    <label>Warehouse
      <select name="warehouse_id" style="padding:6px;border:1px solid #ddd;border-radius:6px;">
        <option value="">All</option>
        <?php foreach (($warehouses ?? []) as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= ((int)($warehouse_id ?? 0) === (int)$w['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($w['name'] ?? '',ENT_QUOTES,'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;">Apply</button>
  </form>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Product</th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Warehouse</th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">On Hand</th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Reserved</th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Available</th>
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

