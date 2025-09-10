<?php declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * Image Optimizer Service
 * 
 * Comprehensive image optimization including lazy loading, responsive images,
 * WebP conversion, and performance optimization.
 */
class ImageOptimizer
{
    private static ?ImageOptimizer $instance = null;
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
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/images';
        
        $this->config = [
            'enable_webp' => true,
            'enable_lazy_loading' => true,
            'quality_jpeg' => 85,
            'quality_webp' => 80,
            'responsive_breakpoints' => [480, 768, 1024, 1200, 1920],
            'lazy_loading_threshold' => 50, // pixels from viewport
            'placeholder_blur' => true,
            'cache_ttl' => 86400 * 30 // 30 days
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
     * Generate responsive image HTML with lazy loading
     */
    public function generateResponsiveImage(string $src, array $options = []): string
    {
        $defaults = [
            'alt' => '',
            'class' => 'img-responsive',
            'width' => null,
            'height' => null,
            'lazy' => $this->config['enable_lazy_loading'],
            'responsive' => true,
            'webp' => $this->config['enable_webp'],
            'quality' => null,
            'placeholder' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Get image info
        $imageInfo = $this->getImageInfo($src);
        if (!$imageInfo) {
            return $this->generateFallbackImage($src, $options);
        }
        
        // Generate responsive versions if enabled
        if ($options['responsive']) {
            return $this->generatePictureElement($src, $imageInfo, $options);
        }
        
        // Generate single optimized image
        return $this->generateOptimizedImage($src, $imageInfo, $options);
    }
    
    /**
     * Generate picture element with multiple sources
     */
    private function generatePictureElement(string $src, array $imageInfo, array $options): string
    {
        $sources = [];
        $webpSources = [];
        
        // Generate responsive versions
        foreach ($this->config['responsive_breakpoints'] as $breakpoint) {
            if ($breakpoint < $imageInfo['width']) {
                $responsiveOptions = array_merge($options, ['width' => $breakpoint]);
                
                // Generate WebP version
                if ($options['webp']) {
                    $webpSrc = $this->optimizeImage($src, array_merge($responsiveOptions, ['format' => 'webp']));
                    $webpSources[] = [
                        'srcset' => $webpSrc,
                        'media' => "(max-width: {$breakpoint}px)",
                        'type' => 'image/webp'
                    ];
                }
                
                // Generate original format version
                $optimizedSrc = $this->optimizeImage($src, $responsiveOptions);
                $sources[] = [
                    'srcset' => $optimizedSrc,
                    'media' => "(max-width: {$breakpoint}px)",
                    'type' => $imageInfo['mime']
                ];
            }
        }
        
        // Generate full-size versions
        if ($options['webp']) {
            $webpSrc = $this->optimizeImage($src, array_merge($options, ['format' => 'webp']));
            $webpSources[] = [
                'srcset' => $webpSrc,
                'type' => 'image/webp'
            ];
        }
        
        $optimizedSrc = $this->optimizeImage($src, $options);
        
        // Build picture element
        $html = '<picture class="responsive-image">';
        
        // Add WebP sources first
        foreach ($webpSources as $source) {
            $media = isset($source['media']) ? " media=\"{$source['media']}\"" : '';
            $html .= "<source srcset=\"{$source['srcset']}\" type=\"{$source['type']}\"{$media}>";
        }
        
        // Add original format sources
        foreach ($sources as $source) {
            $media = isset($source['media']) ? " media=\"{$source['media']}\"" : '';
            $html .= "<source srcset=\"{$source['srcset']}\" type=\"{$source['type']}\"{$media}>";
        }
        
        // Add fallback img element
        $imgAttributes = $this->buildImageAttributes($optimizedSrc, $imageInfo, $options);
        $html .= "<img {$imgAttributes}>";
        
        $html .= '</picture>';
        
        return $html;
    }
    
    /**
     * Generate single optimized image
     */
    private function generateOptimizedImage(string $src, array $imageInfo, array $options): string
    {
        $optimizedSrc = $this->optimizeImage($src, $options);
        $imgAttributes = $this->buildImageAttributes($optimizedSrc, $imageInfo, $options);
        
        return "<img {$imgAttributes}>";
    }
    
    /**
     * Build image attributes string
     */
    private function buildImageAttributes(string $src, array $imageInfo, array $options): string
    {
        $attributes = [];
        
        // Lazy loading attributes
        if ($options['lazy']) {
            $attributes['loading'] = 'lazy';
            $attributes['decoding'] = 'async';
            
            // Generate placeholder if enabled
            if ($options['placeholder']) {
                $placeholder = $this->generatePlaceholder($imageInfo, $options);
                $attributes['data-src'] = $src;
                $attributes['src'] = $placeholder;
                $attributes['class'] = ($options['class'] ?? '') . ' lazy-image';
            } else {
                $attributes['src'] = $src;
            }
        } else {
            $attributes['src'] = $src;
        }
        
        // Standard attributes
        if (!empty($options['alt'])) {
            $attributes['alt'] = htmlspecialchars($options['alt'], ENT_QUOTES, 'UTF-8');
        }
        
        if (!empty($options['class'])) {
            $attributes['class'] = $options['class'];
        }
        
        if ($options['width']) {
            $attributes['width'] = $options['width'];
        }
        
        if ($options['height']) {
            $attributes['height'] = $options['height'];
        }
        
        // Build attribute string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }
        
        return trim($attrString);
    }
    
    /**
     * Optimize single image
     */
    private function optimizeImage(string $src, array $options = []): string
    {
        $originalPath = $this->resolveImagePath($src);
        if (!file_exists($originalPath)) {
            return $src; // Return original if file doesn't exist
        }
        
        // Generate cache key
        $cacheKey = md5($src . serialize($options));
        $format = $options['format'] ?? $this->getImageFormat($originalPath);
        $extension = $format === 'webp' ? '.webp' : $this->getImageExtension($originalPath);
        $cachedPath = $this->cacheDir . '/' . $cacheKey . $extension;
        
        // Check if cached version exists and is fresh
        if (file_exists($cachedPath) && filemtime($cachedPath) > filemtime($originalPath)) {
            return '/cache/images/' . basename($cachedPath);
        }
        
        // Load and process image
        $image = $this->loadImage($originalPath);
        if (!$image) {
            return $src;
        }
        
        // Resize if needed
        if (isset($options['width']) || isset($options['height'])) {
            $image = $this->resizeImage($image, $options);
        }
        
        // Save optimized image
        $success = $this->saveImage($image, $cachedPath, $format, $options);
        imagedestroy($image);
        
        if ($success) {
            return '/cache/images/' . basename($cachedPath);
        }
        
        return $src; // Return original if optimization failed
    }
    
    /**
     * Generate image placeholder for lazy loading
     */
    private function generatePlaceholder(array $imageInfo, array $options): string
    {
        $width = $options['width'] ?? min($imageInfo['width'], 100);
        $height = $options['height'] ?? min($imageInfo['height'], 100);
        
        if ($options['placeholder'] === 'blur') {
            // Generate blurred thumbnail
            return $this->generateBlurredPlaceholder($imageInfo, $width, $height);
        }
        
        // Generate solid color placeholder
        $color = $this->extractDominantColor($imageInfo) ?? '225,225,225';
        return "data:image/svg+xml,%3csvg width='{$width}' height='{$height}' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='rgb({$color})'/%3e%3c/svg%3e";
    }
    
    /**
     * Generate blurred placeholder
     */
    private function generateBlurredPlaceholder(array $imageInfo, int $width, int $height): string
    {
        // Create small blurred version for placeholder
        $cacheKey = 'blur_' . md5($imageInfo['path'] . "{$width}x{$height}");
        $cachedPath = $this->cacheDir . '/' . $cacheKey . '.jpg';
        
        if (!file_exists($cachedPath)) {
            $image = $this->loadImage($imageInfo['path']);
            if ($image) {
                // Resize to very small size
                $blurred = imagecreatetruecolor(20, 20);
                imagecopyresampled($blurred, $image, 0, 0, 0, 0, 20, 20, $imageInfo['width'], $imageInfo['height']);
                
                // Apply blur
                for ($i = 0; $i < 5; $i++) {
                    imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
                }
                
                imagejpeg($blurred, $cachedPath, 50);
                imagedestroy($image);
                imagedestroy($blurred);
            }
        }
        
        if (file_exists($cachedPath)) {
            $imageData = base64_encode(file_get_contents($cachedPath));
            return "data:image/jpeg;base64,{$imageData}";
        }
        
        return $this->generatePlaceholder($imageInfo, ['placeholder' => false]);
    }
    
    /**
     * Extract dominant color from image
     */
    private function extractDominantColor(array $imageInfo): ?string
    {
        $image = $this->loadImage($imageInfo['path']);
        if (!$image) {
            return null;
        }
        
        // Create small version for color analysis
        $small = imagecreatetruecolor(1, 1);
        imagecopyresampled($small, $image, 0, 0, 0, 0, 1, 1, $imageInfo['width'], $imageInfo['height']);
        
        $rgb = imagecolorat($small, 0, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        imagedestroy($image);
        imagedestroy($small);
        
        return "{$r},{$g},{$b}";
    }
    
    /**
     * Get image information
     */
    private function getImageInfo(string $src): ?array
    {
        $path = $this->resolveImagePath($src);
        
        if (!file_exists($path)) {
            return null;
        }
        
        $info = getimagesize($path);
        if (!$info) {
            return null;
        }
        
        return [
            'path' => $path,
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'mime' => $info['mime'],
            'size' => filesize($path)
        ];
    }
    
    /**
     * Resolve image path
     */
    private function resolveImagePath(string $src): string
    {
        // Remove leading slash and domain
        $src = ltrim(parse_url($src, PHP_URL_PATH), '/');
        
        return $this->publicDir . '/' . $src;
    }
    
    /**
     * Load image from file
     */
    private function loadImage(string $path)
    {
        $info = getimagesize($path);
        if (!$info) {
            return false;
        }
        
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Resize image
     */
    private function resizeImage($image, array $options)
    {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        $targetWidth = $options['width'] ?? $originalWidth;
        $targetHeight = $options['height'] ?? $originalHeight;
        
        // Calculate dimensions maintaining aspect ratio
        if (isset($options['width']) && !isset($options['height'])) {
            $targetHeight = (int) (($originalHeight * $targetWidth) / $originalWidth);
        } elseif (isset($options['height']) && !isset($options['width'])) {
            $targetWidth = (int) (($originalWidth * $targetHeight) / $originalHeight);
        }
        
        // Create resized image
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preserve transparency for PNG/GIF
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
        
        return $resized;
    }
    
    /**
     * Save optimized image
     */
    private function saveImage($image, string $path, string $format, array $options): bool
    {
        $quality = $options['quality'] ?? ($format === 'webp' ? $this->config['quality_webp'] : $this->config['quality_jpeg']);
        
        switch ($format) {
            case 'webp':
                return imagewebp($image, $path, $quality);
            case 'jpeg':
            case 'jpg':
                return imagejpeg($image, $path, $quality);
            case 'png':
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = (int) ((100 - $quality) / 10);
                return imagepng($image, $path, $pngQuality);
            case 'gif':
                return imagegif($image, $path);
            default:
                return false;
        }
    }
    
    /**
     * Get image format
     */
    private function getImageFormat(string $path): string
    {
        $info = getimagesize($path);
        if (!$info) {
            return 'jpg';
        }
        
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            default:
                return 'jpg';
        }
    }
    
    /**
     * Get image file extension
     */
    private function getImageExtension(string $path): string
    {
        return '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
    
    /**
     * Generate fallback image HTML
     */
    private function generateFallbackImage(string $src, array $options): string
    {
        $attributes = [];
        $attributes['src'] = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        
        if (!empty($options['alt'])) {
            $attributes['alt'] = htmlspecialchars($options['alt'], ENT_QUOTES, 'UTF-8');
        }
        
        if (!empty($options['class'])) {
            $attributes['class'] = $options['class'];
        }
        
        if ($options['width']) {
            $attributes['width'] = $options['width'];
        }
        
        if ($options['height']) {
            $attributes['height'] = $options['height'];
        }
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }
        
        return "<img{$attrString}>";
    }
    
    /**
     * Generate lazy loading JavaScript
     */
    public function getLazyLoadingScript(): string
    {
        return <<<'JS'
// Lazy Loading Implementation
(function() {
    'use strict';
    
    if (!window.IntersectionObserver) {
        // Fallback for older browsers - load all images immediately
        document.querySelectorAll('.lazy-image').forEach(function(img) {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.classList.remove('lazy-image');
            }
        });
        return;
    }
    
    const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-image');
                    img.classList.add('lazy-loaded');
                    observer.unobserve(img);
                }
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });
    
    function startLazyLoading() {
        document.querySelectorAll('.lazy-image').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startLazyLoading);
    } else {
        startLazyLoading();
    }
    
    // Re-initialize for dynamically added images
    window.initLazyLoading = startLazyLoading;
})();
JS;
    }
    
    /**
     * Clear image cache
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
}