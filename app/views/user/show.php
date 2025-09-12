<?php 
use function App\Core\base_url; 
use function App\Core\user_has_permission;
/** @var array $user, $permissions */
/** @var string $page_title */
?>
<div class="row">
  <div class="col-lg-8">
    <!-- User Information Card -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">
            <i class="fas fa-user"></i> User Details
          </h3>
          <div class="btn-group">
            <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if (user_has_permission('users.manage')): ?>
            <a href="<?= base_url('/users/edit?id=' . (int)($user['id'] ?? 0)) ?>" class="btn btn-primary">
              <i class="fas fa-edit"></i> Edit User
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="card-body">
        <div class="row">
          <!-- Avatar and Basic Info -->
          <div class="col-md-3 text-center mb-4">
            <div class="avatar-lg mx-auto mb-3">
              <span class="avatar-initial bg-primary">
                <?= strtoupper(substr($user['email'] ?? '', 0, 2)) ?>
              </span>
            </div>
            <h5 class="mb-1"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?></h5>
            <p class="text-muted mb-2">User ID: <?= (int)($user['id'] ?? 0) ?></p>
            
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
            <span class="badge bg-<?= $badgeClass ?> fs-6">
              <?= ucfirst(htmlspecialchars($userStatus, ENT_QUOTES)) ?>
            </span>
          </div>
          
          <!-- User Details -->
          <div class="col-md-9">
            <div class="row">
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Email Address</label>
                <div class="fw-medium"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?></div>
              </div>
              
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Legacy Role</label>
                <div class="fw-medium">
                  <span class="badge bg-info">
                    <?= ucfirst(htmlspecialchars($user['role'] ?? 'staff', ENT_QUOTES)) ?>
                  </span>
                </div>
              </div>
              
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Account Status</label>
                <div class="fw-medium">
                  <span class="badge bg-<?= $badgeClass ?>">
                    <?= ucfirst(htmlspecialchars($userStatus, ENT_QUOTES)) ?>
                  </span>
                </div>
              </div>
              
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Last Login</label>
                <div class="fw-medium">
                  <?php if (!empty($user['last_login_at'])): ?>
                  <?= date('F j, Y \a\t g:i A', strtotime($user['last_login_at'])) ?>
                  <br><small class="text-muted"><?= \App\Core\time_ago($user['last_login_at']) ?></small>
                  <?php else: ?>
                  <span class="text-muted">Never logged in</span>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Account Created</label>
                <div class="fw-medium">
                  <?= date('F j, Y \a\t g:i A', strtotime($user['created_at'] ?? '')) ?>
                  <br><small class="text-muted"><?= \App\Core\time_ago($user['created_at'] ?? '') ?></small>
                </div>
              </div>
              
              <div class="col-sm-6 mb-3">
                <label class="form-label text-muted">Last Updated</label>
                <div class="fw-medium">
                  <?php if (!empty($user['updated_at'])): ?>
                  <?= date('F j, Y \a\t g:i A', strtotime($user['updated_at'])) ?>
                  <br><small class="text-muted"><?= \App\Core\time_ago($user['updated_at']) ?></small>
                  <?php else: ?>
                  <span class="text-muted">Never updated</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <!-- RBAC Roles Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-user-shield"></i> Assigned Roles
        </h5>
      </div>
      <div class="card-body">
        <?php if (!empty($user['role_names'])): ?>
        <div class="d-flex flex-column gap-2">
          <?php 
          $roleNames = explode(', ', $user['role_names']);
          foreach ($roleNames as $roleName): 
          ?>
          <div class="d-flex align-items-center">
            <i class="fas fa-shield-alt text-primary me-2"></i>
            <span class="fw-medium"><?= htmlspecialchars(trim($roleName), ENT_QUOTES) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-3">
          <i class="fas fa-user-times fa-2x mb-2"></i>
          <div>No RBAC roles assigned</div>
          <small>User permissions are based on legacy role</small>
        </div>
        <?php endif; ?>
        
        <?php if (user_has_permission('users.manage')): ?>
        <div class="mt-3">
          <a href="<?= base_url('/users/edit?id=' . (int)($user['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit"></i> Modify Roles
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Quick Actions Card -->
    <?php if (user_has_permission('users.manage')): ?>
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-bolt"></i> Quick Actions
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <!-- Status Toggle -->
          <?php if ($user['status'] === 'active'): ?>
          <form method="post" action="<?= base_url('/users/toggle-status') ?>">
            <?= \App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
            <input type="hidden" name="status" value="inactive">
            <button type="submit" class="btn btn-outline-warning btn-sm w-100"
                    onclick="return confirm('Deactivate this user account?')">
              <i class="fas fa-pause"></i> Deactivate Account
            </button>
          </form>
          <?php else: ?>
          <form method="post" action="<?= base_url('/users/toggle-status') ?>">
            <?= \App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
            <input type="hidden" name="status" value="active">
            <button type="submit" class="btn btn-outline-success btn-sm w-100"
                    onclick="return confirm('Activate this user account?')">
              <i class="fas fa-play"></i> Activate Account
            </button>
          </form>
          <?php endif; ?>
          
          <!-- Suspend User -->
          <?php if ($user['status'] !== 'suspended'): ?>
          <form method="post" action="<?= base_url('/users/toggle-status') ?>">
            <?= \App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
            <input type="hidden" name="status" value="suspended">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                    onclick="return confirm('Suspend this user account? User will be unable to login.')">
              <i class="fas fa-ban"></i> Suspend Account
            </button>
          </form>
          <?php endif; ?>
          
          <!-- Delete User -->
          <?php 
          $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
          $userId = (int)($user['id'] ?? 0);
          if ($userId !== $currentUserId): 
          ?>
          <form method="post" action="<?= base_url('/users/delete') ?>">
            <?= \App\Core\csrf_field() ?>
            <input type="hidden" name="id" value="<?= $userId ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                    onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone and will remove all user data.')">
              <i class="fas fa-trash"></i> Delete User
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Permissions Overview -->
<?php if (!empty($permissions)): ?>
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-key"></i> Effective Permissions
        </h5>
      </div>
      <div class="card-body">
        <div class="row">
          <?php foreach ($permissions as $category => $perms): ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <h6 class="text-primary border-bottom pb-1">
              <?= ucwords(str_replace('_', ' ', htmlspecialchars($category, ENT_QUOTES))) ?>
            </h6>
            <div class="ms-2">
              <?php foreach ($perms as $perm): ?>
              <div class="d-flex align-items-center mb-1">
                <i class="fas fa-check-circle text-success me-2"></i>
                <small title="<?= htmlspecialchars($perm['description'] ?? '', ENT_QUOTES) ?>">
                  <?= htmlspecialchars($perm['name'], ENT_QUOTES) ?>
                </small>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.avatar-lg {
  width: 80px;
  height: 80px;
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
  font-size: 24px;
  font-weight: 600;
  color: white;
}
</style>

<?php
// Add time_ago helper if it doesn't exist
if (!function_exists('App\Core\time_ago')) {
    function time_ago($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
?>
