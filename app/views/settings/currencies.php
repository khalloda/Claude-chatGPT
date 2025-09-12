<?php 
use function App\Core\base_url; 
use function App\Core\csrf_field;
use function App\Core\user_has_permission;
/** @var array $currencies, $base_currency */
/** @var string $page_title */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= htmlspecialchars($page_title ?? 'Currency Management', ENT_QUOTES) ?></h2>
  <div class="btn-group">
    <a class="btn btn-outline-secondary" href="<?= base_url('/settings/tax-currency') ?>">
      <i class="fas fa-arrow-left"></i> Back to Settings
    </a>
    <?php if (user_has_permission('settings.manage')): ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCurrencyModal">
      <i class="fas fa-plus"></i> Add Currency
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

<!-- Base Currency Info -->
<?php if ($base_currency): ?>
<div class="alert alert-info mb-4">
  <div class="d-flex align-items-center">
    <i class="fas fa-info-circle me-2"></i>
    <div>
      <strong>Base Currency:</strong> <?= htmlspecialchars($base_currency['code'], ENT_QUOTES) ?> 
      (<?= htmlspecialchars($base_currency['name'], ENT_QUOTES) ?>)
      <div class="small">All exchange rates are relative to this currency. Exchange rate should be 1.000000 for base currency.</div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Currencies Table -->
<div class="card">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-coins"></i> Currencies
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Currency</th>
            <th>Symbol</th>
            <th>Exchange Rate</th>
            <th>Decimal Places</th>
            <th>Status</th>
            <th>Sample Format</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($currencies)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              <i class="fas fa-coins fa-2x mb-2"></i><br>
              No currencies configured yet.
              <?php if (user_has_permission('settings.manage')): ?>
              <br><button type="button" class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#createCurrencyModal">
                Add First Currency
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($currencies as $currency): ?>
          <tr class="<?= $currency['is_base'] ? 'table-primary' : '' ?>">
            <td>
              <div class="d-flex align-items-center">
                <div>
                  <div class="fw-medium">
                    <?= htmlspecialchars($currency['code'], ENT_QUOTES) ?>
                    <?php if ($currency['is_base']): ?>
                    <span class="badge bg-success ms-1">Base</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small"><?= htmlspecialchars($currency['name'], ENT_QUOTES) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="fw-medium"><?= htmlspecialchars($currency['symbol'], ENT_QUOTES) ?></span>
            </td>
            <td>
              <span class="fw-bold <?= $currency['is_base'] ? 'text-success' : 'text-primary' ?>">
                <?= number_format($currency['exchange_rate'], 6) ?>
              </span>
              <?php if ($currency['is_base']): ?>
              <div class="small text-muted">Base rate</div>
              <?php else: ?>
              <div class="small text-muted">
                1 <?= htmlspecialchars($base_currency['code'] ?? 'BASE', ENT_QUOTES) ?> = 
                <?= number_format($currency['exchange_rate'], 4) ?> <?= htmlspecialchars($currency['code'], ENT_QUOTES) ?>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-light text-dark"><?= (int)$currency['decimal_places'] ?></span>
            </td>
            <td>
              <span class="badge bg-<?= $currency['is_active'] ? 'success' : 'secondary' ?>">
                <?= $currency['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td>
              <code class="small">
                <?php 
                $sampleAmount = 1234.56;
                $formatted = number_format($sampleAmount, (int)$currency['decimal_places']);
                echo htmlspecialchars($currency['symbol'] . ' ' . $formatted, ENT_QUOTES);
                ?>
              </code>
            </td>
            <td>
              <?php if (user_has_permission('settings.manage')): ?>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary" 
                        onclick="editCurrency(<?= (int)$currency['id'] ?>)"
                        title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <?php if (!$currency['is_base']): ?>
                <button type="button" class="btn btn-outline-success" 
                        onclick="setBaseCurrency(<?= (int)$currency['id'] ?>, '<?= htmlspecialchars($currency['code'], ENT_QUOTES) ?>')"
                        title="Set as Base">
                  <i class="fas fa-star"></i>
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-info" 
                        onclick="showRateHistory(<?= (int)$currency['id'] ?>, '<?= htmlspecialchars($currency['code'], ENT_QUOTES) ?>')"
                        title="Rate History">
                  <i class="fas fa-history"></i>
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

<!-- Currency Conversion Calculator -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h6 class="card-title mb-0">
          <i class="fas fa-calculator"></i> Currency Converter
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="number" class="form-control" id="convert_amount" placeholder="Amount" value="100">
          </div>
          <div class="col-md-3">
            <select class="form-select" id="convert_from">
              <?php foreach ($currencies as $curr): ?>
              <option value="<?= htmlspecialchars($curr['code'], ENT_QUOTES) ?>" data-rate="<?= $curr['exchange_rate'] ?>">
                <?= htmlspecialchars($curr['code'], ENT_QUOTES) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-1 text-center">
            <i class="fas fa-arrow-right"></i>
          </div>
          <div class="col-md-3">
            <select class="form-select" id="convert_to">
              <?php foreach ($currencies as $curr): ?>
              <option value="<?= htmlspecialchars($curr['code'], ENT_QUOTES) ?>" data-rate="<?= $curr['exchange_rate'] ?>">
                <?= htmlspecialchars($curr['code'], ENT_QUOTES) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mt-3">
          <div class="alert alert-light" id="conversion_result">
            Enter amount to see conversion
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Currency Modal -->
<div class="modal fade" id="createCurrencyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-plus"></i> Add Currency
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= base_url('/settings/currencies/create') ?>" class="needs-validation" novalidate>
        <div class="modal-body">
          <?= csrf_field() ?>
          
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="create_code" class="form-label">Currency Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="create_code" name="code" required
                       maxlength="3" pattern="[A-Z]{3}" placeholder="USD" style="text-transform: uppercase;">
                <div class="invalid-feedback">Please provide a 3-letter currency code (e.g., USD).</div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="mb-3">
                <label for="create_name" class="form-label">Currency Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="create_name" name="name" required
                       placeholder="US Dollar">
                <div class="invalid-feedback">Please provide a currency name.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_symbol" class="form-label">Symbol</label>
                <input type="text" class="form-control" id="create_symbol" name="symbol" 
                       maxlength="10" placeholder="$">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_exchange_rate" class="form-label">Exchange Rate <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="create_exchange_rate" name="exchange_rate" 
                       min="0.000001" step="0.000001" required placeholder="1.000000">
                <div class="invalid-feedback">Please provide a valid exchange rate.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="create_decimal_places" class="form-label">Decimal Places</label>
                <select class="form-select" id="create_decimal_places" name="decimal_places">
                  <option value="0">0 (100)</option>
                  <option value="2" selected>2 (100.00)</option>
                  <option value="3">3 (100.000)</option>
                  <option value="4">4 (100.0000)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="create_is_base" name="is_base" value="1">
                  <label class="form-check-label" for="create_is_base">
                    Set as base currency
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
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Exchange Rate:</strong> Enter the rate relative to your base currency. 
            For example, if 1 EGP = 0.032 USD, enter 0.032000 for USD.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Currency
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Currency Modal -->
<div class="modal fade" id="editCurrencyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-edit"></i> Edit Currency
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= base_url('/settings/currencies/update') ?>" class="needs-validation" novalidate>
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" id="edit_id" name="id">
          
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="edit_code" class="form-label">Currency Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_code" name="code" required
                       maxlength="3" pattern="[A-Z]{3}" style="text-transform: uppercase;">
                <div class="invalid-feedback">Please provide a 3-letter currency code.</div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="mb-3">
                <label for="edit_name" class="form-label">Currency Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_name" name="name" required>
                <div class="invalid-feedback">Please provide a currency name.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_symbol" class="form-label">Symbol</label>
                <input type="text" class="form-control" id="edit_symbol" name="symbol" maxlength="10">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_exchange_rate" class="form-label">Exchange Rate <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="edit_exchange_rate" name="exchange_rate" 
                       min="0.000001" step="0.000001" required>
                <div class="invalid-feedback">Please provide a valid exchange rate.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_decimal_places" class="form-label">Decimal Places</label>
                <select class="form-select" id="edit_decimal_places" name="decimal_places">
                  <option value="0">0 (100)</option>
                  <option value="2">2 (100.00)</option>
                  <option value="3">3 (100.000)</option>
                  <option value="4">4 (100.0000)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="edit_is_base" name="is_base" value="1">
                  <label class="form-check-label" for="edit_is_base">
                    Set as base currency
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Currency
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Currencies data for JavaScript
const currencies = <?= json_encode($currencies) ?>;

// Edit currency function
function editCurrency(id) {
  const currency = currencies.find(c => c.id == id);
  if (!currency) return;
  
  document.getElementById('edit_id').value = currency.id;
  document.getElementById('edit_code').value = currency.code;
  document.getElementById('edit_name').value = currency.name;
  document.getElementById('edit_symbol').value = currency.symbol || '';
  document.getElementById('edit_exchange_rate').value = currency.exchange_rate;
  document.getElementById('edit_decimal_places').value = currency.decimal_places;
  document.getElementById('edit_is_base').checked = currency.is_base == 1;
  document.getElementById('edit_is_active').checked = currency.is_active == 1;
  
  new bootstrap.Modal(document.getElementById('editCurrencyModal')).show();
}

// Set base currency function
function setBaseCurrency(id, code) {
  if (confirm(`Set ${code} as the base currency? This will affect all exchange rates.`)) {
    // You can implement this as a separate endpoint or modify the edit form
    const currency = currencies.find(c => c.id == id);
    if (currency) {
      editCurrency(id);
      document.getElementById('edit_is_base').checked = true;
    }
  }
}

// Show rate history function (placeholder)
function showRateHistory(id, code) {
  alert(`Rate history for ${code} - This feature can be implemented to show exchange rate changes over time.`);
}

// Currency converter
function updateConversion() {
  const amount = parseFloat(document.getElementById('convert_amount').value) || 0;
  const fromSelect = document.getElementById('convert_from');
  const toSelect = document.getElementById('convert_to');
  
  const fromRate = parseFloat(fromSelect.selectedOptions[0]?.dataset.rate) || 1;
  const toRate = parseFloat(toSelect.selectedOptions[0]?.dataset.rate) || 1;
  const fromCode = fromSelect.value;
  const toCode = toSelect.value;
  
  if (amount > 0) {
    // Convert to base currency first, then to target currency
    const baseAmount = amount / fromRate;
    const convertedAmount = baseAmount * toRate;
    
    document.getElementById('conversion_result').innerHTML = 
      `<strong>${amount.toFixed(2)} ${fromCode}</strong> = <strong>${convertedAmount.toFixed(4)} ${toCode}</strong>`;
  } else {
    document.getElementById('conversion_result').innerHTML = 'Enter amount to see conversion';
  }
}

// Add event listeners for converter
document.addEventListener('DOMContentLoaded', function() {
  ['convert_amount', 'convert_from', 'convert_to'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('input', updateConversion);
      element.addEventListener('change', updateConversion);
    }
  });
  
  // Initial conversion
  updateConversion();
});

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

// Uppercase currency code on input
document.addEventListener('DOMContentLoaded', function() {
  ['create_code', 'edit_code'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
      });
    }
  });
});
</script>
