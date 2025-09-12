<?php 
use function App\Core\csrf_field;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('auth.login') ?></h2>
  <p><?= $t('auth.enter_credentials') ?></p>
  <?php if (!empty($error)): ?>
    <div style="background:#ffe9e9;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin:10px 0;">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <form method="post" action="/login" style="display:grid;gap:12px;max-width:380px;">
    <?= csrf_field() ?>
    <label>
      <div><?= $t('common.email') ?></div>
      <input type="email" name="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
    </label>
    <label>
      <div><?= $t('common.password') ?></div>
      <input type="password" name="password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
    </label>
    <button type="submit" style="padding:10px 14px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer;"><?= $t('auth.sign_in') ?></button>
  </form>
</section>
