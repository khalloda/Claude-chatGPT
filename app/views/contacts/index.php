<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
use function App\Core\flash_get;
?>
<section>
  <h2>Contacts</h2>
  <?php if ($m = flash_get('success')): ?><div style="background:#e7f8ee;border:1px solid #b9e7c9;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
  <?php if ($m = flash_get('error')): ?><div style="background:#ffe9e9;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin:10px 0;"><?= htmlspecialchars($m,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>

  <form method="get" action="<?= base_url('/contacts') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
    <input type="text" name="q" placeholder="Search name/email/phone" value="<?= htmlspecialchars((string)($q ?? ''),ENT_QUOTES,'UTF-8') ?>" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
    <select name="customer_id" style="padding:8px;border:1px solid #ddd;border-radius:8px;">
      <option value="">All customers</option>
      <?php foreach (($customers ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($customer_id ?? 0) === (int)$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name'] ?? '',ENT_QUOTES,'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" style="padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;">Filter</button>
    <a href="<?= base_url('/contacts/create') ?>" style="align-self:center;margin-left:auto;">+ New Contact</a>
  </form>

  <table style="width:100%;border-collapse:collapse;">
    <thead><tr>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Name</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Email</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Phone</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Title</th>
      <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Client</th>
      <th style="border-bottom:1px solid #eee;padding:8px;">Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach (($items ?? []) as $ct): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($ct['name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($ct['email'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($ct['phone'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;"><?= htmlspecialchars($ct['job_title'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;">
            <?php if (!empty($ct['customer_id'])): ?>
              <a href="<?= base_url('/customers/view?id='.(int)$ct['customer_id']) ?>"><?= htmlspecialchars($ct['customer_name'] ?? '',ENT_QUOTES,'UTF-8') ?></a>
            <?php endif; ?>
          </td>
          <td style="padding:8px;border-bottom:1px solid #f2f2f4;white-space:nowrap;">
            <a href="<?= base_url('/contacts/edit?id='.(int)$ct['id']) ?>">Edit</a> &nbsp;|&nbsp;
            <form method="post" action="<?= base_url('/contacts/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this contact?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$ct['id'] ?>">
              <button type="submit" style="background:none;border:none;color:#c00;cursor:pointer;">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items ?? [])): ?><tr><td colspan="6" style="padding:12px;">No contacts.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

