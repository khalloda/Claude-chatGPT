<?php 
use function App\Core\base_url; 
use function App\Core\csrf_field;
/** @var array $company_settings, $currency_settings, $tax_settings, $tax_rates, $currencies, $base_currency */
/** @var string $page_title */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= htmlspecialchars($page_title ?? 'Taxes & Currency Settings', ENT_QUOTES) ?></h2>
  <div class="btn-group">
    <a class="btn btn-outline-secondary" href="<?= base_url('/') ?>">
      <i class="fas fa-arrow-left"></i> Back
    </a>
    <a class="btn btn-outline-primary" href="<?= base_url('/settings/tax-rates') ?>">
      <i class="fas fa-percentage"></i> Manage Tax Rates
    </a>
    <a class="btn btn-outline-primary" href="<?= base_url('/settings/currencies') ?>">
      <i class="fas fa-coins"></i> Manage Currencies
    </a>
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

<form method="post" action="<?= base_url('/settings/tax-currency/update') ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  
  <div class="row">
    <!-- Company Information -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-building"></i> Company Information
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="company_name" class="form-label">Company Name</label>
            <input type="text" class="form-control" id="company_name" name="company[company_name]" 
                   value="<?= htmlspecialchars($company_settings['company_name']['value'] ?? '', ENT_QUOTES) ?>"
                   placeholder="Your Company Name">
          </div>
          
          <div class="mb-3">
            <label for="company_address" class="form-label">Address</label>
            <textarea class="form-control" id="company_address" name="company[company_address]" rows="3"
                      placeholder="Company address for invoices"><?= htmlspecialchars($company_settings['company_address']['value'] ?? '', ENT_QUOTES) ?></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="company_phone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="company_phone" name="company[company_phone]" 
                       value="<?= htmlspecialchars($company_settings['company_phone']['value'] ?? '', ENT_QUOTES) ?>"
                       placeholder="+20 xxx xxx xxxx">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="company_email" class="form-label">Email</label>
                <input type="email" class="form-control" id="company_email" name="company[company_email]" 
                       value="<?= htmlspecialchars($company_settings['company_email']['value'] ?? '', ENT_QUOTES) ?>"
                       placeholder="info@company.com">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Currency Settings -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-dollar-sign"></i> Currency Settings
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="base_currency" class="form-label">Base Currency</label>
            <select class="form-select" id="base_currency" name="currency[base_currency]">
              <?php 
              $currentBaseCurrency = $currency_settings['base_currency']['value'] ?? 'EGP';
              foreach ($currencies as $currency): 
              ?>
              <option value="<?= htmlspecialchars($currency['code'], ENT_QUOTES) ?>" 
                      <?= $currency['code'] === $currentBaseCurrency ? 'selected' : '' ?>>
                <?= htmlspecialchars($currency['code'] . ' - ' . $currency['name'], ENT_QUOTES) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="currency_symbol" class="form-label">Currency Symbol</label>
            <input type="text" class="form-control" id="currency_symbol" name="currency[currency_symbol]" 
                   value="<?= htmlspecialchars($currency_settings['currency_symbol']['value'] ?? 'EGP', ENT_QUOTES) ?>"
                   placeholder="EGP" maxlength="10">
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="currency_position" class="form-label">Symbol Position</label>
                <select class="form-select" id="currency_position" name="currency[currency_position]">
                  <?php $currentPosition = $currency_settings['currency_position']['value'] ?? 'after'; ?>
                  <option value="before" <?= $currentPosition === 'before' ? 'selected' : '' ?>>Before ($ 100.00)</option>
                  <option value="after" <?= $currentPosition === 'after' ? 'selected' : '' ?>>After (100.00 EGP)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="decimal_places" class="form-label">Decimal Places</label>
                <select class="form-select" id="decimal_places" name="currency[decimal_places]">
                  <?php $currentDecimals = $currency_settings['decimal_places']['value'] ?? 2; ?>
                  <option value="0" <?= $currentDecimals == 0 ? 'selected' : '' ?>>0 (100)</option>
                  <option value="2" <?= $currentDecimals == 2 ? 'selected' : '' ?>>2 (100.00)</option>
                  <option value="3" <?= $currentDecimals == 3 ? 'selected' : '' ?>>3 (100.000)</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row">
    <!-- Tax Settings -->
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-percentage"></i> Tax Settings
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="default_tax_rate" class="form-label">Default Tax Rate (%)</label>
            <input type="number" class="form-control" id="default_tax_rate" name="tax[default_tax_rate]" 
                   value="<?= htmlspecialchars($tax_settings['default_tax_rate']['value'] ?? '14', ENT_QUOTES) ?>"
                   min="0" max="100" step="0.01" placeholder="14.00">
          </div>
          
          <div class="mb-3">
            <label for="tax_calculation_method" class="form-label">Tax Calculation Method</label>
            <select class="form-select" id="tax_calculation_method" name="tax[tax_calculation_method]">
              <?php $currentMethod = $tax_settings['tax_calculation_method']['value'] ?? 'exclusive'; ?>
              <option value="exclusive" <?= $currentMethod === 'exclusive' ? 'selected' : '' ?>>
                Tax Exclusive (Tax added to amount)
              </option>
              <option value="inclusive" <?= $currentMethod === 'inclusive' ? 'selected' : '' ?>>
                Tax Inclusive (Tax included in amount)
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Current Tax Rates Summary -->
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Current Tax Rates
          </h5>
        </div>
        <div class="card-body">
          <?php if (empty($tax_rates)): ?>
          <div class="text-center text-muted py-3">
            <i class="fas fa-percentage fa-2x mb-2"></i>
            <div>No tax rates configured</div>
          </div>
          <?php else: ?>
          <div class="row g-2">
            <?php foreach (array_slice($tax_rates, 0, 4) as $rate): ?>
            <div class="col-md-6">
              <div class="border rounded p-2 <?= $rate['is_default'] ? 'border-primary bg-light' : '' ?>">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-medium"><?= htmlspecialchars($rate['name'], ENT_QUOTES) ?></div>
                    <div class="text-muted small"><?= ucfirst($rate['type']) ?></div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold"><?= number_format($rate['rate'], 2) ?>%</div>
                    <?php if ($rate['is_default']): ?>
                    <span class="badge bg-primary">Default</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Current Currencies Summary -->
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-coins"></i> Active Currencies
          </h5>
        </div>
        <div class="card-body">
          <?php if (empty($currencies)): ?>
          <div class="text-center text-muted py-3">
            <i class="fas fa-coins fa-2x mb-2"></i>
            <div>No currencies configured</div>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Symbol</th>
                  <th>Exchange Rate</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($currencies, 0, 6) as $currency): ?>
                <tr>
                  <td>
                    <span class="fw-medium"><?= htmlspecialchars($currency['code'], ENT_QUOTES) ?></span>
                    <?php if ($currency['is_base']): ?>
                    <span class="badge bg-success ms-1">Base</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($currency['name'], ENT_QUOTES) ?></td>
                  <td><?= htmlspecialchars($currency['symbol'], ENT_QUOTES) ?></td>
                  <td><?= number_format($currency['exchange_rate'], 4) ?></td>
                  <td>
                    <span class="badge bg-<?= $currency['is_active'] ? 'success' : 'secondary' ?>">
                      <?= $currency['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Save Button -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <a href="<?= base_url('/') ?>" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Save Settings
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
