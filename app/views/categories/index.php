<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
use function App\Core\flash_get;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('categories.categories') ?></h2>

  <?php if ($msg = flash_get('success')): ?>
    <div style="background:#e7f8ee;border:1px solid #b9e7c9;padding:10px;border-radius:8px;margin:10px 0;">
      <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($err = flash_get('error')): ?>
    <div style="background:#ffe9e9;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin:10px 0;">
      <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <p><a href="<?= base_url('/categories/create') ?>"><?= $t('categories.new_category') ?></a></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('common.name') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('categories.slug') ?></th>
        <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;"><?= $t('categories.parent') ?></th>
        <th style="border-bottom:1px solid #eee;padding:8px;"><?= $t('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $row): ?>
        <tr>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;"><?= htmlspecialchars($row['parent_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="border-bottom:1px solid #f2f2f4;padding:8px;white-space:nowrap;">
            <a href="<?= base_url('/categories/edit?id=' . (int)$row['id']) ?>">Edit</a>
            &nbsp;|&nbsp;
            <form method="post" action="<?= base_url('/categories/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this category?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" style="background:none;border:none;color:#c00;cursor:pointer;">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="4" style="padding:12px;">No categories yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
