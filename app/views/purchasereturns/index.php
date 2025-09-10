<?php use function App\Core\base_url; ?>
<section>
  <h2>Purchase Returns (Debit Notes)</h2>
  <p class="text-muted">Most recent purchase returns across all PIs.</p>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="border-bottom:1px solid #eee;padding:8px;">Date</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">PR #</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">PI #</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">Supplier</th>
        <th style="border-bottom:1px solid #eee;padding:8px;text-align:right;">Total</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($r['pr_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/purchaseinvoices/show?id='.(int)($r['purchase_invoice_id'] ?? 0)) ?>">
              <?= htmlspecialchars($r['pi_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($r['supplier_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;">
            <?= number_format((float)($r['total'] ?? 0), 2) ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <a href="<?= base_url('/purchasereturns/print?id='.(int)($r['id'] ?? 0)) ?>" target="_blank">Print</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="padding:12px;">No purchase returns found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

