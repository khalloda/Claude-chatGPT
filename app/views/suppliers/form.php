<?php
use function App\Core\base_url;
use function App\Core\csrf_field;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** @var string $mode */
/** @var array $item */
?>
<section>
  <h2><?= $mode === 'create' ? $t('suppliers.new_supplier') : $t('suppliers.edit_supplier') ?></h2>

  <form method="post" action="<?= base_url($mode==='create' ? '/suppliers' : '/suppliers/update') ?>" style="display:grid;gap:10px;max-width:800px;">
    <?= csrf_field() ?>
    <?php if ($mode==='edit'): ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <label><div><?= $t('common.name') ?></div>
      <input type="text" name="name" value="<?= $h($item['name'] ?? '') ?>" required style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
    </label>

    <label><div><?= $t('common.phone') ?></div>
      <input type="text" name="phone" value="<?= $h($item['phone'] ?? '') ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
    </label>

    <label><div><?= $t('common.email') ?></div>
      <input type="email" name="email" value="<?= $h($item['email'] ?? '') ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
    </label>

    <label><div><?= $t('common.address') ?></div>
      <input type="text" name="address" value="<?= $h($item['address'] ?? '') ?>" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
    </label>

    <div>
      <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer;">
        <?= $mode==='create' ? $t('common.create') : $t('common.save') ?>
      </button>
      <a href="<?= base_url('/suppliers') ?>" style="margin-left:8px;"><?= $t('common.back') ?></a>
    </div>
  </form>
</section>
