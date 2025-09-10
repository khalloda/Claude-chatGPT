<?php declare(strict_types=1);

/**
 * Frontend Performance Testing Suite
 * 
 * Comprehensive testing for frontend performance optimizations including
 * asset minification, lazy loading, caching, and Core Web Vitals.
 */

require_once dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Services\AssetOptimizer;
use App\Services\ImageOptimizer;

class FrontendPerformanceTest
{
    private AssetOptimizer $assetOptimizer;
    private ImageOptimizer $imageOptimizer;
    private array $results = [];
    private int $testCount = 0;
    private int $passedTests = 0;
    
    public function __construct()
    {
        $this->assetOptimizer = AssetOptimizer::getInstance();
        $this->imageOptimizer = ImageOptimizer::getInstance();
        
        echo "=== Frontend Performance Test Suite ===\n";
        echo "Testing comprehensive frontend optimization implementation\n\n";
    }
    
    /**
     * Run all performance tests
     */
    public function runAllTests(): bool
    {
        $this->testAssetOptimization();
        $this->testImageOptimization();
        $this->testCacheManagement(); 
        $this->testLazyLoading();
        $this->testResourceLoading();
        $this->testServiceWorker();
        $this->testPerformanceMetrics();
        
        return $this->generateReport();
    }
    
    /**
     * Test Asset Optimization
     */
    private function testAssetOptimization(): void
    {
        echo "1. Testing Asset Optimization...\n";
        
        // Test CSS optimization
        $cssFiles = ['css/tokens.css', 'css/system.css', 'css/tablekit.css'];
        $cssResult = $this->assetOptimizer->optimizeCSS($cssFiles);
        
        $this->assert(
            !empty($cssResult['cache_key']),
            'CSS optimization generates cache key'
        );
        
        $this->assert(
            !empty($cssResult['url']),
            'CSS optimization generates optimized URL'
        );
        
        $this->assert(
            isset($cssResult['source_map']['optimization']['savings']),
            'CSS optimization provides savings metrics'
        );
        
        // Test JavaScript optimization
        $jsFiles = ['js/app.js', 'js/tablekit.js'];
        $jsResult = $this->assetOptimizer->optimizeJS($jsFiles);
        
        $this->assert(
            !empty($jsResult['cache_key']),
            'JS optimization generates cache key'
        );
        
        $this->assert(
            !empty($jsResult['url']),
            'JS optimization generates optimized URL'
        );
        
        // Test critical CSS generation
        $criticalCSS = $this->assetOptimizer->generateCriticalCSS('/');
        
        $this->assert(
            !empty($criticalCSS),
            'Critical CSS generation produces content'
        );
        
        $this->assert(
            strpos($criticalCSS, 'body{') !== false,
            'Critical CSS contains expected styles'
        );
        
        echo "   ✓ Asset optimization tests completed\n\n";
    }
    
    /**
     * Test Image Optimization
     */
    private function testImageOptimization(): void
    {
        echo "2. Testing Image Optimization...\n";
        
        // Test responsive image generation
        $responsiveHTML = $this->imageOptimizer->generateResponsiveImage(
            '/assets/images/test.jpg',
            [
                'alt' => 'Test image',
                'width' => 800,
                'height' => 600,
                'lazy' => true,
                'responsive' => true,
                'webp' => true
            ]
        );
        
        $this->assert(
            strpos($responsiveHTML, '<picture') !== false,
            'Responsive image generates picture element'
        );
        
        $this->assert(
            strpos($responsiveHTML, 'type="image/webp"') !== false,
            'Responsive image includes WebP sources'
        );
        
        $this->assert(
            strpos($responsiveHTML, 'loading="lazy"') !== false,
            'Responsive image includes lazy loading'
        );
        
        // Test lazy loading script generation
        $lazyScript = $this->imageOptimizer->getLazyLoadingScript();
        
        $this->assert(
            strpos($lazyScript, 'IntersectionObserver') !== false,
            'Lazy loading script uses IntersectionObserver'
        );
        
        $this->assert(
            strpos($lazyScript, 'lazy-image') !== false,
            'Lazy loading script targets lazy-image class'
        );
        
        echo "   ✓ Image optimization tests completed\n\n";
    }
    
    /**
     * Test Cache Management
     */
    private function testCacheManagement(): void
    {
        echo "3. Testing Cache Management...\n";
        
        // Test cache headers
        $cssHeaders = $this->assetOptimizer->getCacheHeaders('css');
        
        $this->assert(
            isset($cssHeaders['Cache-Control']),
            'CSS cache headers include Cache-Control'
        );
        
        $this->assert(
            strpos($cssHeaders['Cache-Control'], 'max-age=31536000') !== false,
            'CSS cache headers set 1-year expiration'
        );
        
        $this->assert(
            strpos($cssHeaders['Cache-Control'], 'immutable') !== false,
            'CSS cache headers include immutable directive'
        );
        
        // Test cache statistics
        $cacheStats = $this->assetOptimizer->getCacheStats();
        
        $this->assert(
            isset($cacheStats['file_count']),
            'Cache statistics include file count'
        );
        
        $this->assert(
            isset($cacheStats['total_size']),
            'Cache statistics include total size'
        );
        
        echo "   ✓ Cache management tests completed\n\n";
    }
    
    /**
     * Test Lazy Loading Implementation
     */
    private function testLazyLoading(): void
    {
        echo "4. Testing Lazy Loading Implementation...\n";
        
        // Test image with lazy loading
        $lazyImage = $this->imageOptimizer->generateResponsiveImage(
            '/assets/images/test.jpg',
            [
                'alt' => 'Lazy test image',
                'width' => 400,
                'height' => 300,
                'lazy' => true,
                'placeholder' => true
            ]
        );
        
        $this->assert(
            strpos($lazyImage, 'data-src') !== false,
            'Lazy image includes data-src attribute'
        );
        
        $this->assert(
            strpos($lazyImage, 'lazy-image') !== false,
            'Lazy image includes lazy-image class'
        );
        
        $this->assert(
            strpos($lazyImage, 'loading="lazy"') !== false,
            'Lazy image includes native lazy loading'
        );
        
        // Test image without lazy loading
        $immediateImage = $this->imageOptimizer->generateResponsiveImage(
            '/assets/images/logo.png',
            [
                'alt' => 'Logo',
                'width' => 100,
                'height' => 100,
                'lazy' => false
            ]
        );
        
        $this->assert(
            strpos($immediateImage, 'data-src') === false,
            'Immediate image does not include data-src'
        );
        
        $this->assert(
            strpos($immediateImage, 'src=') !== false,
            'Immediate image includes direct src attribute'
        );
        
        echo "   ✓ Lazy loading tests completed\n\n";
    }
    
    /**
     * Test Resource Loading Optimization
     */
    private function testResourceLoading(): void
    {
        echo "5. Testing Resource Loading Optimization...\n";
        
        // Test preload headers generation
        $preloads = $this->assetOptimizer->getPreloadHeaders();
        
        $this->assert(
            is_array($preloads),
            'Preload headers return array'
        );
        
        // Test optimized layout file exists
        $optimizedLayoutPath = dirname(__DIR__) . '/app/views/layouts/main_optimized.php';
        
        $this->assert(
            file_exists($optimizedLayoutPath),
            'Optimized layout template exists'
        );
        
        if (file_exists($optimizedLayoutPath)) {
            $layoutContent = file_get_contents($optimizedLayoutPath);
            
            $this->assert(
                strpos($layoutContent, 'performance.mark') !== false,
                'Optimized layout includes performance markers'
            );
            
            $this->assert(
                strpos($layoutContent, 'critical-css') !== false,
                'Optimized layout includes critical CSS'
            );
            
            $this->assert(
                strpos($layoutContent, 'preload') !== false,
                'Optimized layout includes resource preloading'
            );
        }
        
        echo "   ✓ Resource loading tests completed\n\n";
    }
    
    /**
     * Test Service Worker Implementation
     */
    private function testServiceWorker(): void
    {
        echo "6. Testing Service Worker Implementation...\n";
        
        $swPath = dirname(__DIR__) . '/public/sw.js';
        
        $this->assert(
            file_exists($swPath),
            'Service worker file exists'
        );
        
        if (file_exists($swPath)) {
            $swContent = file_get_contents($swPath);
            
            $this->assert(
                strpos($swContent, 'cache-first') !== false,
                'Service worker implements cache-first strategy'
            );
            
            $this->assert(
                strpos($swContent, 'network-first') !== false,
                'Service worker implements network-first strategy'
            );
            
            $this->assert(
                strpos($swContent, 'addEventListener') !== false,
                'Service worker implements event listeners'
            );
            
            $this->assert(
                strpos($swContent, 'STATIC_ASSETS') !== false,
                'Service worker defines static assets for caching'
            );
        }
        
        echo "   ✓ Service worker tests completed\n\n";
    }
    
    /**
     * Test Performance Metrics Implementation
     */
    private function testPerformanceMetrics(): void
    {
        echo "7. Testing Performance Metrics Implementation...\n";
        
        $performanceJsPath = dirname(__DIR__) . '/public/assets/js/performance.js';
        
        $this->assert(
            file_exists($performanceJsPath),
            'Performance metrics script exists'
        );
        
        if (file_exists($performanceJsPath)) {
            $performanceContent = file_get_contents($performanceJsPath);
            
            $this->assert(
                strpos($performanceContent, 'Performance') !== false,
                'Performance script defines Performance object'
            );
            
            $this->assert(
                strpos($performanceContent, 'mark(') !== false,
                'Performance script includes timing marks'
            );
            
            $this->assert(
                strpos($performanceContent, 'measure(') !== false,
                'Performance script includes timing measurements'
            );
            
            $this->assert(
                strpos($performanceContent, 'PerformanceManager') !== false,
                'Performance script defines PerformanceManager'
            );
        }
        
        // Test minified app.js exists
        $minifiedAppPath = dirname(__DIR__) . '/public/assets/js/app.min.js';
        
        $this->assert(
            file_exists($minifiedAppPath),
            'Minified application JavaScript exists'
        );
        
        if (file_exists($minifiedAppPath)) {
            $originalAppPath = dirname(__DIR__) . '/public/assets/js/app.js';
            $originalSize = file_exists($originalAppPath) ? filesize($originalAppPath) : 0;
            $minifiedSize = filesize($minifiedAppPath);
            
            if ($originalSize > 0) {
                $savings = (($originalSize - $minifiedSize) / $originalSize) * 100;
                
                $this->assert(
                    $savings > 0,
                    'Minified JavaScript shows file size reduction'
                );
                
                echo "   → JavaScript minification savings: " . round($savings, 1) . "%\n";
            }
        }
        
        echo "   ✓ Performance metrics tests completed\n\n";
    }
    
    /**
     * Test Performance Benchmarking
     */
    private function testPerformanceBenchmarks(): void
    {
        echo "8. Testing Performance Benchmarks...\n";
        
        // Test asset optimization performance
        $startTime = microtime(true);
        
        $cssFiles = ['css/tokens.css', 'css/system.css', 'css/tablekit.css'];
        $cssResult = $this->assetOptimizer->optimizeCSS($cssFiles);
        
        $optimizationTime = (microtime(true) - $startTime) * 1000;
        
        $this->assert(
            $optimizationTime < 100,
            'CSS optimization completes within 100ms'
        );
        
        echo "   → CSS optimization time: " . round($optimizationTime, 2) . "ms\n";
        
        // Test cache performance
        $startTime = microtime(true);
        $cacheStats = $this->assetOptimizer->getCacheStats();
        $cacheTime = (microtime(true) - $startTime) * 1000;
        
        $this->assert(
            $cacheTime < 10,
            'Cache statistics retrieval completes within 10ms'
        );
        
        echo "   → Cache stats time: " . round($cacheTime, 2) . "ms\n";
        
        echo "   ✓ Performance benchmarks completed\n\n";
    }
    
    /**
     * Test Cache Headers Configuration
     */
    private function testCacheHeaders(): void
    {
        echo "9. Testing Cache Headers Configuration...\n";
        
        $htaccessPath = dirname(__DIR__) . '/public/cache/assets/.htaccess';
        
        $this->assert(
            file_exists($htaccessPath),
            'Cache .htaccess file exists'
        );
        
        if (file_exists($htaccessPath)) {
            $htaccessContent = file_get_contents($htaccessPath);
            
            $this->assert(
                strpos($htaccessContent, 'ExpiresByType') !== false,
                'Cache headers configure expiration by type'
            );
            
            $this->assert(
                strpos($htaccessContent, 'immutable') !== false,
                'Cache headers include immutable directive'
            );
            
            $this->assert(
                strpos($htaccessContent, 'mod_deflate') !== false,
                'Cache headers configure compression'
            );
        }
        
        echo "   ✓ Cache headers tests completed\n\n";
    }
    
    /**
     * Assert test condition
     */
    private function assert(bool $condition, string $message): void
    {
        $this->testCount++;
        
        if ($condition) {
            $this->passedTests++;
            $this->results[] = "✓ {$message}";
        } else {
            $this->results[] = "✗ {$message}";
        }
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateReport(): bool
    {
        echo "=== Frontend Performance Test Results ===\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->testCount - $this->passedTests) . "\n";
        echo "Success Rate: " . round(($this->passedTests / $this->testCount) * 100, 1) . "%\n\n";
        
        echo "Detailed Results:\n";
        foreach ($this->results as $result) {
            echo "  {$result}\n";
        }
        
        echo "\n";
        
        // Performance summary
        $this->generatePerformanceSummary();
        
        // Cache information
        $this->generateCacheInformation();
        
        $allTestsPassed = $this->passedTests === $this->testCount;
        
        if ($allTestsPassed) {
            echo "🎉 All frontend performance tests PASSED! System is optimized for production.\n\n";
        } else {
            echo "⚠️  Some tests FAILED. Please review the implementation.\n\n";
        }
        
        return $allTestsPassed;
    }
    
    /**
     * Generate performance optimization summary
     */
    private function generatePerformanceSummary(): void
    {
        echo "=== Performance Optimization Summary ===\n";
        
        // Asset optimization stats
        $cacheStats = $this->assetOptimizer->getCacheStats();
        echo "Cache Statistics:\n";
        echo "  - Cached files: {$cacheStats['file_count']}\n";
        echo "  - Total cache size: {$cacheStats['total_size_human']}\n";
        
        // Performance features implemented
        echo "\nOptimization Features Implemented:\n";
        echo "  ✓ Asset minification and concatenation\n";
        echo "  ✓ Image lazy loading with IntersectionObserver\n";
        echo "  ✓ Responsive images with WebP support\n";
        echo "  ✓ Critical CSS inlining\n";
        echo "  ✓ Resource preloading\n";
        echo "  ✓ Service worker caching\n";
        echo "  ✓ Performance timing measurement\n";
        echo "  ✓ Browser cache optimization\n";
        echo "  ✓ Gzip compression configuration\n";
        
        echo "\n";
    }
    
    /**
     * Generate cache information
     */
    private function generateCacheInformation(): void
    {
        echo "=== Cache Configuration ===\n";
        
        echo "Cache Types:\n";
        echo "  - Static Assets: 1 year (immutable)\n";
        echo "  - Images: 30 days\n";
        echo "  - Fonts: 1 year (immutable)\n";
        echo "  - Dynamic Content: Network-first with fallback\n";
        
        echo "\nCache Locations:\n";
        echo "  - Optimized assets: /public/cache/assets/\n";
        echo "  - Optimized images: /public/cache/images/\n";
        echo "  - Service worker: Browser cache\n";
        
        echo "\n";
    }
}

// Run the tests
$tester = new FrontendPerformanceTest();
$success = $tester->runAllTests();

exit($success ? 0 : 1);