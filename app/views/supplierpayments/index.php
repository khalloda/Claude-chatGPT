<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('supplierpayments.supplier_payments') ?></h2>
  <p><a href="<?= base_url('/purchaseinvoices') ?>"><?= $t('supplierpayments.back_to_purchase_invoices') ?></a></p>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('supplierpayments.date') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('supplierpayments.supplier') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('supplierpayments.pi_number') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('supplierpayments.method') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('supplierpayments.reference') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;"><?= $t('supplierpayments.amount') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['paid_at'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['supplier_name'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/purchaseinvoices/show?id='.(int)$r['purchase_invoice_id']) ?>"><?= htmlspecialchars($r['pi_no'],ENT_QUOTES,'UTF-8') ?></a>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['method'],ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['reference'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)$r['amount'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="6" style="padding:12px;"><?= $t('supplierpayments.no_supplier_payments') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
