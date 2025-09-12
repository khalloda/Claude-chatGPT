<?php use function App\Core\base_url; /** @var array $role,$perms,$assigned */ ?>
<section>
  <h2><?= ($role['id']??0) ? 'Edit Role' : 'Create Role' ?></h2>
  <p class="no-print" style="margin:8px 0;"><a href="<?= base_url('/roles') ?>">Back</a></p>
  <form method="post" action="<?= base_url(($role['id']??0)?'/roles/update':'/roles') ?>" style="max-width:520px;">
    <?= App\Core\csrf_field() ?>
    <?php if (($role['id']??0)): ?><input type="hidden" name="id" value="<?= (int)$role['id'] ?>"><?php endif; ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input class="form-control" type="text" name="name" value="<?= htmlspecialchars($role['name'] ?? '',ENT_QUOTES) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Slug</label>
      <input class="form-control" type="text" name="slug" value="<?= htmlspecialchars($role['slug'] ?? '',ENT_QUOTES) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Permissions</label>
      <div class="d-flex flex-column" style="gap:6px;">
        <?php $assigned = $assigned ?? []; foreach ($perms as $p): $pid=(int)$p['id']; ?>
          <label><input type="checkbox" name="perm[]" value="<?= $pid ?>" <?= in_array($pid,$assigned,true)?'checked':'' ?>> <?= htmlspecialchars($p['slug'],ENT_QUOTES) ?> (<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>)</label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mb-3"><button class="btn btn-primary" type="submit">Save</button></div>
  </form>
</section>

