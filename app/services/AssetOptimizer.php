<?php declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * Asset Optimizer Service
 * 
 * Comprehensive frontend performance optimization including asset minification,
 * concatenation, compression, and cache management.
 */
class AssetOptimizer
{
    private static ?AssetOptimizer $instance = null;
    private array $config;
    private string $cacheDir;
    private string $publicDir;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct()
    {
        $this->publicDir = dirname(__DIR__, 2) . '/public';
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/assets';
        
        $this->config = [
            'enable_minification' => true,
            'enable_compression' => true,
            'enable_concatenation' => true,
            'cache_ttl' => 3600, // 1 hour
            'version_assets' => true,
            'inline_critical_css' => true,
            'preload_fonts' => true,
            'optimize_images' => true
        ];
        
        $this->ensureCacheDirectory();
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Optimize CSS assets
     */
    public function optimizeCSS(array $cssFiles, array $options = []): array
    {
        $cacheKey = 'css_' . md5(serialize($cssFiles) . serialize($options));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.css';
        $mapFile = $this->cacheDir . '/' . $cacheKey . '.map';
        
        // Check if cached version exists and is fresh
        if ($this->isCacheFresh($cacheFile, $cssFiles)) {
            return $this->getCachedAssetInfo($cacheKey, 'css');
        }
        
        $combinedCSS = '';
        $sourceMap = [];
        
        foreach ($cssFiles as $cssFile) {
            $fullPath = $this->resolveAssetPath($cssFile);
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                
                // Process CSS content
                $content = $this->processCSSContent($content, $cssFile);
                $combinedCSS .= "/* File: {$cssFile} */\n" . $content . "\n\n";
                
                $sourceMap[] = [
                    'file' => $cssFile,
                    'size' => strlen($content),
                    'original_size' => filesize($fullPath)
                ];
            }
        }
        
        // Minify combined CSS
        if ($this->config['enable_minification']) {
            $originalSize = strlen($combinedCSS);
            $combinedCSS = $this->minifyCSS($combinedCSS);
            $compressedSize = strlen($combinedCSS);
            
            $sourceMap['optimization'] = [
                'original_size' => $originalSize,
                'minified_size' => $compressedSize,
                'savings' => round((($originalSize - $compressedSize) / $originalSize) * 100, 2) . '%'
            ];
        }
        
        // Save optimized CSS
        file_put_contents($cacheFile, $combinedCSS);
        file_put_contents($mapFile, json_encode($sourceMap, JSON_PRETTY_PRINT));
        
        return $this->getCachedAssetInfo($cacheKey, 'css', $sourceMap);
    }
    
    /**
     * Optimize JavaScript assets
     */
    public function optimizeJS(array $jsFiles, array $options = []): array
    {
        $cacheKey = 'js_' . md5(serialize($jsFiles) . serialize($options));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.js';
        $mapFile = $this->cacheDir . '/' . $cacheKey . '.map';
        
        // Check if cached version exists and is fresh
        if ($this->isCacheFresh($cacheFile, $jsFiles)) {
            return $this->getCachedAssetInfo($cacheKey, 'js');
        }
        
        $combinedJS = '';
        $sourceMap = [];
        
        foreach ($jsFiles as $jsFile) {
            $fullPath = $this->resolveAssetPath($jsFile);
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                
                // Process JS content
                $content = $this->processJSContent($content, $jsFile);
                $combinedJS .= "/* File: {$jsFile} */\n" . $content . "\n\n";
                
                $sourceMap[] = [
                    'file' => $jsFile,
                    'size' => strlen($content),
                    'original_size' => filesize($fullPath)
                ];
            }
        }
        
        // Minify combined JavaScript
        if ($this->config['enable_minification']) {
            $originalSize = strlen($combinedJS);
            $combinedJS = $this->minifyJS($combinedJS);
            $compressedSize = strlen($combinedJS);
            
            $sourceMap['optimization'] = [
                'original_size' => $originalSize,
                'minified_size' => $compressedSize,
                'savings' => round((($originalSize - $compressedSize) / $originalSize) * 100, 2) . '%'
            ];
        }
        
        // Save optimized JavaScript
        file_put_contents($cacheFile, $combinedJS);
        file_put_contents($mapFile, json_encode($sourceMap, JSON_PRETTY_PRINT));
        
        return $this->getCachedAssetInfo($cacheKey, 'js', $sourceMap);
    }
    
    /**
     * Process CSS content with optimizations
     */
    private function processCSSContent(string $content, string $filename): string
    {
        // Remove comments (preserve important comments)
        $content = preg_replace('/\/\*(?![!*]).*?\*\//s', '', $content);
        
        // Fix relative URLs
        $content = $this->fixCSSUrls($content, $filename);
        
        // Optimize colors
        $content = $this->optimizeColors($content);
        
        // Remove unnecessary whitespace (basic optimization)
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Process JavaScript content with optimizations
     */
    private function processJSContent(string $content, string $filename): string
    {
        // Remove single-line comments (preserve important comments)
        $content = preg_replace('/\/\/(?![!*]).*$/m', '', $content);
        
        // Remove multi-line comments (preserve important comments)
        $content = preg_replace('/\/\*(?![!*]).*?\*\//s', '', $content);
        
        // Basic whitespace optimization
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Minify CSS content
     */
    private function minifyCSS(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary spaces
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ', '], [';', '{', '{', '}', '}', ':', ','], $css);
        
        // Remove trailing semicolons
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript content
     */
    private function minifyJS(string $js): string
    {
        // Basic JavaScript minification
        // Remove comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = str_replace([' {', '{ ', ' }', '} ', ' (', '( ', ' )', ') ', ' ;', '; ', ' ,', ', '], 
                         ['{', '{', '}', '}', '(', '(', ')', ')', ';', ';', ',', ','], $js);
        
        return trim($js);
    }
    
    /**
     * Fix CSS URLs to be relative to optimized file
     */
    private function fixCSSUrls(string $css, string $originalFile): string
    {
        return preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) use ($originalFile) {
            $url = $matches[1];
            
            // Skip absolute URLs and data URLs
            if (preg_match('/^(https?:|\/\/|data:)/', $url)) {
                return $matches[0];
            }
            
            // Convert relative URLs to be relative to the cache directory
            $originalDir = dirname($originalFile);
            $relativePath = $originalDir . '/' . $url;
            
            return 'url(' . $relativePath . ')';
        }, $css);
    }
    
    /**
     * Optimize color values in CSS
     */
    private function optimizeColors(string $css): string
    {
        // Convert long hex colors to short form
        $css = preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3/', '#$1$2$3', $css);
        
        return $css;
    }
    
    /**
     * Check if cached file is fresh
     */
    private function isCacheFresh(string $cacheFile, array $sourceFiles): bool
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($cacheFile);
        
        foreach ($sourceFiles as $sourceFile) {
            $fullPath = $this->resolveAssetPath($sourceFile);
            if (file_exists($fullPath) && filemtime($fullPath) > $cacheTime) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Resolve asset path
     */
    private function resolveAssetPath(string $assetPath): string
    {
        // Remove leading slash if present
        $assetPath = ltrim($assetPath, '/');
        
        // If path starts with 'assets/', it's relative to public directory
        if (strpos($assetPath, 'assets/') === 0) {
            return $this->publicDir . '/' . $assetPath;
        }
        
        // Otherwise, assume it's relative to public directory
        return $this->publicDir . '/assets/' . $assetPath;
    }
    
    /**
     * Get cached asset information
     */
    private function getCachedAssetInfo(string $cacheKey, string $type, array $sourceMap = null): array
    {
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.' . $type;
        $mapFile = $this->cacheDir . '/' . $cacheKey . '.map';
        
        if ($sourceMap === null && file_exists($mapFile)) {
            $sourceMap = json_decode(file_get_contents($mapFile), true);
        }
        
        $fileSize = file_exists($cacheFile) ? filesize($cacheFile) : 0;
        $version = $this->config['version_assets'] ? md5_file($cacheFile) : time();
        
        return [
            'cache_key' => $cacheKey,
            'file_path' => '/cache/assets/' . $cacheKey . '.' . $type,
            'file_size' => $fileSize,
            'version' => $version,
            'url' => '/cache/assets/' . $cacheKey . '.' . $type . '?v=' . $version,
            'source_map' => $sourceMap,
            'cached_at' => file_exists($cacheFile) ? date('Y-m-d H:i:s', filemtime($cacheFile)) : null
        ];
    }
    
    /**
     * Generate critical CSS for above-the-fold content
     */
    public function generateCriticalCSS(string $url, array $options = []): string
    {
        // This would typically use a headless browser to extract critical CSS
        // For now, return essential styles
        
        $criticalCSS = '
        :root{--color-bg:#f7f7f9;--color-surface:#fff;--color-text:#111;--color-primary:#027381;}
        body{background:var(--color-bg);color:var(--color-text);margin:0;font-family:system-ui,sans-serif;}
        .navbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:.5rem 1rem;}
        .navbar-brand{font-weight:600;color:var(--color-text);}
        .btn-primary{background:var(--color-primary);border:1px solid var(--color-primary);color:#fff;padding:.375rem .75rem;border-radius:.375rem;}
        .alert{padding:.75rem 1rem;border-radius:.375rem;margin:.5rem 0;}
        .alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
        .alert-danger{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}
        .container-fluid{width:100%;padding:0 1rem;}
        .row{display:flex;flex-wrap:wrap;}
        .col-12{flex:0 0 auto;width:100%;}
        @media(min-width:768px){.col-md-3{flex:0 0 auto;width:25%;}.col-md-9{flex:0 0 auto;width:75%;}}
        ';
        
        return $this->minifyCSS($criticalCSS);
    }
    
    /**
     * Preload critical resources
     */
    public function getPreloadHeaders(): array
    {
        $preloads = [];
        
        // Preload critical fonts
        if ($this->config['preload_fonts']) {
            $preloads[] = [
                'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap',
                'as' => 'style',
                'crossorigin' => 'anonymous'
            ];
        }
        
        return $preloads;
    }
    
    /**
     * Get cache headers for static assets
     */
    public function getCacheHeaders(string $assetType): array
    {
        $headers = [
            'css' => [
                'Cache-Control' => 'public, max-age=31536000, immutable', // 1 year
                'Expires' => gmdate('D, d M Y H:i:s T', time() + 31536000),
                'Content-Type' => 'text/css; charset=utf-8'
            ],
            'js' => [
                'Cache-Control' => 'public, max-age=31536000, immutable', // 1 year  
                'Expires' => gmdate('D, d M Y H:i:s T', time() + 31536000),
                'Content-Type' => 'application/javascript; charset=utf-8'
            ],
            'image' => [
                'Cache-Control' => 'public, max-age=2592000', // 30 days
                'Expires' => gmdate('D, d M Y H:i:s T', time() + 2592000),
            ],
            'font' => [
                'Cache-Control' => 'public, max-age=31536000, immutable', // 1 year
                'Expires' => gmdate('D, d M Y H:i:s T', time() + 31536000),
                'Access-Control-Allow-Origin' => '*'
            ]
        ];
        
        return $headers[$assetType] ?? [];
    }
    
    /**
     * Clear asset cache
     */
    public function clearCache(): bool
    {
        try {
            $files = glob($this->cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $files = glob($this->cacheDir . '/*');
        $totalSize = 0;
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }
        
        return [
            'file_count' => $fileCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'cache_directory' => $this->cacheDir
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}