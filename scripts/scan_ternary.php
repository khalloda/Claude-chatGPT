<?php
$root = getcwd();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $lines = @file($path);
    if (!$lines) continue;
    foreach ($lines as $i => $line) {
        $l = trim($line);
        if (strpos($l, '?') !== false && substr_count($l, '?') >= 2 && strpos($l, ':') !== false) {
            echo $path, ':', ($i+1), ': ', $l, "\n";
        }
    }
}
?>
