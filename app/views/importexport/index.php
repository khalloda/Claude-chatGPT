<?php
/**
 * File: app/views/importexport/index.php
 * Import/Export Management Interface
 */

use function App\Core\base_url;

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$t = fn($key) => \App\Core\t($key);
?>

<div class="page-header">
  <div class="title d-flex align-items-center gap-2">
    <?= $t('importexport.import_export') ?>
  </div>
  <nav aria-label="breadcrumb" class="ms-auto">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('/') ?>"><?= $t('nav.dashboard') ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= $t('importexport.import_export') ?></li>
    </ol>
  </nav>
</div>

<div class="row g-3">
  <!-- Export Section -->
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?= $t('importexport.export_data') ?></h5>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('/import/export') ?>" id="exportForm">
          <?= \App\Core\csrf_field() ?>
          
          <!-- Export Type -->
          <div class="mb-3">
            <label for="exportType" class="form-label"><?= $t('importexport.export_type') ?></label>
            <select class="form-select" id="exportType" name="type" required>
              <option value="bulk"><?= $t('importexport.bulk_export') ?></option>
              <option value="module"><?= $t('importexport.module_export') ?></option>
              <option value="custom"><?= $t('importexport.custom_export') ?></option>
            </select>
            <div class="form-text"><?= $t('importexport.export_type_help') ?></div>
          </div>

          <!-- Module Selection -->
          <div class="mb-3">
            <label for="module" class="form-label"><?= $t('importexport.select_module') ?></label>
            <select class="form-select" id="module" name="module" required>
              <option value=""><?= $t('importexport.choose_module') ?></option>
              <option value="all"><?= $t('importexport.all_modules') ?></option>
              <optgroup label="<?= $t('importexport.inventory') ?>">
                <option value="products"><?= $t('importexport.products') ?></option>
                <option value="categories"><?= $t('importexport.categories') ?></option>
                <option value="makes"><?= $t('importexport.makes') ?></option>
                <option value="models"><?= $t('importexport.models') ?></option>
                <option value="warehouses"><?= $t('importexport.warehouses') ?></option>
              </optgroup>
              <optgroup label="<?= $t('importexport.sales') ?>">
                <option value="customers"><?= $t('importexport.customers') ?></option>
                <option value="quotes"><?= $t('importexport.quotes') ?></option>
                <option value="orders"><?= $t('importexport.orders') ?></option>
                <option value="invoices"><?= $t('importexport.invoices') ?></option>
              </optgroup>
              <optgroup label="<?= $t('importexport.purchasing') ?>">
                <option value="suppliers"><?= $t('importexport.suppliers') ?></option>
                <option value="purchase_orders"><?= $t('importexport.purchase_orders') ?></option>
                <option value="purchase_invoices"><?= $t('importexport.purchase_invoices') ?></option>
              </optgroup>
              <optgroup label="<?= $t('importexport.system') ?>">
                <option value="users"><?= $t('importexport.users') ?></option>
              </optgroup>
            </select>
          </div>

          <!-- Format Selection -->
          <div class="mb-3">
            <label for="format" class="form-label"><?= $t('importexport.export_format') ?></label>
            <div class="row">
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="format" id="formatCSV" value="csv" checked>
                  <label class="form-check-label" for="formatCSV">
                    <i class="ti ti-file-text text-success"></i> CSV
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="format" id="formatXLS" value="xls">
                  <label class="form-check-label" for="formatXLS">
                    <i class="ti ti-file-spreadsheet text-primary"></i> XLS
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="format" id="formatXLSX" value="xlsx">
                  <label class="form-check-label" for="formatXLSX">
                    <i class="ti ti-file-spreadsheet text-primary"></i> XLSX
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf">
                  <label class="form-check-label" for="formatPDF">
                    <i class="ti ti-file-text text-danger"></i> PDF
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Export Button -->
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-download"></i> <?= $t('importexport.export_data') ?>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
              <i class="ti ti-refresh"></i> <?= $t('common.reset') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Information Panel -->
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><?= $t('importexport.export_info') ?></h6>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-primary"><?= $t('importexport.supported_formats') ?></h6>
          <ul class="list-unstyled small">
            <li><i class="ti ti-check text-success"></i> <strong>CSV:</strong> <?= $t('importexport.csv_description') ?></li>
            <li><i class="ti ti-check text-success"></i> <strong>XLS/XLSX:</strong> <?= $t('importexport.excel_description') ?></li>
            <li><i class="ti ti-check text-success"></i> <strong>PDF:</strong> <?= $t('importexport.pdf_description') ?></li>
          </ul>
        </div>

        <div class="mb-3">
          <h6 class="text-primary"><?= $t('importexport.export_types') ?></h6>
          <ul class="list-unstyled small">
            <li><strong><?= $t('importexport.bulk_export') ?>:</strong> <?= $t('importexport.bulk_description') ?></li>
            <li><strong><?= $t('importexport.module_export') ?>:</strong> <?= $t('importexport.module_description') ?></li>
            <li><strong><?= $t('importexport.custom_export') ?>:</strong> <?= $t('importexport.custom_description') ?></li>
          </ul>
        </div>

        <div class="alert alert-info">
          <i class="ti ti-info-circle"></i>
          <strong><?= $t('importexport.note') ?>:</strong> <?= $t('importexport.export_note') ?>
        </div>
      </div>
    </div>

    <!-- Quick Export Buttons -->
    <div class="card mt-3">
      <div class="card-header">
        <h6 class="mb-0"><?= $t('importexport.quick_export') ?></h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <button class="btn btn-outline-primary btn-sm" onclick="quickExport('products', 'csv')">
            <i class="ti ti-package"></i> <?= $t('importexport.export_products') ?>
          </button>
          <button class="btn btn-outline-success btn-sm" onclick="quickExport('customers', 'csv')">
            <i class="ti ti-users"></i> <?= $t('importexport.export_customers') ?>
          </button>
          <button class="btn btn-outline-info btn-sm" onclick="quickExport('invoices', 'pdf')">
            <i class="ti ti-file-invoice"></i> <?= $t('importexport.export_invoices') ?>
          </button>
          <button class="btn btn-outline-warning btn-sm" onclick="quickExport('all', 'csv')">
            <i class="ti ti-database"></i> <?= $t('importexport.export_all') ?>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function resetForm() {
    document.getElementById('exportForm').reset();
    document.getElementById('formatCSV').checked = true;
}

function quickExport(module, format) {
    document.getElementById('module').value = module;
    document.querySelector(`input[name="format"][value="${format}"]`).checked = true;
    document.getElementById('exportForm').submit();
}

// Form validation
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const module = document.getElementById('module').value;
    const format = document.querySelector('input[name="format"]:checked').value;
    
    if (!module) {
        e.preventDefault();
        alert('<?= $t('importexport.please_select_module') ?>');
        return;
    }
    
    if (!format) {
        e.preventDefault();
        alert('<?= $t('importexport.please_select_format') ?>');
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="ti ti-loader"></i> <?= $t('importexport.exporting') ?>...';
    submitBtn.disabled = true;
    
    // Re-enable after 5 seconds (in case of error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});
</script>
