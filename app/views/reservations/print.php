<?php use function App\Core\base_url; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reservations</title>
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
    <a href="<?= base_url('/reservations') ?>" style="margin-left:8px;">Back</a>
  </div>

  <h1>Reservations</h1>
  <div class="muted">Printed at <?= date('Y-m-d H:i:s') ?></div>

  <table>
    <thead>
      <tr>
        <th>Type</th>
        <th>Customer</th>
        <th>Doc No</th>
        <th>Date</th>
        <th>Expires</th>
        <th class="r">Qty Reserved</th>
        <th class="r">Value</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= htmlspecialchars(ucfirst($r['scope'] ?? ''),ENT_QUOTES,'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['doc_no'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['doc_date'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['expires_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td class="r"><?= (int)($r['qty_reserved'] ?? 0) ?></td>
          <td class="r"><?= number_format((float)($r['value_reserved'] ?? 0),2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows ?? [])): ?><tr><td colspan="7">No reservations.</td></tr><?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="r" style="padding:8px;">Totals:</td>
        <td class="r" style="padding:8px;"><strong><?= (int)($tot_qty ?? 0) ?></strong></td>
        <td class="r" style="padding:8px;"><strong><?= number_format((float)($tot_val ?? 0),2) ?></strong></td>
      </tr>
    </tfoot>
  </table>
</body>
</html>

