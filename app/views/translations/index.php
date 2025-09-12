<?php 
use function App\Core\base_url; 
use function App\Core\csrf_field;
use function App\Core\user_has_permission;
use function App\Core\get_locale;
use function App\Core\is_rtl;
/** @var array $translation_keys, $arabic_translations, $available_locales, $stats */
/** @var string $page_title, $current_locale */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= htmlspecialchars($page_title ?? 'Translation Management', ENT_QUOTES) ?></h2>
  <div class="btn-group">
    <a class="btn btn-outline-secondary" href="<?= base_url('/') ?>">
      <i class="fas fa-arrow-left"></i> Back
    </a>
    <?php if (user_has_permission('settings.manage')): ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#translationStatsModal">
      <i class="fas fa-chart-bar"></i> Translation Stats
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

<!-- Language Switcher -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-globe"></i> Language Settings
    </h5>
  </div>
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h6>Current Language: 
          <span class="badge bg-primary fs-6">
            <?= htmlspecialchars($available_locales[$current_locale] ?? $current_locale, ENT_QUOTES) ?>
            <?php if (is_rtl()): ?>
            <i class="fas fa-align-right ms-1" title="Right-to-Left"></i>
            <?php else: ?>
            <i class="fas fa-align-left ms-1" title="Left-to-Right"></i>
            <?php endif; ?>
          </span>
        </h6>
        <p class="text-muted mb-0">
          <?= is_rtl() ? 'Text direction: Right-to-Left (RTL)' : 'Text direction: Left-to-Right (LTR)' ?>
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <div class="btn-group">
          <?php foreach ($available_locales as $code => $name): ?>
          <a href="<?= base_url('/locale?lang=' . $code) ?>" 
             class="btn <?= $current_locale === $code ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= htmlspecialchars($name, ENT_QUOTES) ?>
            <?php if ($code === 'ar'): ?>
            <i class="fas fa-align-right ms-1"></i>
            <?php else: ?>
            <i class="fas fa-align-left ms-1"></i>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Translation Statistics -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-primary"><?= $stats['total_keys'] ?></h3>
        <p class="card-text">Total Translation Keys</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-success"><?= $stats['translated_keys'] ?></h3>
        <p class="card-text">Translated to Arabic</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-warning"><?= $stats['missing_keys'] ?></h3>
        <p class="card-text">Missing Translations</p>
      </div>
    </div>
  </div>
</div>

<!-- Translation Progress -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-tasks"></i> Translation Progress
    </h5>
  </div>
  <div class="card-body">
    <?php 
    $progressPercentage = $stats['total_keys'] > 0 ? round(($stats['translated_keys'] / $stats['total_keys']) * 100, 1) : 0;
    ?>
    <div class="progress mb-2" style="height: 25px;">
      <div class="progress-bar bg-success" 
           role="progressbar" 
           style="width: <?= $progressPercentage ?>%"
           aria-valuenow="<?= $progressPercentage ?>" 
           aria-valuemin="0" 
           aria-valuemax="100">
        <?= $progressPercentage ?>% Complete
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <small class="text-muted">
          <i class="fas fa-check text-success"></i> 
          <?= $stats['translated_keys'] ?> of <?= $stats['total_keys'] ?> keys translated
        </small>
      </div>
      <div class="col-md-6 text-md-end">
        <small class="text-muted">
          <i class="fas fa-exclamation-triangle text-warning"></i> 
          <?= $stats['missing_keys'] ?> keys need translation
        </small>
      </div>
    </div>
  </div>
</div>

<!-- Translation Keys Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="fas fa-language"></i> Translation Keys
    </h5>
    <div class="input-group" style="width: 300px;">
      <input type="text" class="form-control" id="searchTranslations" placeholder="Search translation keys...">
      <span class="input-group-text">
        <i class="fas fa-search"></i>
      </span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="translationsTable">
        <thead class="table-light">
          <tr>
            <th width="25%">Key</th>
            <th width="35%">English</th>
            <th width="35%">Arabic (العربية)</th>
            <th width="5%">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($translation_keys)): ?>
          <tr>
            <td colspan="4" class="text-center py-4 text-muted">
              <i class="fas fa-language fa-2x mb-2"></i><br>
              No translation keys found.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($translation_keys as $key => $englishValue): ?>
          <tr class="translation-row">
            <td>
              <code class="small text-primary"><?= htmlspecialchars($key, ENT_QUOTES) ?></code>
            </td>
            <td>
              <span class="text-dark"><?= htmlspecialchars($englishValue, ENT_QUOTES) ?></span>
            </td>
            <td class="<?= is_rtl() ? 'text-end' : '' ?>">
              <?php if (isset($arabic_translations[$key]) && !empty($arabic_translations[$key])): ?>
                <span class="text-dark" dir="rtl" lang="ar">
                  <?= htmlspecialchars($arabic_translations[$key], ENT_QUOTES) ?>
                </span>
              <?php else: ?>
                <span class="text-muted fst-italic">Not translated</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if (isset($arabic_translations[$key]) && !empty($arabic_translations[$key])): ?>
                <i class="fas fa-check-circle text-success" title="Translated"></i>
              <?php else: ?>
                <i class="fas fa-exclamation-circle text-warning" title="Missing translation"></i>
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

<!-- Translation Statistics Modal -->
<div class="modal fade" id="translationStatsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-chart-bar"></i> Detailed Translation Statistics
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6>Translation Coverage</h6>
            <canvas id="translationChart" width="300" height="200"></canvas>
          </div>
          <div class="col-md-6">
            <h6>Language Information</h6>
            <table class="table table-sm">
              <tr>
                <td><strong>Default Language:</strong></td>
                <td>English (en)</td>
              </tr>
              <tr>
                <td><strong>Secondary Language:</strong></td>
                <td>Arabic (ar) - RTL</td>
              </tr>
              <tr>
                <td><strong>Total Keys:</strong></td>
                <td><?= $stats['total_keys'] ?></td>
              </tr>
              <tr>
                <td><strong>Completion Rate:</strong></td>
                <td><?= $progressPercentage ?>%</td>
              </tr>
            </table>
            
            <h6 class="mt-3">Missing Translation Categories</h6>
            <div class="list-group list-group-flush">
              <?php 
              $categories = [];
              foreach ($translation_keys as $key => $value) {
                  if (!isset($arabic_translations[$key]) || empty($arabic_translations[$key])) {
                      $category = explode('.', $key)[0];
                      $categories[$category] = ($categories[$category] ?? 0) + 1;
                  }
              }
              arsort($categories);
              ?>
              <?php foreach (array_slice($categories, 0, 5) as $category => $count): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                <span><?= ucfirst(htmlspecialchars($category, ENT_QUOTES)) ?></span>
                <span class="badge bg-warning"><?= $count ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <?php if (user_has_permission('settings.manage')): ?>
        <button type="button" class="btn btn-primary" onclick="exportTranslations()">
          <i class="fas fa-download"></i> Export for Translation
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Search functionality
document.getElementById('searchTranslations').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#translationsTable .translation-row');
    
    rows.forEach(row => {
        const key = row.cells[0].textContent.toLowerCase();
        const english = row.cells[1].textContent.toLowerCase();
        const arabic = row.cells[2].textContent.toLowerCase();
        
        if (key.includes(searchTerm) || english.includes(searchTerm) || arabic.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Translation chart (if Chart.js is available)
function createTranslationChart() {
    const ctx = document.getElementById('translationChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Translated', 'Missing'],
                datasets: [{
                    data: [<?= $stats['translated_keys'] ?>, <?= $stats['missing_keys'] ?>],
                    backgroundColor: ['#28a745', '#ffc107'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Export translations function
function exportTranslations() {
    // This could be implemented to export missing translations for translators
    alert('Export functionality would create a file with missing translations for translators.');
}

// Initialize chart when modal is shown
document.getElementById('translationStatsModal').addEventListener('shown.bs.modal', function() {
    setTimeout(createTranslationChart, 100);
});
</script>