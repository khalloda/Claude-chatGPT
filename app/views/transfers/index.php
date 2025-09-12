<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('transfers.stock_transfers') ?></h2>
  <p><a class="no-print" href="<?= base_url('/transfers/create') ?>"><?= $t('transfers.new_transfer') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('transfers.tr_number') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('transfers.date') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('transfers.from') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('transfers.to') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach (($items ?? []) as $t): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($t['tr_no'],ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($t['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($t['from_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($t['to_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <a href="<?= base_url('/transfers/show?id='.(int)$t['id']) ?>"><?= $t('transfers.open') ?></a> ·
          <a href="<?= base_url('/transfers/print?id='.(int)$t['id']) ?>"><?= $t('common.print') ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?><tr><td colspan="5" style="padding:12px;"><?= $t('transfers.no_transfers') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
