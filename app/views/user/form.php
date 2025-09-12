<?php 
use function App\Core\base_url; 
use function App\Core\csrf_field;
/** @var array $item, $roles, $assigned */
/** @var string $mode */
$isEdit = $mode === 'edit';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">
            <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus' ?>"></i>
            <?= $isEdit ? 'Edit User' : 'Create New User' ?>
          </h3>
          <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
          </a>
        </div>
      </div>
      
      <form method="post" action="<?= base_url($isEdit ? '/users/update' : '/users') ?>" class="needs-validation" novalidate>
        <div class="card-body">
          <?= csrf_field() ?>
          <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
          <?php endif; ?>
          
          <!-- Flash Messages -->
          <?php if (\App\Core\has_flash('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?= \App\Core\flash_get('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
              <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-user"></i> Basic Information
              </h5>
              
              <div class="mb-3">
                <label for="email" class="form-label">
                  Email Address <span class="text-danger">*</span>
                </label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       value="<?= htmlspecialchars($item['email'] ?? '', ENT_QUOTES) ?>" 
                       required>
                <div class="invalid-feedback">
                  Please provide a valid email address.
                </div>
              </div>
              
              <div class="mb-3">
                <label for="password" class="form-label">
                  Password <?= $isEdit ? '(leave blank to keep current)' : '<span class="text-danger">*</span>' ?>
                </label>
                <div class="input-group">
                  <input type="password" 
                         class="form-control" 
                         id="password" 
                         name="password" 
                         <?= $isEdit ? '' : 'required' ?>
                         minlength="8">
                  <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                    <i class="fas fa-eye" id="password-toggle-icon"></i>
                  </button>
                </div>
                <div class="form-text">
                  Password must be at least 8 characters and contain uppercase, lowercase, and numeric characters.
                </div>
                <div class="invalid-feedback">
                  Password must be at least 8 characters long.
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                      <?php 
                      $currentStatus = $item['status'] ?? 'active';
                      $statuses = [
                        'active' => 'Active',
                        'inactive' => 'Inactive', 
                        'suspended' => 'Suspended',
                        'pending' => 'Pending'
                      ];
                      foreach ($statuses as $value => $label): 
                      ?>
                      <option value="<?= $value ?>" <?= $currentStatus === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="role" class="form-label">Legacy Role (Fallback)</label>
                    <select class="form-select" id="role" name="role">
                      <?php 
                      $currentRole = $item['role'] ?? 'staff';
                      $legacyRoles = ['admin' => 'Administrator', 'manager' => 'Manager', 'staff' => 'Staff'];
                      foreach ($legacyRoles as $value => $label): 
                      ?>
                      <option value="<?= $value ?>" <?= $currentRole === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                      Used when RBAC is not available. 'Administrator' grants all permissions.
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Role-Based Access Control -->
            <div class="col-md-6">
              <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-user-shield"></i> Role-Based Access Control
              </h5>
              
              <?php if (!empty($roles)): ?>
              <div class="mb-3">
                <label class="form-label">Assign Roles</label>
                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                  <?php 
                  $assignedRoles = $assigned ?? [];
                  foreach ($roles as $role): 
                    $roleId = (int)$role['id'];
                    $isAssigned = in_array($roleId, $assignedRoles, true);
                  ?>
                  <div class="form-check mb-2">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="role_<?= $roleId ?>" 
                           name="roles[]" 
                           value="<?= $roleId ?>"
                           <?= $isAssigned ? 'checked' : '' ?>>
                    <label class="form-check-label" for="role_<?= $roleId ?>">
                      <div class="d-flex justify-content-between">
                        <div>
                          <strong><?= htmlspecialchars($role['name'], ENT_QUOTES) ?></strong>
                          <br>
                          <small class="text-muted"><?= htmlspecialchars($role['slug'], ENT_QUOTES) ?></small>
                        </div>
                      </div>
                      <?php if (!empty($role['description'])): ?>
                      <div class="small text-muted mt-1">
                        <?= htmlspecialchars($role['description'], ENT_QUOTES) ?>
                      </div>
                      <?php endif; ?>
                    </label>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-text">
                  Select one or more roles to assign to this user. Roles define sets of permissions.
                </div>
              </div>
              <?php else: ?>
              <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No roles are available. <a href="<?= base_url('/roles/create') ?>">Create a role</a> first.
              </div>
              <?php endif; ?>
              
              <!-- Role Creation Quick Link -->
              <div class="mb-3">
                <a href="<?= base_url('/roles') ?>" class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-cog"></i> Manage Roles
                </a>
                <a href="<?= base_url('/permissions') ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-key"></i> Manage Permissions
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card-footer bg-light">
          <div class="d-flex justify-content-between">
            <a href="<?= base_url('/users') ?>" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> 
              <?= $isEdit ? 'Update User' : 'Create User' ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Bootstrap form validation
(function() {
  'use strict';
  window.addEventListener('load', function() {
    var forms = document.getElementsByClassName('needs-validation');
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();

// Password toggle functionality
function togglePassword() {
  const passwordField = document.getElementById('password');
  const toggleIcon = document.getElementById('password-toggle-icon');
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    toggleIcon.classList.remove('fa-eye');
    toggleIcon.classList.add('fa-eye-slash');
  } else {
    passwordField.type = 'password';
    toggleIcon.classList.remove('fa-eye-slash');
    toggleIcon.classList.add('fa-eye');
  }
}

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
  const password = this.value;
  const feedback = this.parentNode.nextElementSibling;
  
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;
  
  const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
  const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];
  
  if (password.length > 0) {
    feedback.innerHTML = `Password strength: <span class="text-${strengthColors[strength - 1] || 'danger'}">${strengthText[strength - 1] || 'Very Weak'}</span>`;
  } else {
    feedback.innerHTML = 'Password must be at least 8 characters and contain uppercase, lowercase, and numeric characters.';
  }
});
</script>