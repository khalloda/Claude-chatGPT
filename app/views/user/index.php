<?php 
use function App\Core\base_url; 
use function App\Core\user_has_permission;
/** @var array $rows, $statuses, $roles, $pagination */
/** @var string $search, $status, $role, $page_title */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= htmlspecialchars($page_title ?? 'User Management', ENT_QUOTES) ?></h2>
  <div class="btn-group">
    <a class="btn btn-outline-secondary" href="<?= base_url('/') ?>">
      <i class="fas fa-arrow-left"></i> Back
    </a>
    <?php if (user_has_permission('users.manage')): ?>
    <a class="btn btn-primary" href="<?= base_url('/users/create') ?>">
      <i class="fas fa-plus"></i> Add User
    </a>
    <?php endif; ?>
    <a class="btn btn-outline-primary" href="<?= base_url('/roles') ?>">
      <i class="fas fa-user-shield"></i> Roles
    </a>
    <a class="btn btn-outline-primary" href="<?= base_url('/permissions') ?>">
      <i class="fas fa-key"></i> Permissions
    </a>
  </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?= base_url('/users') ?>" class="row g-3">
      <div class="col-md-4">
        <label for="search" class="form-label">Search</label>
        <input type="text" class="form-control" id="search" name="search" 
               value="<?= htmlspecialchars($search ?? '', ENT_QUOTES) ?>" 
               placeholder="Email or User ID">
      </div>
      <div class="col-md-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="">All Statuses</option>
          <?php foreach ($statuses ?? [] as $s): ?>
          <option value="<?= htmlspecialchars($s, ENT_QUOTES) ?>" 
                  <?= ($status ?? '') === $s ? 'selected' : '' ?>>
            <?= ucfirst(htmlspecialchars($s, ENT_QUOTES)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="role" class="form-label">Legacy Role</label>
        <select class="form-select" id="role" name="role">
          <option value="">All Roles</option>
          <?php foreach ($roles ?? [] as $r): ?>
          <option value="<?= htmlspecialchars($r, ENT_QUOTES) ?>" 
                  <?= ($role ?? '') === $r ? 'selected' : '' ?>>
            <?= ucfirst(htmlspecialchars($r, ENT_QUOTES)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary me-2">
          <i class="fas fa-search"></i> Search
        </button>
        <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary">
          <i class="fas fa-times"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Results Summary -->
<?php if (isset($pagination)): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <span class="text-muted">
    Showing <?= count($rows) ?> of <?= $pagination['total'] ?> users
  </span>
  <div class="text-muted">
    Page <?= $pagination['current'] ?> of <?= $pagination['pages'] ?>
  </div>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Status</th>
            <th>Legacy Role</th>
            <th>RBAC Roles</th>
            <th>Last Login</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              <i class="fas fa-users fa-2x mb-2"></i><br>
              No users found matching your criteria.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($rows as $user): ?>
          <tr>
            <td>
              <span class="badge bg-light text-dark"><?= (int)($user['id'] ?? 0) ?></span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar me-2">
                  <span class="avatar-initial bg-primary">
                    <?= strtoupper(substr($user['email'] ?? '', 0, 1)) ?>
                  </span>
                </div>
                <div>
                  <div class="fw-medium"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?></div>
                  <small class="text-muted">ID: <?= (int)($user['id'] ?? 0) ?></small>
                </div>
              </div>
            </td>
            <td>
              <?php 
              $statusClass = [
                'active' => 'success',
                'inactive' => 'secondary', 
                'suspended' => 'danger',
                'pending' => 'warning'
              ];
              $userStatus = $user['status'] ?? 'active';
              $badgeClass = $statusClass[$userStatus] ?? 'secondary';
              ?>
              <span class="badge bg-<?= $badgeClass ?>">
                <?= ucfirst(htmlspecialchars($userStatus, ENT_QUOTES)) ?>
              </span>
            </td>
            <td>
              <span class="badge bg-info">
                <?= ucfirst(htmlspecialchars($user['role'] ?? 'staff', ENT_QUOTES)) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($user['role_names'])): ?>
              <div class="d-flex flex-wrap gap-1">
                <?php 
                $roleNames = explode(', ', $user['role_names']);
                foreach ($roleNames as $roleName): 
                ?>
                <span class="badge bg-outline-primary">
                  <?= htmlspecialchars(trim($roleName), ENT_QUOTES) ?>
                </span>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <span class="text-muted">No RBAC roles</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($user['last_login_at'])): ?>
              <small class="text-muted">
                <?= date('M j, Y g:i A', strtotime($user['last_login_at'])) ?>
              </small>
              <?php else: ?>
              <span class="text-muted">Never</span>
              <?php endif; ?>
            </td>
            <td>
              <small class="text-muted">
                <?= date('M j, Y', strtotime($user['created_at'] ?? '')) ?>
              </small>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <?php if (user_has_permission('users.view')): ?>
                <a href="<?= base_url('/users/show?id=' . (int)($user['id'] ?? 0)) ?>" 
                   class="btn btn-outline-primary" title="View Details">
                  <i class="fas fa-eye"></i>
                </a>
                <?php endif; ?>
                
                <?php if (user_has_permission('users.manage')): ?>
                <a href="<?= base_url('/users/edit?id=' . (int)($user['id'] ?? 0)) ?>" 
                   class="btn btn-outline-secondary" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                
                <!-- Status Toggle -->
                <?php if ($user['status'] === 'active'): ?>
                <form method="post" action="<?= base_url('/users/toggle-status') ?>" style="display:inline;">
                  <?= \App\Core\csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
                  <input type="hidden" name="status" value="inactive">
                  <button type="submit" class="btn btn-outline-warning" title="Deactivate"
                          onclick="return confirm('Deactivate this user?')">
                    <i class="fas fa-pause"></i>
                  </button>
                </form>
                <?php else: ?>
                <form method="post" action="<?= base_url('/users/toggle-status') ?>" style="display:inline;">
                  <?= \App\Core\csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
                  <input type="hidden" name="status" value="active">
                  <button type="submit" class="btn btn-outline-success" title="Activate"
                          onclick="return confirm('Activate this user?')">
                    <i class="fas fa-play"></i>
                  </button>
                </form>
                <?php endif; ?>
                
                <!-- Delete Button -->
                <?php 
                $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
                $userId = (int)($user['id'] ?? 0);
                if ($userId !== $currentUserId): 
                ?>
                <form method="post" action="<?= base_url('/users/delete') ?>" style="display:inline;">
                  <?= \App\Core\csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $userId ?>">
                  <button type="submit" class="btn btn-outline-danger" title="Delete"
                          onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Pagination -->
<?php if (isset($pagination) && $pagination['pages'] > 1): ?>
<nav aria-label="Users pagination" class="mt-4">
  <ul class="pagination justify-content-center">
    <!-- Previous Page -->
    <?php if ($pagination['current'] > 1): ?>
    <li class="page-item">
      <a class="page-link" href="<?= base_url('/users?' . http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1]))) ?>">
        <i class="fas fa-chevron-left"></i> Previous
      </a>
    </li>
    <?php else: ?>
    <li class="page-item disabled">
      <span class="page-link"><i class="fas fa-chevron-left"></i> Previous</span>
    </li>
    <?php endif; ?>
    
    <!-- Page Numbers -->
    <?php
    $start = max(1, $pagination['current'] - 2);
    $end = min($pagination['pages'], $pagination['current'] + 2);
    
    if ($start > 1): ?>
    <li class="page-item">
      <a class="page-link" href="<?= base_url('/users?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>">1</a>
    </li>
    <?php if ($start > 2): ?>
    <li class="page-item disabled"><span class="page-link">...</span></li>
    <?php endif; ?>
    <?php endif; ?>
    
    <?php for ($i = $start; $i <= $end; $i++): ?>
    <li class="page-item <?= $i === $pagination['current'] ? 'active' : '' ?>">
      <a class="page-link" href="<?= base_url('/users?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    
    <?php if ($end < $pagination['pages']): ?>
    <?php if ($end < $pagination['pages'] - 1): ?>
    <li class="page-item disabled"><span class="page-link">...</span></li>
    <?php endif; ?>
    <li class="page-item">
      <a class="page-link" href="<?= base_url('/users?' . http_build_query(array_merge($_GET, ['page' => $pagination['pages']]))) ?>"><?= $pagination['pages'] ?></a>
    </li>
    <?php endif; ?>
    
    <!-- Next Page -->
    <?php if ($pagination['current'] < $pagination['pages']): ?>
    <li class="page-item">
      <a class="page-link" href="<?= base_url('/users?' . http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1]))) ?>">
        Next <i class="fas fa-chevron-right"></i>
      </a>
    </li>
    <?php else: ?>
    <li class="page-item disabled">
      <span class="page-link">Next <i class="fas fa-chevron-right"></i></span>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

<style>
.avatar {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}
.avatar-initial {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 14px;
  font-weight: 600;
  color: white;
}
.bg-outline-primary {
  background-color: transparent;
  border: 1px solid #0d6efd;
  color: #0d6efd;
}
</style>