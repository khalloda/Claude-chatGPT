<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('nav.payments') ?></h2>

  <p><a href="<?= base_url('/invoices') ?>"><?= $t('payments.back_to_invoices') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.date') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.payment_number') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.invoice') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.customer') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.method') ?></th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.reference') ?></th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;"><?= $t('payments.amount') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['paid_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?php $pnum = 'PMT'.str_pad((string)((int)$r['id']), 6, '0', STR_PAD_LEFT); echo htmlspecialchars($pnum, ENT_QUOTES, 'UTF-8'); ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/invoices/show?id='.(int)$r['invoice_id']) ?>">
              <?= htmlspecialchars($r['inv_no'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['method'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$r['amount'], 2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/payments/create?invoice_id='.(int)$r['invoice_id'].'&_return='.urlencode('/payments')) ?>"><?= $t('payments.new_for_invoice') ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="8" style="padding:12px;"><?= $t('payments.no_payments') ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
