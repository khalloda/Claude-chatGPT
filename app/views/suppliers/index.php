<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('nav.suppliers') ?></h2>
  <p><a href="<?= base_url('/purchaseorders') ?>"><?= $t('suppliers.back_to_purchase_orders') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.name') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.phone') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.email') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.address') ?></th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('suppliers.balance') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $s): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($s['name'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($s['phone'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($s['email'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($s['address'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;">
            <?= number_format((float)($s['balance'] ?? 0), 2) ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
			<a href="<?= base_url('/suppliers/show?id='.(int)$s['id']) ?>"><?= $t('common.view') ?></a> ·
  			<a href="<?= base_url('/suppliers/statement?id='.(int)$s['id'].'&from='.date('Y-m-01').'&to='.date('Y-m-d')) ?>"><?= $t('suppliers.statement') ?></a>
            </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
        <tr><td colspan="6" style="padding:12px;"><?= $t('suppliers.no_suppliers') ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
