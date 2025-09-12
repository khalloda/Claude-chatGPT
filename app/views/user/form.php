<?php use function App\Core\base_url; /** @var array $item,$roles,$assigned; @var string $mode */ ?>
<section>
  <h2><?= $mode==='create' ? 'Create User' : 'Edit User' ?></h2>
  <p class="no-print" style="margin:8px 0;"><a href="<?= base_url('/users') ?>">Back</a></p>
  <form method="post" action="<?= base_url($mode==='create'?'/users':'/users/update') ?>" style="max-width:520px;">
    <?= App\Core\csrf_field() ?>
    <?php if ($mode==='edit'): ?><input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>"><?php endif; ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '',ENT_QUOTES) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Legacy Role (fallback)</label>
      <select class="form-select" name="role">
        <?php $cur = $item['role'] ?? 'staff'; foreach (['admin','manager','staff'] as $r): ?>
        <option value="<?= $r ?>" <?= $cur===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Used when RBAC tables are not present. 'admin' grants all.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Password <?= $mode==='edit' ? '(leave blank to keep)' : '' ?></label>
      <input class="form-control" type="password" name="password" <?= $mode==='create'?'required':'' ?> >
    </div>
    <?php if (!empty($roles)): ?>
    <div class="mb-3">
      <label class="form-label">Assign Roles</label>
      <div class="d-flex flex-column" style="gap:6px;">
        <?php $assigned = $assigned ?? []; foreach ($roles as $r): $rid=(int)$r['id']; ?>
          <label><input type="checkbox" name="roles[]" value="<?= $rid ?>" <?= in_array($rid,$assigned,true)?'checked':'' ?>> <?= htmlspecialchars($r['name'] ?? $r['slug'],ENT_QUOTES) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="mb-3">
      <button class="btn btn-primary" type="submit">Save</button>
    </div>
  </form>
</section>

