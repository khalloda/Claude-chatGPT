/**
 * Frontend Performance Enhancement Module
 * 
 * Comprehensive performance optimization including resource loading,
 * critical resource prioritization, and performance monitoring.
 */
(function(window, document) {
    'use strict';
    
    // Performance monitoring
    const Performance = {
        marks: new Map(),
        timings: new Map(),
        
        mark(name) {
            this.marks.set(name, performance.now());
            if (performance.mark) {
                performance.mark(name);
            }
        },
        
        measure(name, startMark, endMark = null) {
            const start = this.marks.get(startMark);
            const end = endMark ? this.marks.get(endMark) : performance.now();
            
            if (start !== undefined) {
                const duration = end - start;
                this.timings.set(name, duration);
                
                if (performance.measure && performance.mark) {
                    try {
                        performance.measure(name, startMark, endMark || undefined);
                    } catch(e) {
                        // Ignore if marks don't exist in performance API
                    }
                }
                
                return duration;
            }
            return null;
        },
        
        getTimings() {
            return Object.fromEntries(this.timings);
        },
        
        clearTimings() {
            this.marks.clear();
            this.timings.clear();
            if (performance.clearMarks) {
                performance.clearMarks();
            }
            if (performance.clearMeasures) {
                performance.clearMeasures();
            }
        },
        
        report() {
            const report = {
                timings: this.getTimings(),
                navigation: this.getNavigationTimings(),
                resources: this.getResourceTimings(),
                vitals: this.getWebVitals()
            };
            
            console.group('Performance Report');
            console.table(report.timings);
            console.log('Navigation:', report.navigation);
            console.log('Web Vitals:', report.vitals);
            console.groupEnd();
            
            return report;
        },
        
        getNavigationTimings() {
            if (!performance.getEntriesByType) return {};
            
            const navigation = performance.getEntriesByType('navigation')[0];
            if (!navigation) return {};
            
            return {
                dns: navigation.domainLookupEnd - navigation.domainLookupStart,
                tcp: navigation.connectEnd - navigation.connectStart,
                ssl: navigation.secureConnectionStart > 0 ? navigation.connectEnd - navigation.secureConnectionStart : 0,
                ttfb: navigation.responseStart - navigation.requestStart,
                download: navigation.responseEnd - navigation.responseStart,
                domParse: navigation.domContentLoadedEventStart - navigation.responseEnd,
                domReady: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                load: navigation.loadEventEnd - navigation.loadEventStart,
                total: navigation.loadEventEnd - navigation.navigationStart
            };
        },
        
        getResourceTimings() {
            if (!performance.getEntriesByType) return [];
            
            return performance.getEntriesByType('resource').map(entry => ({
                name: entry.name,
                duration: entry.duration,
                size: entry.transferSize || 0,
                type: this.getResourceType(entry.name)
            }));
        },
        
        getResourceType(url) {
            if (url.includes('.css')) return 'css';
            if (url.includes('.js')) return 'javascript';
            if (url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) return 'image';
            if (url.match(/\.(woff|woff2|ttf|otf)$/i)) return 'font';
            return 'other';
        },
        
        getWebVitals() {
            const vitals = {};
            
            // Largest Contentful Paint
            if (performance.getEntriesByType) {
                const lcpEntries = performance.getEntriesByType('largest-contentful-paint');
                if (lcpEntries.length > 0) {
                    vitals.lcp = lcpEntries[lcpEntries.length - 1].startTime;
                }
                
                // First Input Delay would need PerformanceObserver
                // Cumulative Layout Shift would need PerformanceObserver
            }
            
            // First Contentful Paint
            if (performance.getEntriesByName) {
                const fcpEntries = performance.getEntriesByName('first-contentful-paint');
                if (fcpEntries.length > 0) {
                    vitals.fcp = fcpEntries[0].startTime;
                }
            }
            
            return vitals;
        }
    };
    
    // Resource Loading Optimization
    const ResourceLoader = {
        preloadedResources: new Set(),
        
        preloadResource(href, as, type = null, crossorigin = true) {
            if (this.preloadedResources.has(href)) {
                return Promise.resolve();
            }
            
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.href = href;
                link.as = as;
                
                if (type) link.type = type;
                if (crossorigin) link.crossOrigin = 'anonymous';
                
                link.onload = () => {
                    this.preloadedResources.add(href);
                    resolve();
                };
                link.onerror = () => reject(new Error(`Failed to preload: ${href}`));
                
                document.head.appendChild(link);
            });
        },
        
        loadScript(src, options = {}) {
            return new Promise((resolve, reject) => {
                // Check if already loaded
                if (document.querySelector(`script[src="${src}"]`)) {
                    resolve();
                    return;
                }
                
                const script = document.createElement('script');
                script.src = src;
                script.async = options.async !== false;
                script.defer = options.defer || false;
                
                if (options.crossorigin) script.crossOrigin = options.crossorigin;
                if (options.integrity) script.integrity = options.integrity;
                
                script.onload = resolve;
                script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
                
                document.head.appendChild(script);
            });
        },
        
        loadStylesheet(href, options = {}) {
            return new Promise((resolve, reject) => {
                // Check if already loaded
                if (document.querySelector(`link[href="${href}"]`)) {
                    resolve();
                    return;
                }
                
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                
                if (options.media) link.media = options.media;
                if (options.crossorigin) link.crossOrigin = options.crossorigin;
                if (options.integrity) link.integrity = options.integrity;
                
                link.onload = resolve;
                link.onerror = () => reject(new Error(`Failed to load stylesheet: ${href}`));
                
                document.head.appendChild(link);
            });
        },
        
        loadResourcesSequentially(resources) {
            return resources.reduce((promise, resource) => {
                return promise.then(() => {
                    if (resource.type === 'script') {
                        return this.loadScript(resource.src, resource.options);
                    } else if (resource.type === 'stylesheet') {
                        return this.loadStylesheet(resource.href, resource.options);
                    } else if (resource.type === 'preload') {
                        return this.preloadResource(resource.href, resource.as, resource.type, resource.crossorigin);
                    }
                    return Promise.resolve();
                });
            }, Promise.resolve());
        },
        
        loadResourcesConcurrently(resources) {
            const promises = resources.map(resource => {
                if (resource.type === 'script') {
                    return this.loadScript(resource.src, resource.options);
                } else if (resource.type === 'stylesheet') {
                    return this.loadStylesheet(resource.href, resource.options);
                } else if (resource.type === 'preload') {
                    return this.preloadResource(resource.href, resource.as, resource.mimeType, resource.crossorigin);
                }
                return Promise.resolve();
            });
            
            return Promise.all(promises);
        }
    };
    
    // Image Optimization
    const ImageOptimizer = {
        observer: null,
        
        init() {
            if (!window.IntersectionObserver) {
                // Fallback for older browsers
                this.loadAllImages();
                return;
            }
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            this.observeImages();
        },
        
        observeImages() {
            document.querySelectorAll('img[data-src], source[data-srcset]').forEach(element => {
                this.observer.observe(element);
            });
        },
        
        loadImage(element) {
            Performance.mark(`image_load_start_${element.dataset.src || element.dataset.srcset}`);
            
            if (element.dataset.src) {
                element.src = element.dataset.src;
                element.removeAttribute('data-src');
            }
            
            if (element.dataset.srcset) {
                element.srcset = element.dataset.srcset;
                element.removeAttribute('data-srcset');
            }
            
            element.classList.remove('lazy-image');
            element.classList.add('lazy-loaded');
            
            element.onload = () => {
                Performance.mark(`image_load_end_${element.src}`);
                Performance.measure(`image_load_time_${element.src}`, `image_load_start_${element.src}`, `image_load_end_${element.src}`);
            };
        },
        
        loadAllImages() {
            document.querySelectorAll('img[data-src], source[data-srcset]').forEach(element => {
                this.loadImage(element);
            });
        },
        
        reinit() {
            if (this.observer) {
                this.observeImages();
            }
        }
    };
    
    // Critical Resource Loading
    const CriticalLoader = {
        loadCriticalCSS() {
            Performance.mark('critical_css_start');
            
            // Load critical CSS inline to avoid render blocking
            const criticalCSS = document.querySelector('#critical-css');
            if (criticalCSS) {
                Performance.mark('critical_css_end');
                Performance.measure('critical_css_time', 'critical_css_start', 'critical_css_end');
            }
        },
        
        preloadCriticalFonts() {
            Performance.mark('font_preload_start');
            
            const fontPreloads = [
                { href: '/assets/fonts/inter-variable.woff2', type: 'font/woff2' },
                { href: '/assets/fonts/inter-italic.woff2', type: 'font/woff2' }
            ];
            
            const promises = fontPreloads.map(font => 
                ResourceLoader.preloadResource(font.href, 'font', font.type, true)
            );
            
            Promise.all(promises).then(() => {
                Performance.mark('font_preload_end');
                Performance.measure('font_preload_time', 'font_preload_start', 'font_preload_end');
            });
        },
        
        loadNonCriticalResources() {
            Performance.mark('non_critical_start');
            
            // Load non-critical CSS after initial render
            requestIdleCallback(() => {
                const nonCriticalCSS = [
                    '/assets/css/system.css',
                    '/assets/css/tablekit.css'
                ];
                
                const promises = nonCriticalCSS.map(href => 
                    ResourceLoader.loadStylesheet(href)
                );
                
                Promise.all(promises).then(() => {
                    Performance.mark('non_critical_end');
                    Performance.measure('non_critical_time', 'non_critical_start', 'non_critical_end');
                });
            }, { timeout: 1000 });
        }
    };
    
    // Service Worker Registration
    const ServiceWorkerManager = {
        register() {
            if ('serviceWorker' in navigator) {
                Performance.mark('sw_register_start');
                
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(registration => {
                        Performance.mark('sw_register_end');
                        Performance.measure('sw_register_time', 'sw_register_start', 'sw_register_end');
                        
                        console.log('Service Worker registered successfully');
                    })
                    .catch(error => {
                        console.warn('Service Worker registration failed:', error);
                    });
            }
        }
    };
    
    // Main Performance Manager
    const PerformanceManager = {
        init() {
            Performance.mark('performance_init_start');
            
            // Load critical resources immediately
            CriticalLoader.loadCriticalCSS();
            CriticalLoader.preloadCriticalFonts();
            
            // Initialize image optimization
            ImageOptimizer.init();
            
            // Register service worker
            ServiceWorkerManager.register();
            
            // Load non-critical resources after initial render
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    CriticalLoader.loadNonCriticalResources();
                });
            } else {
                CriticalLoader.loadNonCriticalResources();
            }
            
            // Set up performance monitoring
            window.addEventListener('load', () => {
                Performance.mark('performance_init_end');
                Performance.measure('performance_init_time', 'performance_init_start', 'performance_init_end');
                
                // Report performance metrics after a delay
                setTimeout(() => {
                    this.reportPerformanceMetrics();
                }, 1000);
            });
        },
        
        reportPerformanceMetrics() {
            const report = Performance.report();
            
            // Send metrics to analytics (if configured)
            if (window.gtag) {
                window.gtag('event', 'performance_metrics', {
                    custom_map: {
                        custom_parameter_1: 'load_time',
                        custom_parameter_2: 'fcp',
                        custom_parameter_3: 'lcp'
                    },
                    load_time: report.navigation.total,
                    fcp: report.vitals.fcp,
                    lcp: report.vitals.lcp
                });
            }
            
            return report;
        },
        
        reinitializeImages() {
            ImageOptimizer.reinit();
        }
    };
    
    // Export to global scope
    window.PerformanceManager = PerformanceManager;
    window.Performance = Performance;
    window.ResourceLoader = ResourceLoader;
    window.ImageOptimizer = ImageOptimizer;
    
    // Auto-initialize
    PerformanceManager.init();
    
})(window, document);