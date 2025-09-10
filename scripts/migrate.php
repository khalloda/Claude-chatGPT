<?php declare(strict_types=1);

/**
 * Lightweight migration runner
 * - Applies SQL files under scripts/migrations in filename order
 * - Tracks applied migrations in schema_migrations table with checksum
 * - Usage:
 *   php scripts/migrate.php            # apply pending
 *   php scripts/migrate.php --list     # list migrations
 *   php scripts/migrate.php --dry-run  # print what would run
 */

require __DIR__ . '/../app/core/bootstrap.php';

use App\Core\DB;

function println(string $msg): void { echo $msg, PHP_EOL; }

$args = $argv;
array_shift($args);
$dryRun = in_array('--dry-run', $args, true);
$list   = in_array('--list', $args, true);

// Attempt DB connection unless explicitly skipping
$pdo = null;
try {
    $pdo = DB::conn();
} catch (Throwable $e) {
    if (!$dryRun) {
        throw $e; // for real runs we need DB
    }
    println('[DRY RUN] Warning: DB connection not available; assuming no migrations applied.');
}

// Read applied if DB available; create ledger only for real run
$applied = [];
if ($pdo) {
    if (!$dryRun) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
          id INT AUTO_INCREMENT PRIMARY KEY,
          filename VARCHAR(255) NOT NULL UNIQUE,
          checksum VARCHAR(64) NOT NULL,
          applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    }
    try {
        foreach ($pdo->query('SELECT filename, checksum FROM schema_migrations') as $row) {
            $applied[$row['filename']] = $row['checksum'];
        }
    } catch (Throwable $e) {
        if (!$dryRun) { throw $e; }
        println('[DRY RUN] Warning: schema_migrations not readable; assuming none applied.');
    }
}

$dir = __DIR__ . '/migrations';
if (!is_dir($dir)) {
    println('No migrations directory found at scripts/migrations. Nothing to do.');
    exit(0);
}

$files = glob($dir . '/*.sql');
sort($files, SORT_STRING);

$pending = [];
foreach ($files as $f) {
    $name = basename($f);
    $sql  = file_get_contents($f) ?: '';
    $sum  = hash('sha256', $sql);
    if (!isset($applied[$name]) || $applied[$name] !== $sum) {
        $pending[] = [$name, $f, $sum];
    }
}

if ($list) {
    println('Migrations:');
    foreach ($files as $f) {
        $name = basename($f);
        $status = isset($applied[$name]) ? 'applied' : 'pending';
        println(" - {$name} [{$status}]");
    }
    exit(0);
}

if (empty($pending)) {
    println('No pending migrations.');
    exit(0);
}

println(($dryRun ? '[DRY RUN] ' : '') . 'Applying ' . count($pending) . ' migration(s)...');

foreach ($pending as [$name, $path, $sum]) {
    println('-> ' . $name);
    if ($dryRun) { continue; }
    $sql = file_get_contents($path) ?: '';
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?) ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), applied_at=CURRENT_TIMESTAMP');
        $ins->execute([$name, $sum]);
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
        println('   applied');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        println('   FAILED: ' . $e->getMessage());
        exit(1);
    }
}

println('Done.');
