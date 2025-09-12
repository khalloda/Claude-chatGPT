<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('adjustments.stock_adjustments') ?></h2>
  <p><a class="no-print" href="<?= base_url('/adjustments/create') ?>"><?= $t('adjustments.new_adjustment') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('adjustments.ad_number') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('adjustments.date') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('adjustments.warehouse') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('adjustments.reason') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach (($items ?? []) as $a): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($a['adj_no'],ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($a['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($a['warehouse_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($a['reason'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <a href="<?= base_url('/adjustments/show?id='.(int)$a['id']) ?>"><?= $t('adjustments.open') ?></a> ·
          <a href="<?= base_url('/adjustments/print?id='.(int)$a['id']) ?>"><?= $t('common.print') ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?><tr><td colspan="5" style="padding:12px;"><?= $t('adjustments.no_adjustments') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
