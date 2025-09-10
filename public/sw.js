/**
 * Service Worker for Frontend Performance Optimization
 * 
 * Provides intelligent caching, resource optimization, and offline functionality.
 */

const CACHE_NAME = 'spare-parts-v1.0.0';
const STATIC_CACHE = 'static-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-v1.0.0';
const IMAGE_CACHE = 'images-v1.0.0';

// Resources to cache immediately
const STATIC_ASSETS = [
    '/',
    '/assets/css/tokens.css',
    '/assets/css/system.css',
    '/assets/css/tablekit.css',
    '/assets/js/app.min.js',
    '/assets/js/performance.js',
    '/assets/images/favicon.ico'
];

// Cache strategies for different resource types
const CACHE_STRATEGIES = {
    static: 'cache-first',
    api: 'network-first',
    images: 'cache-first',
    documents: 'network-first'
};

// Cache durations
const CACHE_DURATIONS = {
    static: 30 * 24 * 60 * 60 * 1000, // 30 days
    dynamic: 24 * 60 * 60 * 1000,     // 1 day
    images: 7 * 24 * 60 * 60 * 1000   // 7 days
};

/**
 * Service Worker Installation
 */
self.addEventListener('install', event => {
    console.log('Service Worker: Installing');
    
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache => {
            console.log('Service Worker: Caching static assets');
            return cache.addAll(STATIC_ASSETS);
        }).then(() => {
            console.log('Service Worker: Static assets cached');
            return self.skipWaiting();
        }).catch(error => {
            console.error('Service Worker: Installation failed', error);
        })
    );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // Delete old caches
                    if (cacheName !== STATIC_CACHE && 
                        cacheName !== DYNAMIC_CACHE && 
                        cacheName !== IMAGE_CACHE) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker: Activated');
            return self.clients.claim();
        })
    );
});

/**
 * Fetch Event Handler with Intelligent Caching
 */
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests and chrome-extension requests
    if (request.method !== 'GET' || url.protocol === 'chrome-extension:') {
        return;
    }
    
    // Determine resource type and caching strategy
    const resourceType = getResourceType(url);
    const strategy = CACHE_STRATEGIES[resourceType] || 'network-first';
    
    event.respondWith(
        handleFetchWithStrategy(request, strategy, resourceType)
    );
});

/**
 * Determine resource type from URL
 */
function getResourceType(url) {
    const pathname = url.pathname;
    
    // Static assets
    if (pathname.includes('/assets/') || pathname.includes('/cache/assets/')) {
        if (pathname.match(/\.(css|js)$/)) return 'static';
        if (pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) return 'images';
    }
    
    // API endpoints
    if (pathname.includes('/api/') || pathname.includes('/ajax/')) {
        return 'api';
    }
    
    // Images
    if (pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) {
        return 'images';
    }
    
    // HTML documents
    return 'documents';
}

/**
 * Handle fetch with specific caching strategy
 */
async function handleFetchWithStrategy(request, strategy, resourceType) {
    const cacheName = getCacheNameForResource(resourceType);
    
    switch (strategy) {
        case 'cache-first':
            return cacheFirst(request, cacheName, resourceType);
        case 'network-first':
            return networkFirst(request, cacheName, resourceType);
        case 'stale-while-revalidate':
            return staleWhileRevalidate(request, cacheName, resourceType);
        default:
            return fetch(request);
    }
}

/**
 * Cache First Strategy
 */
async function cacheFirst(request, cacheName, resourceType) {
    try {
        const cache = await caches.open(cacheName);
        const cached = await cache.match(request);
        
        if (cached) {
            // Check if cached resource is still fresh
            if (isCacheFresh(cached, resourceType)) {
                return cached;
            }
            
            // If stale, update in background
            updateCacheInBackground(request, cache);
            return cached;
        }
        
        // Not in cache, fetch from network
        const response = await fetch(request);
        if (response.ok) {
            await cache.put(request, response.clone());
        }
        return response;
        
    } catch (error) {
        console.warn('Service Worker: Cache first failed', error);
        return fetch(request);
    }
}

/**
 * Network First Strategy
 */
async function networkFirst(request, cacheName, resourceType) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            const cache = await caches.open(cacheName);
            await cache.put(request, response.clone());
        }
        return response;
        
    } catch (error) {
        console.warn('Service Worker: Network failed, trying cache', error);
        
        const cache = await caches.open(cacheName);
        const cached = await cache.match(request);
        
        if (cached) {
            return cached;
        }
        
        // Return offline fallback for documents
        if (resourceType === 'documents') {
            return createOfflineFallback();
        }
        
        throw error;
    }
}

/**
 * Stale While Revalidate Strategy
 */
async function staleWhileRevalidate(request, cacheName, resourceType) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    // Start network request (don't await)
    const networkPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => null);
    
    // Return cached version immediately if available
    if (cached) {
        // Network request continues in background
        networkPromise.catch(() => {});
        return cached;
    }
    
    // No cached version, wait for network
    return networkPromise;
}

/**
 * Update cache in background
 */
function updateCacheInBackground(request, cache) {
    fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
    }).catch(() => {
        // Ignore background update failures
    });
}

/**
 * Check if cached resource is fresh
 */
function isCacheFresh(cachedResponse, resourceType) {
    const dateHeader = cachedResponse.headers.get('date');
    if (!dateHeader) return false;
    
    const cacheDate = new Date(dateHeader);
    const now = new Date();
    const age = now.getTime() - cacheDate.getTime();
    
    const maxAge = CACHE_DURATIONS[resourceType] || CACHE_DURATIONS.dynamic;
    return age < maxAge;
}

/**
 * Get appropriate cache name for resource type
 */
function getCacheNameForResource(resourceType) {
    switch (resourceType) {
        case 'static':
            return STATIC_CACHE;
        case 'images':
            return IMAGE_CACHE;
        default:
            return DYNAMIC_CACHE;
    }
}

/**
 * Create offline fallback response
 */
function createOfflineFallback() {
    const offlineHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Offline - MI Spare Parts</title>
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <style>
                body {
                    font-family: system-ui, sans-serif;
                    text-align: center;
                    padding: 2rem;
                    background: #f7f7f9;
                }
                .offline-container {
                    max-width: 400px;
                    margin: 0 auto;
                    background: white;
                    padding: 2rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .offline-icon {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }
                .btn {
                    background: #027381;
                    color: white;
                    padding: 0.5rem 1rem;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 1rem;
                }
            </style>
        </head>
        <body>
            <div class="offline-container">
                <div class="offline-icon">📱</div>
                <h1>You're Offline</h1>
                <p>This page isn't available offline. Please check your internet connection and try again.</p>
                <button class="btn" onclick="window.location.reload()">Try Again</button>
            </div>
        </body>
        </html>
    `;
    
    return new Response(offlineHTML, {
        headers: {
            'Content-Type': 'text/html; charset=utf-8',
            'Cache-Control': 'no-cache'
        }
    });
}

/**
 * Background Sync for offline actions
 */
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

/**
 * Perform background sync operations
 */
async function doBackgroundSync() {
    try {
        // Sync any cached offline actions
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedRequests = await cache.keys();
        
        for (const request of cachedRequests) {
            if (request.url.includes('/offline-action/')) {
                try {
                    await fetch(request);
                    await cache.delete(request);
                } catch (error) {
                    console.warn('Background sync failed for:', request.url);
                }
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

/**
 * Push notification handling
 */
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'You have a new notification',
        icon: '/assets/images/favicon.ico',
        badge: '/assets/images/badge.png',
        tag: 'spare-parts-notification',
        requireInteraction: true
    };
    
    event.waitUntil(
        self.registration.showNotification('MI Spare Parts', options)
    );
});

/**
 * Notification click handling
 */
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/')
    );
});

/**
 * Message handling for cache management
 */
self.addEventListener('message', event => {
    if (event.data && event.data.type) {
        switch (event.data.type) {
            case 'SKIP_WAITING':
                self.skipWaiting();
                break;
            case 'CLEAR_CACHE':
                clearAllCaches().then(() => {
                    event.ports[0].postMessage({ success: true });
                });
                break;
            case 'GET_CACHE_SIZE':
                getCacheSize().then(size => {
                    event.ports[0].postMessage({ size });
                });
                break;
        }
    }
});

/**
 * Clear all caches
 */
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    return Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

/**
 * Get total cache size
 */
async function getCacheSize() {
    let totalSize = 0;
    const cacheNames = await caches.keys();
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        
        for (const request of requests) {
            const response = await cache.match(request);
            if (response) {
                const blob = await response.blob();
                totalSize += blob.size;
            }
        }
    }
    
    return totalSize;
}

console.log('Service Worker: Loaded and ready');