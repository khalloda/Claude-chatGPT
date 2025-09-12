<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
?>
<section>
  <h2><?= ($mode ?? 'create') === 'edit' ? 'Edit Contact' : 'New Contact' ?></h2>
  <form method="post" action="<?= base_url(($mode ?? 'create')==='edit' ? '/contacts/update' : '/contacts') ?>" style="display:grid;gap:10px;max-width:700px;">
    <?= csrf_field() ?>
    <?php if (($mode ?? 'create')==='edit'): ?>
      <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
    <?php endif; ?>

    <label>Client
      <select name="customer_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
        <option value="">— none —</option>
        <?php foreach (($customers ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($item['customer_id'] ?? 0) === (int)$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name'] ?? '',ENT_QUOTES,'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Name
      <input type="text" name="name" required value="<?= htmlspecialchars($item['name'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    </label>

    <label>Email
      <input type="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    </label>

    <label>Phone
      <input type="text" name="phone" value="<?= htmlspecialchars($item['phone'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    </label>

    <label>Job Title
      <input type="text" name="job_title" value="<?= htmlspecialchars($item['job_title'] ?? '',ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    </label>

    <label>Note
      <textarea name="note" rows="4" style="padding:8px;border:1px solid #ddd;border-radius:8px;"><?= htmlspecialchars($item['note'] ?? '',ENT_QUOTES,'UTF-8') ?></textarea>
    </label>

    <div style="display:flex;gap:10px;">
      <button type="submit" style="padding:10px 14px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer;">Save</button>
      <a href="<?= base_url('/contacts') ?>" style="align-self:center;">Cancel</a>
    </div>
  </form>
</section>

