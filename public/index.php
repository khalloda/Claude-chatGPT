<?php declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';

use App\Core\Router;

$router = new Router();

// home + health
$router->get('/', 'homecontroller@index');
$router->get('/health', function () {
    $status = 'ok';
    $details = [
        'timestamp' => date('c'),
    ];

    // Session backend and health
    try {
        $sm = \App\Services\SessionManager::getInstance();
        // Ensure initialized so handler exists
        if (!$sm->initialize()) {
            $status = 'degraded';
        }
        $stats = $sm->getStats();
        $sessionHealth = $sm->healthCheck();
        $redisTarget = $stats['redis_stats']['config'] ?? null;
        $details['session'] = [
            'driver' => $stats['driver'] ?? 'unknown',
            'initialized' => $stats['initialized'] ?? false,
            'handler_healthy' => ($sessionHealth['status'] ?? '') === 'healthy',
            'health' => $sessionHealth,
            'redis_target' => $redisTarget,
        ];
        if (($sessionHealth['status'] ?? '') !== 'healthy') { $status = 'degraded'; }
    } catch (\Throwable $e) {
        $status = 'degraded';
        $details['session_error'] = $e->getMessage();
    }

    // DB connectivity and latency
    $dbOk = false; $dbLatency = null; $dbErr = null;
    try {
        $start = microtime(true);
        $pdo = \App\Core\DB::conn();
        $pdo->query('SELECT 1');
        $dbLatency = (microtime(true) - $start) * 1000.0;
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbErr = $e->getMessage();
        $status = 'degraded';
    }
    $details['database'] = [
        'ok' => $dbOk,
        'latency_ms' => $dbLatency,
        'error' => $dbErr,
    ];

    // Redis connectivity (via session handler health when driver is redis)
    // Already included in session health above. Expose a short view.
    $details['redis'] = [
        'enabled' => ($details['session']['driver'] ?? '') === 'redis',
        'ok' => isset($details['session']['health']['status']) && $details['session']['health']['status'] === 'healthy',
        'performance_ms' => $details['session']['health']['performance_test'] ?? null,
        'errors' => $details['session']['health']['errors'] ?? [],
    ];

    http_response_code($status === 'ok' ? 200 : 207); // 207 Multi-Status for degraded
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'details' => $details], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
});

// CSRF token refresh endpoint
$router->get('/csrf-refresh', function () {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    \App\Core\refresh_csrf_if_needed();
    $newToken = \App\Core\regenerate_csrf_token();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['token' => $newToken], JSON_UNESCAPED_UNICODE);
});

// auth
$router->get('/login', 'authcontroller@loginform');
$router->post('/login', 'authcontroller@login');
$router->post('/logout', 'authcontroller@logout');

// user profile
$router->get('/profile', 'usercontroller@profile');
$router->post('/profile/password', 'usercontroller@changepassword');

// categories
$router->get('/categories', 'categoriescontroller@index');
$router->get('/categories/create', 'categoriescontroller@create');
$router->post('/categories', 'categoriescontroller@store');
$router->get('/categories/edit', 'categoriescontroller@edit');
$router->post('/categories/update', 'categoriescontroller@update');
$router->post('/categories/delete', 'categoriescontroller@destroy');

// makes
$router->get('/makes', 'makescontroller@index');
$router->get('/makes/create', 'makescontroller@create');
$router->post('/makes', 'makescontroller@store');
$router->get('/makes/edit', 'makescontroller@edit');
$router->post('/makes/update', 'makescontroller@update');
$router->post('/makes/delete', 'makescontroller@destroy');

// models
$router->get('/models', 'modelscontroller@index');
$router->get('/models/create', 'modelscontroller@create');
$router->post('/models', 'modelscontroller@store');
$router->get('/models/edit', 'modelscontroller@edit');
$router->post('/models/update', 'modelscontroller@update');
$router->post('/models/delete', 'modelscontroller@destroy');

// warehouses
$router->get('/warehouses', 'warehousescontroller@index');
$router->get('/warehouses/create', 'warehousescontroller@create');
$router->post('/warehouses', 'warehousescontroller@store');
$router->get('/warehouses/edit', 'warehousescontroller@edit');
$router->post('/warehouses/update', 'warehousescontroller@update');
$router->post('/warehouses/delete', 'warehousescontroller@destroy');
$router->get('/warehouses/show', 'warehousescontroller@detail');
$router->get('/warehouses/detail', 'warehousescontroller@detail');
$router->get('/warehouses/exportcsv','warehousescontroller@exportcsv');

// products
$router->get('/products', 'productscontroller@index');
$router->get('/products/create', 'productscontroller@create');
$router->post('/products', 'productscontroller@store');
$router->get('/products/edit', 'productscontroller@edit');
$router->post('/products/update', 'productscontroller@update');
$router->post('/products/delete', 'productscontroller@destroy');
$router->get('/products/stock', 'productscontroller@stock');
$router->post('/products/stock', 'productscontroller@savestock');

// customers
$router->get('/customers', 'customerscontroller@index');
$router->get('/customers/create', 'customerscontroller@create');
$router->post('/customers', 'customerscontroller@store');
$router->get('/customers/edit', 'customerscontroller@edit');
$router->post('/customers/update', 'customerscontroller@update');
$router->post('/customers/delete', 'customerscontroller@destroy');
$router->get('/customers/show', 'customerscontroller@show'); 
$router->get('/customers/statement', 'customerscontroller@statement');

// quotes
$router->get('/quotes', 'quotescontroller@index');
$router->get('/quotes/create', 'quotescontroller@create');
$router->post('/quotes', 'quotescontroller@store');
$router->get('/quotes/show', 'quotescontroller@show');
$router->post('/quotes/cancel', 'quotescontroller@cancel');
$router->post('/quotes/markexpired', 'quotescontroller@markexpired');
$router->post('/quotes/createorder', 'quotescontroller@createorder');
$router->post('/quotes/marksent', 'quotescontroller@marksent');
$router->post('/quotes/delete', 'quotescontroller@destroy');

// orders
$router->get('/orders', 'orderscontroller@index');
$router->get('/orders/show', 'orderscontroller@show');

// notes
$router->post('/notes', 'notescontroller@store');
$router->post('/notes/update', 'notescontroller@update');
$router->post('/notes/delete', 'notescontroller@destroy');

// printables
$router->get('/quotes/print', 'quotescontroller@printpage');
$router->get('/orders/print', 'orderscontroller@printpage');

// invoices
$router->get('/invoices', 'invoicescontroller@index');
$router->get('/invoices/show', 'invoicescontroller@show');
$router->get('/invoices/print', 'invoicescontroller@printpage');
$router->post('/invoices/create-from-order', 'invoicescontroller@createfromorder');
$router->post('/invoices/addpayment', 'invoicescontroller@addpayment');
$router->post('/invoices/deletepayment', 'invoicescontroller@deletepayment');

// payments (invoice)
$router->get('/payments', 'paymentscontroller@index');
$router->get('/payments/create', 'paymentscontroller@create');
$router->post('/payments', 'paymentscontroller@store');
$router->post('/payments/delete', 'paymentscontroller@destroy');

// suppliers
$router->get('/suppliers', 'supplierscontroller@index');
$router->get('/suppliers/create', 'supplierscontroller@create');
$router->post('/suppliers', 'supplierscontroller@store');
$router->get('/suppliers/edit', 'supplierscontroller@edit');
$router->post('/suppliers/update', 'supplierscontroller@update');
$router->post('/suppliers/delete', 'supplierscontroller@destroy');
$router->get('/suppliers/show', 'supplierscontroller@show'); 
$router->get('/suppliers/statement', 'supplierscontroller@statement');

// purchase orders
$router->get('/purchaseorders', 'purchaseorderscontroller@index');
$router->get('/purchaseorders/create', 'purchaseorderscontroller@create');
$router->post('/purchaseorders', 'purchaseorderscontroller@store');
$router->get('/purchaseorders/edit', 'purchaseorderscontroller@edit');
$router->post('/purchaseorders/update', 'purchaseorderscontroller@update');
$router->get('/purchaseorders/show', 'purchaseorderscontroller@show');
$router->post('/purchaseorders/mark-ordered', 'purchaseorderscontroller@markordered');
$router->get('/purchaseorders/print', 'purchaseorderscontroller@printpage');
$router->post('/purchaseorders/close', 'purchaseorderscontroller@markclosed');
$router->post('/purchaseorders/delete', 'purchaseorderscontroller@destroy');

// purchase invoices
$router->get('/purchaseinvoices', 'purchaseinvoicescontroller@index');
$router->get('/purchaseinvoices/show', 'purchaseinvoicescontroller@show');
$router->get('/purchaseinvoices/print', 'purchaseinvoicescontroller@printpage');
$router->post('/purchaseinvoices/create-from-po', 'purchaseinvoicescontroller@createfrompo');
$router->post('/purchaseinvoices/receive', 'purchaseinvoicescontroller@receive');

// receipts (from purchase invoices)
$router->get('/goodsreceipts', 'receiptscontroller@index');
$router->post('/receipts', function () {
    // Preserve method for compatibility and log redirect for observability
    \App\Core\Logger::info('Redirecting legacy /receipts to /purchaseinvoices/receive', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
        'uri' => $_SERVER['REQUEST_URI'] ?? '/receipts',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    header('Location: /purchaseinvoices/receive', true, 308);
});

// stock availability (for live UI)
$router->get('/stock/available', 'stockcontroller@available');
$router->post('/receipts/delete', 'receiptscontroller@destroy');
$router->get('/receipts/print', 'receiptscontroller@printgrn');

// supplier payments (AP)
$router->get('/supplierpayments', 'supplierpaymentscontroller@index');
$router->post('/supplierpayments', 'supplierpaymentscontroller@store');
$router->post('/supplierpayments/delete', 'supplierpaymentscontroller@destroy');

// sales returns (credit notes)
$router->post('/salesreturns', 'salesreturnscontroller@store');
$router->get('/salesreturns/print', 'salesreturnscontroller@printnote');

// purchase returns (debit notes)
$router->get('/purchasereturns', 'purchasereturnscontroller@index');
$router->post('/purchasereturns', 'purchasereturnscontroller@store');
$router->get('/purchasereturns/print', 'purchasereturnscontroller@printnote');

// reports
$router->get('/reports/ap-aging', 'reportscontroller@apaging');
$router->get('/reports/ar-aging', 'reportscontroller@araging');
$router->get('/reports/inventory-valuation', 'reportscontroller@inventoryvaluation');

// stock transfers
$router->get('/transfers', 'transferscontroller@index');
$router->get('/transfers/create', 'transferscontroller@create');
$router->post('/transfers', 'transferscontroller@store');
$router->get('/transfers/show', 'transferscontroller@show');
$router->get('/transfers/print', 'transferscontroller@printnote');

// stock adjustments
$router->get('/adjustments', 'adjustmentscontroller@index');
$router->get('/adjustments/create', 'adjustmentscontroller@create');
$router->post('/adjustments', 'adjustmentscontroller@store');
$router->get('/adjustments/show', 'adjustmentscontroller@show');
$router->get('/adjustments/print', 'adjustmentscontroller@printnote');

// Track request performance
$requestStart = microtime(true);

try {
    $router->dispatch();
    $responseCode = http_response_code() ?: 200;
} catch (\Throwable $e) {
    // Let ErrorHandler handle the exception
    $responseCode = 500;
    throw $e;
} finally {
    // Log the request
    $requestDuration = microtime(true) - $requestStart;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    \App\Core\Logger::request($method, $uri, $responseCode, $requestDuration);
}
