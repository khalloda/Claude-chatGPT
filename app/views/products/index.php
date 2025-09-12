<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
use function App\Core\flash_get;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('nav.products') ?></h2>

  <?php if ($m = flash_get('success')): ?>
    <div style="background:#e7f8ee;border:1px solid #b9e7c9;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($m = flash_get('error')): ?>
    <div style="background:#ffe9e9;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="get" action="<?= base_url('/products') ?>" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <input type="text" name="q" placeholder="<?= $t('products.search_placeholder') ?>" value="<?= $h((string)$q) ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    <select name="category_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value=""><?= $t('products.all_categories') ?></option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?php if ((int)$category_id === (int)$c['id']) echo 'selected'; ?>>
          <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="make_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value=""><?= $t('products.all_makes') ?></option>
      <?php foreach ($makes as $mk): ?>
        <option value="<?= (int)$mk['id'] ?>" <?php if ((int)$make_id === (int)$mk['id']) echo 'selected'; ?>>
          <?= htmlspecialchars($mk['name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="model_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value=""><?= $t('products.all_models') ?></option>
      <?php foreach ($models as $md): ?>
        <option value="<?= (int)$md['id'] ?>" <?php if ((int)$model_id === (int)$md['id']) echo 'selected'; ?>>
          <?= htmlspecialchars($md['name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;"><?= $t('common.filter') ?></button>
    <a href="<?= base_url('/products/create') ?>" style="align-self:center;margin-left:auto;"><?= $t('products.new_product') ?></a>
  </form>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.code') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.name') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.category') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.make_model') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.cost') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.price') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('products.availability') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $p): $avail = (int)$p['on_hand'] - (int)$p['reserved']; $buckets = \App\Models\Product::reservedBucketsForProduct((int)$p['id']); ?>
        <tr>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($p['code'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($p['category_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars(trim(($p['make_name'] ?? '').' / '.($p['model_name'] ?? ''), ' /'), ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;text-align:right;"><?= number_format((float)$p['cost'],2) ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;text-align:right;"><?= number_format((float)$p['price'],2) ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;text-align:right;">
            <?= (int)$avail ?> / <?= (int)$p['reserved'] ?>
            <span style="margin-left:6px;" title="Reserved for Quotes">
              <span style="display:inline-block;background:#eef2ff;color:#3730a3;border-radius:10px;padding:2px 6px;font-size:12px;">Q <?= (int)($buckets['rq'] ?? 0) ?></span>
            </span>
            <span style="margin-left:4px;" title="Reserved for Orders">
              <span style="display:inline-block;background:#ecfeff;color:#155e75;border-radius:10px;padding:2px 6px;font-size:12px;">O <?= (int)($buckets['ro'] ?? 0) ?></span>
            </span>
          </td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;white-space:nowrap;">
            <a href="<?= base_url('/products/stock?id='.(int)$p['id']) ?>"><?= $t('products.stock') ?></a> &nbsp;|&nbsp;
            <a href="<?= base_url('/products/edit?id='.(int)$p['id']) ?>"><?= $t('common.edit') ?></a> &nbsp;|&nbsp;
            <form method="post" action="<?= base_url('/products/delete') ?>" style="display:inline" onsubmit="return confirm('<?= $t('products.delete_confirm') ?>');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" style="background:none;border:none;color:#c00;cursor:pointer;"><?= $t('common.delete') ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="8" style="padding:12px;"><?= $t('products.no_products') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
