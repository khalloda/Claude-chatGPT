<?php
declare(strict_types=1);

// Usage: php scripts/scan_env_get.php

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$total = 0;
$hits = [];
$numericDefaults = [];

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $lines = @file($path);
    if ($lines === false) { continue; }
    foreach ($lines as $i => $line) {
        if (strpos($line, 'Env::get(') !== false) {
            $total++;
            $trim = rtrim($line, "\r\n");
            $hits[] = $path . ':' . ($i+1) . ': ' . $trim;

            // Detect numeric default parameter (unquoted number)
            if (preg_match('/Env::get\s*\(\s*[^,]+,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/', $line)) {
                $numericDefaults[] = $path . ':' . ($i+1) . ': ' . $trim;
            }
        }
    }
}

echo "Total Env::get occurrences: {$total}\n\n";
echo "All occurrences:\n";
foreach ($hits as $h) {
    echo $h, "\n";
}

echo "\nPotential numeric defaults (need string cast):\n";
if (empty($numericDefaults)) {
    echo "<none>\n";
} else {
    foreach ($numericDefaults as $h) echo $h, "\n";
}

