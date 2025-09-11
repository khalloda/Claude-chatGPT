<?php use function App\Core\base_url; ?>
<section>
  <h2>Credit Notes (Sales Returns)</h2>
  <p><a href="<?= base_url('/invoices') ?>">Back to Invoices</a></p>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Credit #</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Invoice #</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Customer</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Date</th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Total</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach (($items ?? []) as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><a href="<?= base_url('/salesreturns/show?id='.(int)$r['id']) ?>"><?= htmlspecialchars($r['sr_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['inv_no'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['total'] ?? 0),2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><a href="<?= base_url('/salesreturns/print?id='.(int)$r['id']) ?>">Print</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items ?? [])): ?><tr><td colspan="6" style="padding:12px;">No credit notes yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

