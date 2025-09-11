<?php
use function App\Core\base_url;
/** @var array $rows, $tot_qty, $tot_val */
?>
<section>
  <h2>Reservations</h2>
  <p class="text-muted">Shows documents currently holding stock reservations. Quotes reserve when status is Sent. Orders reserve after converting from quotes. Delivery confirmation on invoice releases order reservations.</p>

  <form method="get" action="<?= base_url('/reservations') ?>" style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;">
    <select name="customer_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value="">All customers</option>
      <?php foreach (($filters['customers'] ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($filters['customer_id'] ?? 0) === (int)$c['id'])?'selected':'' ?>>
          <?= htmlspecialchars($c['name'] ?? '',ENT_QUOTES,'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="scope" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value="">All types</option>
      <option value="quote" <?= (($filters['scope'] ?? '')==='quote')?'selected':'' ?>>Quotes</option>
      <option value="order" <?= (($filters['scope'] ?? '')==='order')?'selected':'' ?>>Orders</option>
    </select>
    <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    <input type="date" name="to"   value="<?= htmlspecialchars($filters['to'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;">Apply</button>
  </form>

  <table style="width:100%;border-collapse:collapse;margin-top:10px;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Type</th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Customer</th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Doc No</th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Date</th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Expires</th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Qty Reserved</th>
        <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Value</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars(ucfirst($r['scope']),ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?php if (($r['scope'] ?? '') === 'quote'): ?>
              <a href="<?= base_url('/quotes/show?id='.(int)$r['id']) ?>"><?= htmlspecialchars($r['doc_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a>
              <a href="<?= base_url('/reservations/detail?scope=quote&id='.(int)$r['id']) ?>" style="margin-left:8px;">details</a>
            <?php else: ?>
              <a href="<?= base_url('/orders/show?id='.(int)$r['id']) ?>"><?= htmlspecialchars($r['doc_no'] ?? '',ENT_QUOTES,'UTF-8') ?></a>
              <a href="<?= base_url('/reservations/detail?scope=order&id='.(int)$r['id']) ?>" style="margin-left:8px;">details</a>
            <?php endif; ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['doc_date'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['expires_at'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= (int)($r['qty_reserved'] ?? 0) ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;text-align:right;"><?= number_format((float)($r['value_reserved'] ?? 0),2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="padding:10px;">No reservations.</td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="padding:8px;text-align:right;border-top:1px solid #eee;">Totals:</td>
        <td style="padding:8px;text-align:right;border-top:1px solid #eee;"><strong><?= (int)$tot_qty ?></strong></td>
        <td style="padding:8px;text-align:right;border-top:1px solid #eee;"><strong><?= number_format((float)$tot_val,2) ?></strong></td>
      </tr>
    </tfoot>
  </table>
</section>
