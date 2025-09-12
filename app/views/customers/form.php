<?php
use function App\Core\base_url;
use function App\Core\csrf_field;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $mode==='create' ? $t('customers.new_customer') : $t('customers.edit_customer') ?></h2>

  <form method="post" action="<?= $mode==='create'?base_url('/customers'):base_url('/customers/update') ?>" style="display:grid;gap:12px;max-width:720px;">
    <?= csrf_field() ?>
    <?php if ($mode==='edit'): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
    <label><div><?= $t('common.name') ?></div>
      <input type="text" name="name" required value="<?= $h($item['name'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
    </label>
    <label><div><?= $t('common.phone') ?></div>
      <input type="text" name="phone" value="<?= $h($item['phone'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
    </label>
    <label><div><?= $t('common.email') ?></div>
      <input type="email" name="email" value="<?= $h($item['email'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
    </label>
    <label><div><?= $t('common.address') ?></div>
      <textarea name="address" rows="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;"><?= $h($item['address'] ?? '') ?></textarea>
    </label>
    <div style="display:flex;gap:10px;">
      <button type="submit" style="padding:10px 14px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer;"><?= $mode==='create' ? $t('common.create') : $t('common.save_changes') ?></button>
      <a href="<?= base_url('/customers') ?>" style="align-self:center;"><?= $t('common.cancel') ?></a>
    </div>
  </form>
  <?php if ($mode === 'edit'): ?>
  <?php
    $entity_type = 'customer';
    $entity_id   = (int)$item['id'];
    $notes       = $notes ?? [];
    include __DIR__ . '/../partials/notes.php';
  ?>
<?php endif; ?>
</section>
