<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** @var array $roles */ 
?>
<section>
  <h2><?= $t('users.roles') ?></h2>
  <p class="no-print" style="margin:8px 0; display:flex; gap:12px; align-items:center;">
    <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/users') ?>"><?= $t('common.back') ?></a>
    <a class="btn btn-sm btn-primary" href="<?= base_url('/roles/create') ?>"><?= $t('users.new_role') ?></a>
  </p>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.id') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.name') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('users.slug') ?></th>
      <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($roles as $r): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= (int)$r['id'] ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['name'],ENT_QUOTES) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['slug'],ENT_QUOTES) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/roles/edit?id='.(int)$r['id']) ?>"><?= $t('common.edit') ?></a>
          <form method="post" action="<?= base_url('/roles/delete') ?>" style="display:inline-block;" onsubmit="return confirm('<?= $t('users.delete_role_confirm') ?>');">
            <?= App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit"><?= $t('common.delete') ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$roles): ?><tr><td colspan="4" style="padding:12px;"><?= $t('users.no_roles') ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

