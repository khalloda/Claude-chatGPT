<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
use function App\Core\flash_get;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v)=>htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <div class="page-header">
    <div class="title"><?= $t('nav.warehouses') ?></div>
    <a class="ms-auto btn btn-sm btn-primary" href="<?= base_url('/warehouses/create') ?>"><?= $t('warehouses.new_warehouse') ?></a>
  </div>

  <?php if ($m = flash_get('success')): ?>
    <div class="alert alert-success"><?= $h($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash_get('error')): ?>
    <div class="alert alert-danger"><?= $h($m) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th><?= $t('warehouses.code') ?></th>
              <th><?= $t('common.name') ?></th>
              <th><?= $t('warehouses.location') ?></th>
              <th class="text-end"><?= $t('warehouses.on_hand') ?></th>
              <th class="text-end"><?= $t('warehouses.reserved') ?></th>
              <th class="text-end"><?= $t('warehouses.value') ?></th>
              <th class="text-end"><?= $t('common.actions') ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $w): ?>
            <tr>
              <td><?= $h($w['code']) ?></td>
              <td><?= $h($w['name']) ?></td>
              <td><?= $h($w['location'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)$w['on_hand'], 2) ?></td>
              <td class="text-end"><?= number_format((float)$w['reserved'], 2) ?></td>
              <td class="text-end"><?= number_format((float)$w['value'], 2) ?></td>
              <td style="border-bottom:1px solid #f2f2f4;padding:8px;white-space:nowrap;">
  <a href="<?= base_url('/warehouses/detail?id='.(int)$w['id']) ?>">View</a> &nbsp;|&nbsp;
  <a href="<?= base_url('/warehouses/edit?id='.(int)$w['id']) ?>">Edit</a> &nbsp;|&nbsp;
  <form method="post" action="<?= base_url('/warehouses/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this warehouse?');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
    <button type="submit" style="background:none;border:none;color:#c00;cursor:pointer;">Delete</button>
  </form>
</td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$items): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No warehouses yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
