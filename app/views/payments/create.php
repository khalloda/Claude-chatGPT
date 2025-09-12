<?php
use function App\Core\base_url;
use function App\Core\csrf_field;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('payments.new_payment') ?></h2>

  <p><?= $t('payments.invoice') ?>:
    <a href="<?= base_url('/invoices/show?id='.(int)$i['id']) ?>">
      <?= $h($i['inv_no']) ?>
    </a>
    &nbsp;•&nbsp; <?= $t('payments.customer') ?>: <?= $h($i['customer_name'] ?? ('#'.(int)$i['customer_id'])) ?>
    &nbsp;•&nbsp; <?= $t('common.total') ?>: <strong><?= number_format((float)$i['total'], 2) ?></strong>
    &nbsp;•&nbsp; <?= $t('payments.paid') ?>: <strong><?= number_format((float)$i['paid_amount'], 2) ?></strong>
  </p>

  <form method="post" action="<?= base_url('/payments') ?>"
        style="margin-top:10px;display:grid;grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap:8px; align-items:end; max-width:1000px;">
    <?= csrf_field() ?>
    <input type="hidden" name="invoice_id" value="<?= (int)$i['id'] ?>">
    <input type="hidden" name="_return" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">

    <label>
      <div><?= $t('payments.date') ?></div>
      <input type="datetime-local" name="paid_at" value="<?= $h($now) ?>" required
             style="padding:8px;border:1px solid #ddd;border-radius:6px;">
    </label>

    <label>
      <div><?= $t('payments.method') ?></div>
      <input type="text" name="method" value="cash" required
             style="padding:8px;border:1px solid #ddd;border-radius:6px;">
    </label>

    <label>
      <div><?= $t('payments.reference') ?></div>
      <input type="text" name="reference"
             style="padding:8px;border:1px solid #ddd;border-radius:6px;">
    </label>

    <label>
      <div><?= $t('payments.amount') ?></div>
      <input type="number" step="0.01" min="0.01" name="amount" required
             style="padding:8px;border:1px solid #ddd;border-radius:6px;">
    </label>

    <label style="grid-column: 1 / -2;">
      <div><?= $t('payments.note') ?></div>
      <input type="text" name="note"
             style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;">
    </label>

    <div>
      <button type="submit"
              style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer;">
        <?= $t('payments.save_payment') ?>
      </button>
    </div>
  </form>

  <p style="margin-top:12px;">
    <a href="<?= base_url('/invoices/show?id='.(int)$i['id']) ?>"><?= $t('payments.back_to_invoice') ?></a>
    · <a href="<?= base_url('/payments') ?>"><?= $t('payments.payments_list') ?></a>
  </p>
</section>
