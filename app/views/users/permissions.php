<?php use function App\Core\base_url; /** @var array $perms */ ?>
<section>
  <h2>Permissions</h2>
  <p class="no-print" style="margin:8px 0; display:flex; gap:12px; align-items:center;">
    <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/users') ?>">Back</a>
  </p>
  <form method="post" action="<?= base_url('/permissions') ?>" class="mb-3" style="max-width:520px;">
    <?= App\Core\csrf_field() ?>
    <div class="mb-2"><label class="form-label">Name</label><input class="form-control" type="text" name="name" required></div>
    <div class="mb-2"><label class="form-label">Slug</label><input class="form-control" type="text" name="slug" required placeholder="e.g., reports.view"></div>
    <button class="btn btn-primary" type="submit">Add Permission</button>
  </form>
  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="border-bottom:1px solid #eee;padding:8px;">ID</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Slug</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Name</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Actions</th>
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
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$perms): ?><tr><td colspan="4" style="padding:12px;">No permissions found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

