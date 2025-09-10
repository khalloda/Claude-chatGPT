<?php declare(strict_types=1);

/**
 * Redis Configuration
 * 
 * Configuration settings for Redis caching service
 */

return [
    'default' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
        'prefix' => $_ENV['REDIS_PREFIX'] ?? 'spare_parts:',
        'timeout' => (float)($_ENV['REDIS_TIMEOUT'] ?? 5.0),
        'retry_interval' => (int)($_ENV['REDIS_RETRY_INTERVAL'] ?? 100),
        'read_timeout' => (float)($_ENV['REDIS_READ_TIMEOUT'] ?? 2.0),
        'persistent' => filter_var($_ENV['REDIS_PERSISTENT'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'serializer' => Redis::SERIALIZER_JSON,
    ],
    
    'session' => [
        'host' => $_ENV['REDIS_SESSION_HOST'] ?? $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_SESSION_PORT'] ?? $_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_SESSION_PASSWORD'] ?? $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int)($_ENV['REDIS_SESSION_DATABASE'] ?? 1),
        'prefix' => $_ENV['REDIS_SESSION_PREFIX'] ?? 'sess:',
        'timeout' => (float)($_ENV['REDIS_SESSION_TIMEOUT'] ?? 5.0),
        'retry_interval' => (int)($_ENV['REDIS_SESSION_RETRY_INTERVAL'] ?? 100),
        'read_timeout' => (float)($_ENV['REDIS_SESSION_READ_TIMEOUT'] ?? 2.0),
        'persistent' => filter_var($_ENV['REDIS_SESSION_PERSISTENT'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'serializer' => Redis::SERIALIZER_PHP,
    ],
    
    'queue' => [
        'host' => $_ENV['REDIS_QUEUE_HOST'] ?? $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_QUEUE_PORT'] ?? $_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_QUEUE_PASSWORD'] ?? $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int)($_ENV['REDIS_QUEUE_DATABASE'] ?? 2),
        'prefix' => $_ENV['REDIS_QUEUE_PREFIX'] ?? 'queue:',
        'timeout' => (float)($_ENV['REDIS_QUEUE_TIMEOUT'] ?? 5.0),
        'retry_interval' => (int)($_ENV['REDIS_QUEUE_RETRY_INTERVAL'] ?? 100),
        'read_timeout' => (float)($_ENV['REDIS_QUEUE_READ_TIMEOUT'] ?? 2.0),
        'persistent' => filter_var($_ENV['REDIS_QUEUE_PERSISTENT'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'serializer' => Redis::SERIALIZER_JSON,
    ],
    
    // TTL settings for different data types
    'ttl' => [
        'reference_data' => (int)($_ENV['REDIS_TTL_REFERENCE'] ?? 3600), // 1 hour
        'query_cache' => (int)($_ENV['REDIS_TTL_QUERY'] ?? 300), // 5 minutes
        'user_sessions' => (int)($_ENV['REDIS_TTL_SESSION'] ?? 7200), // 2 hours
        'product_data' => (int)($_ENV['REDIS_TTL_PRODUCT'] ?? 1800), // 30 minutes
        'customer_aging' => (int)($_ENV['REDIS_TTL_AGING'] ?? 900), // 15 minutes
        'inventory_data' => (int)($_ENV['REDIS_TTL_INVENTORY'] ?? 600), // 10 minutes
        'search_results' => (int)($_ENV['REDIS_TTL_SEARCH'] ?? 180), // 3 minutes
    ],
    
    // Cache key patterns
    'keys' => [
        'reference_data' => 'ref:{type}',
        'query_cache' => 'query:{hash}',
        'product_data' => 'product:{id}',
        'customer_aging' => 'aging:customer:{id}',
        'inventory_stock' => 'stock:product:{product_id}:warehouse:{warehouse_id}',
        'search_results' => 'search:{type}:{hash}',
        'user_permissions' => 'perms:user:{id}',
        'api_rate_limit' => 'rate:api:{ip}:{endpoint}',
    ],
    
    // Performance monitoring settings
    'monitoring' => [
        'enabled' => filter_var($_ENV['REDIS_MONITORING_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'slow_query_threshold' => (float)($_ENV['REDIS_SLOW_QUERY_THRESHOLD'] ?? 0.1), // 100ms
        'memory_warning_threshold' => (int)($_ENV['REDIS_MEMORY_WARNING'] ?? 1073741824), // 1GB
        'connection_pool_size' => (int)($_ENV['REDIS_CONNECTION_POOL_SIZE'] ?? 10),
    ],
    
    // Failover and reliability settings
    'reliability' => [
        'max_retries' => (int)($_ENV['REDIS_MAX_RETRIES'] ?? 3),
        'retry_delay' => (int)($_ENV['REDIS_RETRY_DELAY'] ?? 100), // milliseconds
        'circuit_breaker_threshold' => (int)($_ENV['REDIS_CIRCUIT_BREAKER_THRESHOLD'] ?? 10),
        'circuit_breaker_timeout' => (int)($_ENV['REDIS_CIRCUIT_BREAKER_TIMEOUT'] ?? 60), // seconds
        'fallback_to_file_cache' => filter_var($_ENV['REDIS_FALLBACK_FILE_CACHE'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    ],
    
    // Security settings
    'security' => [
        'require_auth' => filter_var($_ENV['REDIS_REQUIRE_AUTH'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'ssl_enabled' => filter_var($_ENV['REDIS_SSL_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'ssl_cert_file' => $_ENV['REDIS_SSL_CERT_FILE'] ?? null,
        'ssl_key_file' => $_ENV['REDIS_SSL_KEY_FILE'] ?? null,
        'ssl_ca_file' => $_ENV['REDIS_SSL_CA_FILE'] ?? null,
        'allowed_commands' => [
            'GET', 'SET', 'DEL', 'EXISTS', 'EXPIRE', 'TTL', 'PING',
            'MGET', 'MSET', 'INCR', 'INCRBY', 'KEYS', 'FLUSHDB',
            'INFO', 'MULTI', 'EXEC', 'PIPELINE'
        ],
    ],
];