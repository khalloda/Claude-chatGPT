<?php 
use function App\Core\base_url; 
use function App\Core\csrf_field;
use function App\Core\user_has_permission;
/** @var array $tax_rates, $tax_types */
/** @var string $page_title */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= htmlspecialchars($page_title ?? 'Tax Rate Management', ENT_QUOTES) ?></h2>
  <div class="btn-group">
    <a class="btn btn-outline-secondary" href="<?= base_url('/settings/tax-currency') ?>">
      <i class="fas fa-arrow-left"></i> Back to Settings
    </a>
    <?php if (user_has_permission('settings.manage')): ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaxRateModal">
      <i class="fas fa-plus"></i> Add Tax Rate
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Flash Messages -->
<?php if (\App\Core\has_flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="fas fa-check-circle"></i> <?= \App\Core\flash_get('success') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (\App\Core\has_flash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="fas fa-exclamation-triangle"></i> <?= \App\Core\flash_get('error') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Tax Rates Table -->
<div class="card">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-percentage"></i> Tax Rates
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Rate</th>
            <th>Type</th>
            <th>Status</th>
            <th>Effective Period</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tax_rates)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              <i class="fas fa-percentage fa-2x mb-2"></i><br>
              No tax rates configured yet.
              <?php if (user_has_permission('settings.manage')): ?>
              <br><button type="button" class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#createTaxRateModal">
                Add First Tax Rate
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($tax_rates as $rate): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div>
                  <div class="fw-medium"><?= htmlspecialchars($rate['name'], ENT_QUOTES) ?></div>
                  <?php if ($rate['is_default']): ?>
                  <span class="badge bg-primary">Default</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="fw-bold text-primary"><?= number_format($rate['rate'], 2) ?>%</span>
            </td>
            <td>
              <span class="badge bg-info">
                <?= ucfirst(htmlspecialchars($rate['type'], ENT_QUOTES)) ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?= $rate['is_active'] ? 'success' : 'secondary' ?>">
                <?= $rate['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td>
              <?php if ($rate['effective_from'] || $rate['effective_to']): ?>
              <small class="text-muted">
                <?php if ($rate['effective_from']): ?>
                From: <?= date('M j, Y', strtotime($rate['effective_from'])) ?><br>
                <?php endif; ?>
                <?php if ($rate['effective_to']): ?>
                To: <?= date('M j, Y', strtotime($rate['effective_to'])) ?>
                <?php endif; ?>
              </small>
              <?php else: ?>
              <span class="text-muted">No limits</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($rate['description'])): ?>
              <small class="text-muted"><?= htmlspecialchars($rate['description'], ENT_QUOTES) ?></small>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (user_has_permission('settings.manage')): ?>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary" 
                        onclick="editTaxRate(<?= (int)$rate['id'] ?>)"
                        title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" 
                        onclick="deleteTaxRate(<?= (int)$rate['id'] ?>, '<?= htmlspecialchars($rate['name'], ENT_QUOTES) ?>')"
                        title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Tax Rate Modal -->
<div class="modal fade" id="createTaxRateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-plus"></i> Add Tax Rate
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= base_url('/settings/tax-rates/create') ?>" class="needs-validation" novalidate>
        <div class="modal-body">
          <?= csrf_field() ?>
          
          <div class="row">
            <div class="col-md-8">
              <div class="mb-3">
                <label for="create_name" class="form-label">Tax Rate Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="create_name" name="name" required
                       placeholder="e.g., Standard VAT">
                <div class="invalid-feedback">Please provide a tax rate name.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="create_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="create_rate" name="rate" 
                       min="0" max="100" step="0.01" required placeholder="14.00">
                <div class="invalid-feedback">Please provide a valid rate between 0 and 100.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_type" class="form-label">Tax Type</label>
                <select class="form-select" id="create_type" name="type">
                  <?php foreach ($tax_types as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="create_is_default" name="is_default" value="1">
                  <label class="form-check-label" for="create_is_default">
                    Set as default for this type
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                  <label class="form-check-label" for="create_is_active">
                    Active
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="create_description" class="form-label">Description</label>
            <textarea class="form-control" id="create_description" name="description" rows="2"
                      placeholder="Optional description for this tax rate"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_effective_from" class="form-label">Effective From</label>
                <input type="date" class="form-control" id="create_effective_from" name="effective_from">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_effective_to" class="form-label">Effective To</label>
                <input type="date" class="form-control" id="create_effective_to" name="effective_to">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Tax Rate
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Tax Rate Modal -->
<div class="modal fade" id="editTaxRateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-edit"></i> Edit Tax Rate
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= base_url('/settings/tax-rates/update') ?>" class="needs-validation" novalidate>
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" id="edit_id" name="id">
          
          <div class="row">
            <div class="col-md-8">
              <div class="mb-3">
                <label for="edit_name" class="form-label">Tax Rate Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_name" name="name" required>
                <div class="invalid-feedback">Please provide a tax rate name.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="edit_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="edit_rate" name="rate" 
                       min="0" max="100" step="0.01" required>
                <div class="invalid-feedback">Please provide a valid rate between 0 and 100.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_type" class="form-label">Tax Type</label>
                <select class="form-select" id="edit_type" name="type">
                  <?php foreach ($tax_types as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default" value="1">
                  <label class="form-check-label" for="edit_is_default">
                    Set as default for this type
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                  <label class="form-check-label" for="edit_is_active">
                    Active
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_effective_from" class="form-label">Effective From</label>
                <input type="date" class="form-control" id="edit_effective_from" name="effective_from">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_effective_to" class="form-label">Effective To</label>
                <input type="date" class="form-control" id="edit_effective_to" name="effective_to">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Tax Rate
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTaxRateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="fas fa-exclamation-triangle"></i> Delete Tax Rate
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete the tax rate <strong id="delete_tax_name"></strong>?</p>
        <p class="text-muted">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="<?= base_url('/settings/tax-rates/delete') ?>" style="display: inline;">
          <?= csrf_field() ?>
          <input type="hidden" id="delete_tax_id" name="id">
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash"></i> Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Tax rates data for JavaScript
const taxRates = <?= json_encode($tax_rates) ?>;

// Edit tax rate function
function editTaxRate(id) {
  const rate = taxRates.find(r => r.id == id);
  if (!rate) return;
  
  document.getElementById('edit_id').value = rate.id;
  document.getElementById('edit_name').value = rate.name;
  document.getElementById('edit_rate').value = rate.rate;
  document.getElementById('edit_type').value = rate.type;
  document.getElementById('edit_is_default').checked = rate.is_default == 1;
  document.getElementById('edit_is_active').checked = rate.is_active == 1;
  document.getElementById('edit_description').value = rate.description || '';
  document.getElementById('edit_effective_from').value = rate.effective_from || '';
  document.getElementById('edit_effective_to').value = rate.effective_to || '';
  
  new bootstrap.Modal(document.getElementById('editTaxRateModal')).show();
}

// Delete tax rate function
function deleteTaxRate(id, name) {
  document.getElementById('delete_tax_id').value = id;
  document.getElementById('delete_tax_name').textContent = name;
  new bootstrap.Modal(document.getElementById('deleteTaxRateModal')).show();
}

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
</script>
