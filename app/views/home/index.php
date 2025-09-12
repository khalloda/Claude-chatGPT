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
  <!-- Left: Trends / Activity placeholder -->
  <div class="col-12 col-xl-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <strong><?= $t('dashboard.activity') ?></strong>
        <div class="text-muted small"><?= $t('dashboard.last_30_days') ?></div>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2">
          <?= $t('dashboard.charts_placeholder') ?>
        </p>
        <div class="empty">
          <i class="ti ti-chart-bar"></i>
          <div class="mt-2"><?= $t('dashboard.connect_metrics') ?></div>
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
