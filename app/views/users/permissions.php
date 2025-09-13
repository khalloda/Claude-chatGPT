<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** @var array $perms */ 
?>
<section>
  <h2><?= $t('users.permissions') ?></h2>
  <p class="no-print" style="margin:8px 0; display:flex; gap:12px; align-items:center;">
    <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/users') ?>"><?= $t('common.back') ?></a>
  </p>
  <form method="post" action="<?= base_url('/permissions') ?>" class="mb-3" style="max-width:520px;">
    <?= App\Core\csrf_field() ?>
    <div class="mb-2"><label class="form-label"><?= $t('common.name') ?></label><input class="form-control" type="text" name="name" required></div>
    <div class="mb-2"><label class="form-label"><?= $t('users.slug') ?></label><input class="form-control" type="text" name="slug" required placeholder="e.g., reports.view"></div>
    <button class="btn btn-primary" type="submit"><?= $t('users.add_permission') ?></button>
  </form>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.id') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('users.slug') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.name') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($perms as $p): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= (int)$p['id'] ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($p['slug'],ENT_QUOTES) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($p['name'],ENT_QUOTES) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <form method="post" action="<?= base_url('/permissions/delete') ?>" style="display:inline-block;" onsubmit="return confirm('Delete this permission?');">
            <?= App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit"><?= $t('common.delete') ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$perms): ?><tr><td colspan="4" style="padding:12px;"><?= $t('users.no_permissions_found') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

