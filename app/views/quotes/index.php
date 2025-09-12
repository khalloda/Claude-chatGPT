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
  <h2><?= $t('nav.quotes') ?></h2>

  <?php if ($m = flash_get('success')): ?><div style="background:#e7f8ee;border:1px solid #b9e7c9;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($m = flash_get('error')): ?><div style="background:#ffe9e9;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

  <p><a href="<?= base_url('/quotes/create') ?>"><?= $t('quotes.new_quote') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('quotes.quote_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('quotes.customer') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.total') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.status') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $q): ?>
        <tr>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($q['quote_no'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($q['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;text-align:right;"><?= number_format((float)$q['total'],2) ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($q['status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;">
            <a href="<?= base_url('/quotes/show?id='.(int)$q['id']) ?>"><?= $t('common.view') ?> - </a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5" style="padding:12px;"><?= $t('quotes.no_quotes') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
