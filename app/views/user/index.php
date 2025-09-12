<?php use function App\Core\base_url; /** @var array $rows */ ?>
<section>
  <h2>Users & Roles</h2>
  <p class="no-print" style="margin:8px 0; display:flex; gap:12px; align-items:center;">
    <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/') ?>">Back</a>
    <a class="btn btn-sm btn-primary" href="<?= base_url('/users/create') ?>">New User</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('/roles') ?>">Roles</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('/permissions') ?>">Permissions</a>
  </p>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="border-bottom:1px solid #eee;padding:8px;">ID</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">Email</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">Role</th>
        <th style="border-bottom:1px solid #eee;padding:8px;">Created</th>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
          <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/users/edit?id='.(int)($r['id']??0)) ?>">Edit</a>
          <form method="post" action="<?= base_url('/users/delete') ?>" style="display:inline-block;" onsubmit="return confirm('Delete this user?');">
            <?= App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= (int)($r['id'] ?? 0) ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="4" style="padding:12px;">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
