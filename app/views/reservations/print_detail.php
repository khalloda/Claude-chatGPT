<?php use function App\Core\base_url; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reservation Details</title>
  <style>
    body{font-family:Arial, sans-serif; margin:24px;}
    h1{margin:0 0 8px 0;}
    .muted{color:#666;}
    table{width:100%;border-collapse:collapse;margin-top:12px;}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left;}
    td.r, th.r {text-align:right;}
    .toolbar{margin-bottom:12px; padding:10px; border:1px solid #eee; border-radius:8px;}
    @media print{ .no-print{ display:none !important; } .toolbar{ display:none !important; } }
  </style>
  <script>function doPrint(){window.print()}</script>
</head>
<body>
  <div class="toolbar no-print">
    <button type="button" onclick="doPrint()" style="padding:6px 10px;border:1px solid #111;border-radius:8px;background:#111;color:#fff;cursor:pointer;">Print</button>
    <a href="<?= base_url('/reservations/detail?scope='.(isset($scope)?urlencode($scope):'').'&id='.(int)($doc['id'] ?? 0)) ?>" style="margin-left:8px;">Back</a>
  </div>

  <h1>Reservation Details — <?= htmlspecialchars(ucfirst($scope ?? ''),ENT_QUOTES,'UTF-8') ?></h1>
  <div class="muted">Printed at <?= date('Y-m-d H:i:s') ?></div>

  <?php if (($scope ?? '')==='quote'): ?>
    <div>Quote: <?= htmlspecialchars($doc['quote_no'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Customer: <?= htmlspecialchars($doc['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Date: <?= htmlspecialchars($doc['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?> | Expires: <?= htmlspecialchars($doc['expires_at'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
  <?php else: ?>
    <div>Order: <?= htmlspecialchars($doc['so_no'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Customer: <?= htmlspecialchars($doc['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
    <div>Date: <?= htmlspecialchars($doc['created_at'] ?? '',ENT_QUOTES,'UTF-8') ?></div>
  <?php endif; ?>

  <table>
    <thead><tr>
      <th>Product</th>
      <th>Warehouse</th>
      <th class="r">Qty</th>
      <th class="r">Unit Price</th>
      <th class="r">Line Total</th>
    </tr></thead>
    <tbody>
      <?php foreach (($lines ?? []) as $it): ?>
        <tr>
          <td><?= htmlspecialchars(($it['product_code'] ?? '').' — '.($it['product_name'] ?? ''),ENT_QUOTES,'UTF-8') ?></td>
          <td><?= htmlspecialchars($it['warehouse_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td class="r"><?= (int)($it['qty'] ?? 0) ?></td>
          <td class="r"><?= number_format((float)($it['price'] ?? 0),2) ?></td>
          <td class="r"><?= number_format((float)($it['line_total'] ?? ((int)($it['qty'] ?? 0) * (float)($it['price'] ?? 0))),2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($lines ?? [])): ?><tr><td colspan="5">No lines.</td></tr><?php endif; ?>
    </tbody>
  </table>
</body>
</html>

