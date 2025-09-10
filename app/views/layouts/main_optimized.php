<?php /** @var string $content */ ?>
<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
use function App\Core\auth_check;
use function App\Core\auth_user;
use function App\Core\flash_get;

// Performance optimization setup
use App\Services\AssetOptimizer;
use App\Services\ImageOptimizer;

$assetOptimizer = AssetOptimizer::getInstance();
$imageOptimizer = ImageOptimizer::getInstance();

// Locale & direction (fallback to session → 'en')
$locale = $_SESSION['locale'] ?? 'en';
$is_ar  = ($locale === 'ar');
$dir    = $is_ar ? 'rtl' : 'ltr';

// Helpers
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$page_title = $page_title ?? (function_exists('t') ? t('app.title') : 'MI Spare Parts');

// Current user (if any)
$user = auth_check() ? (auth_user() ?? []) : null;

// Safe base_url wrapper
$u = function (string $path): string {
  $path = '/' . ltrim($path, '/');
  return function_exists('base_url') ? base_url($path) : $path;
};

// Optimize CSS assets
$cssFiles = [
    'css/tokens.css',
    'css/system.css', 
    'css/tablekit.css'
];
$optimizedCSS = $assetOptimizer->optimizeCSS($cssFiles);

// Optimize JavaScript assets
$jsFiles = [
    'js/app.js',
    'js/tablekit.js'
];
$optimizedJS = $assetOptimizer->optimizeJS($jsFiles);

// Generate critical CSS
$criticalCSS = $assetOptimizer->generateCriticalCSS($u('/'));

// Get preload directives
$preloads = $assetOptimizer->getPreloadHeaders();
?>
<!doctype html>
<html lang="<?= $h($locale) ?>" dir="<?= $dir ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= $h($page_title) ?></title>
  <link rel="icon" href="/assets/images/favicon.ico">

  <!-- Preload critical resources -->
  <?php foreach ($preloads as $preload): ?>
    <link rel="preload" href="<?= $h($preload['href']) ?>" as="<?= $h($preload['as']) ?>"<?= !empty($preload['crossorigin']) ? ' crossorigin="' . $h($preload['crossorigin']) . '"' : '' ?>>
  <?php endforeach; ?>

  <!-- Critical CSS (inline for immediate rendering) -->
  <style id="critical-css">
    <?= $criticalCSS ?>
    
    /* Loading states and performance optimizations */
    .lazy-image {
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
    }
    
    .lazy-loaded {
      opacity: 1;
    }
    
    /* Prevent layout shift for images */
    img[width][height] {
      aspect-ratio: attr(width) / attr(height);
      height: auto;
    }
    
    /* Performance-optimized loading states */
    .loading-skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, transparent 37%, transparent 63%, #f0f0f0 75%);
      background-size: 400% 100%;
      animation: skeleton 1.4s ease-in-out infinite;
    }
    
    @keyframes skeleton {
      0% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    /* Toast performance optimization */
    .toast {
      will-change: transform;
    }
  </style>

  <!-- Bootstrap CSS (async loading for non-critical) -->
  <?php if ($is_ar): ?>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet"></noscript>
  <?php else: ?>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
  <?php endif; ?>
  
  <!-- Icons (async loading) -->
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"></noscript>

  <!-- Optimized application CSS (non-critical, loaded after initial render) -->
  <link rel="preload" href="<?= $h($optimizedCSS['url']) ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link href="<?= $h($optimizedCSS['url']) ?>" rel="stylesheet"></noscript>

  <!-- Page-specific CSS hook (optional) -->
  <?= $extra_css ?? '' ?>

  <!-- Performance monitoring script (inline for immediate execution) -->
  <script>
    // Performance timing marks
    performance.mark('head_start');
    
    // CSRF refresh functionality (optimized)
    window.App = window.App || {};
    window.App.refreshCsrfToken = function() {
      return fetch('/csrf-refresh', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(response => {
        if (response.ok) {
          return response.json().then(data => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && data.token) {
              meta.setAttribute('content', data.token);
              return data.token;
            }
          });
        }
      }).catch(error => {
        console.warn('Failed to refresh CSRF token:', error);
        return null;
      });
    };
    
    // Critical performance optimizations
    window.addEventListener('DOMContentLoaded', function() {
      performance.mark('dom_ready');
      
      // Initialize automatic CSRF token refresh for long-running sessions  
      setInterval(function() {
        if (window.App && window.App.refreshCsrfToken) {
          window.App.refreshCsrfToken();
        }
      }, 90 * 60 * 1000); // 90 minutes
      
      // Also refresh when the page becomes visible after being hidden
      document.addEventListener('visibilitychange', function() {
        if (!document.hidden && window.App && window.App.refreshCsrfToken) {
          window.App.refreshCsrfToken();
        }
      });
    });
    
    performance.mark('head_end');
  </script>

  <!-- Preload JavaScript resources -->
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" as="script">
  <link rel="preload" href="<?= $h($optimizedJS['url']) ?>" as="script">

  <!-- Page-specific head scripts hook (optional) -->
  <?= $head_scripts ?? '' ?>
</head>
<body>
  <script>performance.mark('body_start');</script>

  <!-- Top navbar -->
  <header class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $u('/') ?>">
        <?= $imageOptimizer->generateResponsiveImage('/assets/images/logo.png', [
          'alt' => 'logo',
          'width' => 28,
          'height' => 28,
          'class' => 'navbar-logo',
          'lazy' => false  // Logo should load immediately
        ]) ?>
        <span class="fw-semibold"><?= $h(function_exists('t') ? t('app.title') : 'Spare Parts App') ?></span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="topnav" class="collapse navbar-collapse">
        <form class="ms-auto me-3" role="search" method="get" action="<?= $u('/search') ?>">
          <div class="input-group">
            <input class="form-control" type="search" name="q" placeholder="<?= $h(function_exists('t') ? t('table.search_placeholder') : 'Search…') ?>" autocomplete="off">
            <button class="btn btn-outline-secondary" type="submit"><i class="ti ti-search"></i></button>
          </div>
        </form>

        <ul class="navbar-nav align-items-lg-center">
          <li class="nav-item me-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/locale?lang=en') ?>">EN</a>
          </li>
          <li class="nav-item me-3">
            <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/locale?lang=ar') ?>">AR</a>
          </li>

          <?php if ($user): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-user-circle"></i><span><?= $h($user['email'] ?? $user['name'] ?? 'User') ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $u('/profile') ?>"><?= $h('Profile') ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="<?= $u('/logout') ?>" class="px-3 py-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><?= $h('Logout') ?></button>
                  </form>
                </li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="btn btn-sm btn-primary" href="<?= $u('/login') ?>"><?= $h('Login') ?></a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </header>

  <div class="app-shell container-fluid">
    <div class="row g-0">
      <!-- Sidebar (if present) -->
      <aside class="col-12 col-md-3 col-lg-2 border-end bg-white d-print-none">
        <?php
          $sidebar_path = __DIR__ . '/../partials/sidebar.php';
          if (file_exists($sidebar_path)) {
            include $sidebar_path;
          } else {
            // Fallback quick links (old layout) if sidebar.php not added yet
            ?>
            <div class="fallback-container">
              <h6 class="mb-3 text-muted">Quick Links</h6>
              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/') ?>">Home</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/health') ?>">Health</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/quotes') ?>">Quotes</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/salesorders') ?>">Sales Orders</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/invoices') ?>">Invoices</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/purchaseorders') ?>">POs</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/purchaseinvoices') ?>">PIs</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $u('/products') ?>">Products</a>
              </div>
            </div>
            <?php
          }
        ?>
      </aside>

      <!-- Main content -->
      <main class="app-main col-12 col-md-9 col-lg-10">
        <script>performance.mark('main_content_start');</script>
        
        <!-- Flash messages -->
        <?php if ($m = flash_get('success')): ?>
          <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="ti ti-check me-2"></i><div><?= $h($m) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($m = flash_get('error')): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="ti ti-alert-triangle me-2"></i><div><?= $h($m) ?></div>
          </div>
        <?php endif; ?>

        <!-- Page content -->
        <?= $content ?>
        
        <script>performance.mark('main_content_end');</script>
      </main>
    </div>
  </div>

  <!-- Load JavaScript resources asynchronously -->
  <script>
    // Load Bootstrap JS
    function loadScript(src, callback) {
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      if (callback) script.onload = callback;
      script.onerror = function() { console.error('Failed to load:', src); };
      document.head.appendChild(script);
    }
    
    // Load critical scripts in order
    loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', function() {
      // Load optimized application JavaScript after Bootstrap
      loadScript('<?= $h($optimizedJS['url']) ?>', function() {
        // Load performance monitoring
        loadScript('/assets/js/performance.js', function() {
          performance.mark('scripts_loaded');
          
          // Initialize image optimization
          if (window.PerformanceManager) {
            window.PerformanceManager.reinitializeImages();
          }
          
          // Mark script loading complete
          performance.measure('script_load_time', 'body_start', 'scripts_loaded');
        });
      });
    });
  </script>

  <!-- Lazy loading script for images -->
  <script>
    <?= $imageOptimizer->getLazyLoadingScript() ?>
  </script>

  <!-- Page-tail scripts hook (optional) -->
  <?= $body_scripts ?? '' ?>

  <!-- Performance measurement -->
  <script>
    window.addEventListener('load', function() {
      performance.mark('page_fully_loaded');
      performance.measure('total_load_time', 'navigationStart', 'page_fully_loaded');
      
      // Report performance metrics to console in development
      <?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
      setTimeout(function() {
        if (window.Performance) {
          window.Performance.report();
        }
      }, 1000);
      <?php endif; ?>
    });
  </script>
</body>
</html>