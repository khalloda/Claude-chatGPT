<?php
/**
 * File: app/views/home/index.php
 * Dashboard home with KPIs + system status
 * - Safe to render even if $kpi is not provided by the controller.
 * - Preserves your previous diagnostics ($db_ok, $debug, $db_error).
 */

use function App\Core\base_url;

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$U = fn($path) => (function_exists('base_url') ? base_url('/' . ltrim($path, '/')) : '/' . ltrim($path, '/'));
$t = fn($key) => \App\Core\t($key);

// KPI defaults (controller can override by passing ['kpi'=>[...]])
$kpi = array_merge([
  'stock_value'   => 0,
  'out_of_stock'  => 0,
  'low_stock'     => 0,
  'quotes_week'   => 0,
  'orders_week'   => 0,
  'invoices_week' => 0,
  'overdue_ar'    => 0,
  'best_seller'   => ['name' => '—', 'qty' => 0],
], $kpi ?? []);

// Legacy diagnostics defaults (from your old view)
$db_ok    = $db_ok    ?? false;
$debug    = $debug    ?? false;
$db_error = $db_error ?? '';
?>

<div class="page-header">
  <div class="title d-flex align-items-center gap-2">
    <?= $t('nav.dashboard') ?>
    <?php 
      $driver = $session_driver ?? 'unknown';
      $latMs = isset($db_latency_ms) && is_numeric($db_latency_ms) ? round((float)$db_latency_ms) : null;
    ?>
    <span class="chip chip-secondary" title="Session backend">
      <span class="dot"></span>Session: <?= $h($driver) ?>
    </span>
    <?php if ($latMs !== null): ?>
      <span class="chip chip-secondary" title="DB latency (ms)">
        <span class="dot"></span>DB: <?= $h((string)$latMs) ?> ms
      </span>
    <?php endif; ?>
  </div>
  <nav aria-label="breadcrumb" class="ms-auto">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item active" aria-current="page"><?= $t('nav.dashboard') ?></li>
    </ol>
  </nav>
</div>

<!-- KPIs -->
<section class="kpis">
  <div class="kpi">
    <div class="label"><?= $t('dashboard.total_stock_value') ?></div>
    <div class="value"><?= $h(number_format((float)$kpi['stock_value'], 2)) ?></div>
  </div>
  <div class="kpi">
    <div class="label"><?= $t('dashboard.out_of_stock') ?></div>
    <div class="value"><?= $h((int)$kpi['out_of_stock']) ?></div>
  </div>
  <div class="kpi">
    <div class="label"><?= $t('dashboard.low_stock') ?></div>
    <div class="value"><?= $h((int)$kpi['low_stock']) ?></div>
  </div>
  <div class="kpi">
    <div class="label"><?= $t('dashboard.quotes_this_week') ?></div>
    <div class="value"><?= $h((int)$kpi['quotes_week']) ?></div>
  </div>
  <div class="kpi">
    <div class="label"><?= $t('dashboard.orders_this_week') ?></div>
    <div class="value"><?= $h((int)$kpi['orders_week']) ?></div>
  </div>
  <div class="kpi">
    <div class="label"><?= $t('dashboard.invoices_this_week') ?></div>
    <div class="value"><?= $h((int)$kpi['invoices_week']) ?></div>
  </div>
</section>

<div class="row g-3 mt-2">
  <!-- Left: Trends / Activity -->
  <div class="col-12 col-xl-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <strong><?= $t('dashboard.activity') ?></strong>
        <div class="text-muted small"><?= $t('dashboard.last_30_days') ?></div>
      </div>
      <div class="card-body">
        <!-- Sales vs Purchases Chart -->
        <div class="mb-3">
          <h6 class="mb-2"><?= $t('dashboard.sales_vs_purchases') ?></h6>
          <div class="activity-chart-container">
            <canvas id="salesPurchasesChart"></canvas>
          </div>
        </div>
        
        <!-- Top Products -->
        <?php if (!empty($activityData['top_products'])): ?>
        <div class="mb-3">
          <h6 class="mb-2"><?= $t('dashboard.top_products') ?></h6>
          <div class="row">
            <?php foreach (array_slice($activityData['top_products'], 0, 3) as $index => $product): ?>
            <div class="col-12 col-md-4 mb-2">
              <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                <div>
                  <div class="fw-semibold small"><?= $h($product['name']) ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;"><?= $h((int)$product['total_qty']) ?> <?= $t('dashboard.units') ?></div>
                </div>
                <div class="text-end">
                  <div class="fw-semibold text-primary small"><?= $h(number_format((float)$product['total_value'], 2)) ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Today's Activity Summary -->
        <div class="row">
          <div class="col-6 col-md-3">
            <div class="text-center p-1 bg-primary bg-opacity-10 rounded">
              <div class="fw-bold text-primary small"><?= $h($activityData['recent_activity']['quotes_today']) ?></div>
              <div class="text-muted" style="font-size: 0.7rem;"><?= $t('dashboard.quotes_today') ?></div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-1 bg-success bg-opacity-10 rounded">
              <div class="fw-bold text-success small"><?= $h($activityData['recent_activity']['orders_today']) ?></div>
              <div class="text-muted" style="font-size: 0.7rem;"><?= $t('dashboard.orders_today') ?></div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-1 bg-info bg-opacity-10 rounded">
              <div class="fw-bold text-info small"><?= $h($activityData['recent_activity']['invoices_today']) ?></div>
              <div class="text-muted" style="font-size: 0.7rem;"><?= $t('dashboard.invoices_today') ?></div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-1 bg-warning bg-opacity-10 rounded">
              <div class="fw-bold text-warning small"><?= $h($activityData['recent_activity']['purchases_today']) ?></div>
              <div class="text-muted" style="font-size: 0.7rem;"><?= $t('dashboard.purchases_today') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Alerts / Best seller -->
  <div class="col-12 col-xl-4">
    <div class="card mb-3">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="chip chip-primary"><span class="dot"></span><?= $t('dashboard.alerts') ?></span>
        <div>
          <div class="fw-semibold"><?= $t('dashboard.low_stock_items') ?></div>
          <div class="text-muted small"><?= $h((int)$kpi['low_stock']) ?> <?= $t('dashboard.products_below_threshold') ?></div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body d-flex align-items-center gap-3">
        <i class="ti ti-star"></i>
        <div>
          <div class="fw-semibold"><?= $t('dashboard.best_seller_week') ?></div>
          <div class="text-muted small">
            <?= $h($kpi['best_seller']['name']) ?> — <?= $h((int)$kpi['best_seller']['qty']) ?> <?= $t('dashboard.units') ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body d-flex align-items-center gap-3">
        <i class="ti ti-calendar-exclamation"></i>
        <div>
          <div class="fw-semibold"><?= $t('dashboard.overdue_ar') ?></div>
          <div class="text-muted small"><?= $h((int)$kpi['overdue_ar']) ?> <?= $t('dashboard.invoices_overdue') ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- System status (preserves your old checks) -->
<div class="card mt-3">
  <div class="card-header"><strong><?= $t('dashboard.system_status') ?></strong></div>
  <div class="card-body">
    <p class="mb-2"><?= $t('dashboard.system_wired') ?></p>
    <p class="mb-2">
      <?= $t('dashboard.database_connection') ?>
      <span class="<?= $db_ok ? 'text-success' : 'text-danger' ?>"><strong><?= $db_ok ? $t('common.ok') : $t('common.failed') ?></strong></span>
    </p>

    <?php if (!$db_ok && !empty($debug) && !empty($db_error)): ?>
      <pre class="p-2 rounded bg-light border text-danger" style="white-space:pre-wrap"><?= $h($db_error) ?></pre>
    <?php endif; ?>

    <p class="mb-0">
      <?= $t('dashboard.health_check') ?>
      <a href="<?= $U('/health') ?>"><?= $U('/health') ?></a>
    </p>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
#salesPurchasesChart {
    max-height: 200px !important;
}
.activity-chart-container {
    height: 200px;
    position: relative;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales vs Purchases Chart
    const ctx = document.getElementById('salesPurchasesChart').getContext('2d');
    const salesPurchasesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($activityData['dates']) ?>,
            datasets: [{
                label: '<?= $t('dashboard.sales') ?>',
                data: <?= json_encode($activityData['sales']) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            }, {
                label: '<?= $t('dashboard.purchases') ?>',
                data: <?= json_encode($activityData['purchases']) ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD'
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: '<?= $t('dashboard.date') ?>'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: '<?= $t('dashboard.amount') ?>'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD',
                                minimumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>
