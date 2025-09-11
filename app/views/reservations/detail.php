<?php
use function App\Core\base_url;
/** @var array $doc, $lines, $scope */
?>
<section>
  <h2>Reservation Details — <?= htmlspecialchars(ucfirst($scope),ENT_QUOTES,'UTF-8') ?></h2>
  <?php if ($scope==='quote'): ?>
    <div>Quote: <a href="<?= base_url('/quotes/show?id='.(int)$doc['id']) ?>"><?= htmlspecialchars($doc['quote_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a></div>
    <div>Customer: <?= htmlspecialchars($doc['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Date: <?= htmlspecialchars($doc['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?> | Expires: <?= htmlspecialchars($doc['expires_at'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
  <?php else: ?>
    <div>Order: <a href="<?= base_url('/orders/show?id='.(int)$doc['id']) ?>"><?= htmlspecialchars($doc['so_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a></div>
    <div>Customer: <?= htmlspecialchars($doc['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Date: <?= htmlspecialchars($doc['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
  <?php endif; ?>

  <table style="width:100%;border-collapse:collapse;margin-top:10px;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Product</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Warehouse</th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Qty</th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Unit Price</th>
      <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Line Total</th>
    </tr></thead>
    <tbody>
      <?php foreach ($lines as $it): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars(($it['product_code'] ?? '').' — '.($it['product_name'] ?? ''),ENT_QUOTES,'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?= htmlspecialchars($it['warehouse_name'] ?? '',ENT_QUOTES,'UTF-8') ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)($it['qty'] ?? 0) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($it['price'] ?? 0),2) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($it['line_total'] ?? ((int)($it['qty'] ?? 0) * (float)($it['price'] ?? 0))),2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($lines)): ?><tr><td colspan="5" style="padding:10px;">No lines.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <p style="margin-top:10px;"><a href="<?= base_url('/reservations') ?>">Back to Reservations</a></p>
</section>

