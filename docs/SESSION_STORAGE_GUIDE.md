# Session Storage Implementation Guide

## Overview

This guide covers the implementation of Redis-based session storage for the spare parts management system. The session system provides enterprise-grade security, performance monitoring, and scalability features.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Installation and Setup](#installation-and-setup)
3. [Configuration](#configuration)
4. [Session Management](#session-management)
5. [Security Features](#security-features)
6. [Monitoring and Performance](#monitoring-and-performance)
7. [Migration Procedures](#migration-procedures)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

## Architecture Overview

### Components

The session storage system consists of several key components:

- **SessionManager**: Centralized session lifecycle management
- **RedisSessionHandler**: PHP SessionHandlerInterface implementation for Redis
- **SessionMonitor**: Performance monitoring and security tracking
- **Migration Tools**: Utilities for migrating from file-based sessions

### Flow Diagram

```
Browser → PHP Application → SessionManager → RedisSessionHandler → Redis Server
                          ↓
                     SessionMonitor (metrics & alerts)
```

### Redis Database Strategy

- **Database 0**: General caching
- **Database 1**: Session storage
- **Database 2**: Queue management

## Installation and Setup

### Prerequisites

- PHP 7.4+ with Redis extension
- Redis server 6.0+
- Proper firewall configuration
- SSL/TLS certificates (recommended for production)

### Quick Setup

1. **Install Redis Extension**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-redis
   
   # CentOS/RHEL
   sudo yum install php-redis
   
   # Or compile from source
   pecl install redis
   ```

2. **Install Redis Server**:
   ```bash
   # Use the automated setup script
   sudo ./scripts/redis_setup.sh
   ```

3. **Configure Environment**:
   ```bash
   # Copy environment variables from setup output
   cp /tmp/redis-env.txt .env
   ```

### Manual Installation

If you prefer manual installation:

1. **Install Redis Server**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get update
   sudo apt-get install redis-server
   
   # Configure Redis
   sudo nano /etc/redis/redis.conf
   ```

2. **Configure Redis for Sessions**:
   ```conf
   # /etc/redis/redis.conf
   bind 127.0.0.1 ::1
   port 6379
   requirepass your_secure_password
   databases 16
   maxmemory 2gb
   maxmemory-policy allkeys-lru
   ```

3. **Start Redis Service**:
   ```bash
   sudo systemctl enable redis
   sudo systemctl start redis
   ```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=7200
SESSION_COOKIE=spare_parts_session
SESSION_SECURE=auto
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Lax
SESSION_ENCRYPT=false

# Redis Session Configuration
REDIS_SESSION_HOST=127.0.0.1
REDIS_SESSION_PORT=6379
REDIS_SESSION_PASSWORD=your_redis_password
REDIS_SESSION_DATABASE=1
REDIS_SESSION_PREFIX=sess:
REDIS_SESSION_TIMEOUT=5.0
REDIS_SESSION_PERSISTENT=true

# Session Monitoring
SESSION_MONITORING_ENABLED=true
SESSION_MONITORING_LOG_LEVEL=info
SESSION_ALERT_MEMORY_THRESHOLD=0.8
SESSION_ALERT_SLOW_THRESHOLD=1.0
SESSION_ALERT_ERROR_RATE=0.1
SESSION_ALERT_CONCURRENT_LIMIT=1000
```

### Redis Configuration

The system automatically loads Redis configuration from `config/redis.php`:

```php
return [
    'session' => [
        'host' => env('REDIS_SESSION_HOST', '127.0.0.1'),
        'port' => env('REDIS_SESSION_PORT', 6379),
        'password' => env('REDIS_SESSION_PASSWORD'),
        'database' => env('REDIS_SESSION_DATABASE', 1),
        'prefix' => env('REDIS_SESSION_PREFIX', 'sess:'),
        'timeout' => env('REDIS_SESSION_TIMEOUT', 5.0),
        'persistent' => env('REDIS_SESSION_PERSISTENT', true),
        'serializer' => Redis::SERIALIZER_PHP,
    ],
    'ttl' => [
        'user_sessions' => 7200, // 2 hours
    ]
];
```

## Session Management

### Basic Usage

The session system is automatically initialized in `bootstrap.php`:

```php
use App\Services\SessionManager;

$sessionManager = SessionManager::getInstance();
$sessionManager->initialize();
```

### Manual Session Operations

```php
// Get session manager instance
$sessionManager = SessionManager::getInstance();

// Regenerate session ID
$sessionManager->regenerateId();

// Destroy session
$sessionManager->destroySession();

// Get session statistics
$stats = $sessionManager->getStats();

// Get active sessions (admin only)
$activeSessions = $sessionManager->getActiveSessions();

// Perform health check
$health = $sessionManager->healthCheck();
```

### Session Data Access

```php
// Standard PHP session usage
$_SESSION['user_id'] = 123;
$_SESSION['username'] = 'john_doe';

// Read session data
$userId = $_SESSION['user_id'] ?? null;

// CSRF protection
echo csrf_field(); // Generates CSRF token input
$isValid = verify_csrf_request(); // Validates CSRF token
```

## Security Features

### Built-in Security

1. **Session Hijack Prevention**:
   - User agent validation
   - IP address tracking
   - Session ID regeneration

2. **CSRF Protection**:
   ```php
   // Generate CSRF token
   $token = csrf_token();
   
   // Validate CSRF token
   if (verify_csrf_request()) {
       // Process request
   }
   ```

3. **Secure Cookie Configuration**:
   - HTTPOnly flag
   - Secure flag (HTTPS)
   - SameSite protection

4. **Session Validation**:
   - Automatic expiration
   - Integrity checking
   - Invalid session detection

### Security Monitoring

```php
use App\Services\SessionMonitor;

$monitor = SessionMonitor::getInstance();

// Record security events
$monitor->recordSessionEvent('session_hijack_attempt', [
    'user_id' => $userId,
    'ip_address' => $ipAddress
]);

// Get security report
$securityReport = $monitor->getSecurityReport();
```

## Monitoring and Performance

### Performance Metrics

The system automatically tracks:

- Session operation performance
- Memory usage
- Error rates
- Active session count
- Redis health metrics

### Accessing Metrics

```php
use App\Services\SessionMonitor;

$monitor = SessionMonitor::getInstance();

// Get current metrics
$metrics = $monitor->getMetrics();

// Get performance report
$performanceReport = $monitor->getPerformanceReport();

// Export metrics (JSON, Prometheus, CSV)
$jsonMetrics = $monitor->exportMetrics('json');
$prometheusMetrics = $monitor->exportMetrics('prometheus');
```

### Alerting

The system generates alerts for:

- High memory usage
- Slow operations
- High error rates
- Security events
- Redis connectivity issues

### Performance Tuning

1. **Redis Optimization**:
   ```conf
   # Redis configuration optimizations
   maxmemory-policy allkeys-lru
   lazyfree-lazy-eviction yes
   tcp-keepalive 300
   ```

2. **PHP Optimization**:
   ```ini
   ; php.ini optimizations
   session.lazy_write = On
   session.cache_limiter = nocache
   ```

3. **Connection Pooling**:
   - Use persistent connections
   - Configure appropriate timeouts
   - Monitor connection counts

## Migration Procedures

### From File-based Sessions

Use the migration utility to transfer existing sessions:

```bash
# Test migration (dry run)
php scripts/session_migrate.php --dry-run

# Perform migration with backup
php scripts/session_migrate.php

# Migrate without backup
php scripts/session_migrate.php --no-backup

# Clean up old session files
php scripts/session_migrate.php --cleanup=86400
```

### Migration Process

1. **Pre-migration Checklist**:
   - [ ] Redis server is running and accessible
   - [ ] Redis authentication is configured
   - [ ] Backup of existing session files
   - [ ] Application maintenance mode (recommended)

2. **Migration Steps**:
   ```bash
   # 1. Test Redis connectivity
   redis-cli -a 'password' ping
   
   # 2. Run migration dry run
   php scripts/session_migrate.php --dry-run
   
   # 3. Perform actual migration
   php scripts/session_migrate.php
   
   # 4. Verify migration
   php tests/session_test.php
   
   # 5. Clean up old files (after verification)
   php scripts/session_migrate.php --cleanup=86400
   ```

3. **Rollback Procedure**:
   ```bash
   # If migration fails, restore from backup
   cp /tmp/session_backup_*/sess_* /path/to/session/directory/
   
   # Revert to file-based sessions
   export SESSION_DRIVER=file
   ```

### Zero-downtime Migration

For production environments:

1. Configure load balancer for gradual traffic shift
2. Migrate sessions in batches
3. Monitor session metrics during migration
4. Implement fallback mechanisms

## Testing

### Automated Testing

Run the comprehensive test suite:

```bash
# Run all session tests
php tests/session_test.php

# Run with verbose output
php tests/session_test.php --verbose

# Run specific test groups
php tests/session_test.php --group=redis
php tests/session_test.php --group=security
```

### Manual Testing

1. **Basic Functionality**:
   ```bash
   # Test session creation
   curl -c cookies.txt http://localhost/login
   
   # Test session persistence
   curl -b cookies.txt http://localhost/dashboard
   ```

2. **Redis Connectivity**:
   ```bash
   # Test Redis connection
   redis-cli -a 'password' ping
   
   # Check session data in Redis
   redis-cli -a 'password' -n 1 keys "sess:*"
   ```

3. **Performance Testing**:
   ```bash
   # Use Apache Bench for load testing
   ab -n 1000 -c 10 http://localhost/
   
   # Monitor Redis performance
   redis-cli -a 'password' --latency-history
   ```

### Health Checks

```php
// Application health check
$sessionManager = SessionManager::getInstance();
$health = $sessionManager->healthCheck();

if ($health['status'] !== 'healthy') {
    // Handle unhealthy state
    error_log('Session system unhealthy: ' . json_encode($health));
}
```

## Troubleshooting

### Common Issues

1. **Redis Connection Failed**:
   ```
   Error: Redis connection failed
   
   Solutions:
   - Check Redis server status: systemctl status redis
   - Verify connection parameters in .env
   - Check firewall rules
   - Validate Redis authentication
   ```

2. **Session Data Not Persisting**:
   ```
   Error: Session data lost between requests
   
   Solutions:
   - Verify Redis database selection
   - Check session cookie configuration
   - Validate session serialization
   - Review Redis memory policy
   ```

3. **High Memory Usage**:
   ```
   Warning: Redis memory usage high
   
   Solutions:
   - Implement session cleanup
   - Adjust TTL settings
   - Configure memory limits
   - Monitor session sizes
   ```

4. **Slow Session Operations**:
   ```
   Warning: Session operations slow
   
   Solutions:
   - Check Redis server performance
   - Optimize network latency
   - Review session data size
   - Configure connection pooling
   ```

### Debugging

1. **Enable Debug Logging**:
   ```env
   SESSION_MONITORING_LOG_LEVEL=debug
   APP_DEBUG=true
   ```

2. **Monitor Redis Operations**:
   ```bash
   # Monitor Redis commands
   redis-cli -a 'password' monitor
   
   # Check Redis slow log
   redis-cli -a 'password' slowlog get 10
   ```

3. **Session Debugging**:
   ```php
   // Debug session data
   var_dump($_SESSION);
   
   // Debug session configuration
   var_dump(session_get_cookie_params());
   
   // Debug Redis handler
   $stats = $sessionManager->getStats();
   var_dump($stats['redis_stats']);
   ```

### Log Analysis

Important log entries to monitor:

```
# Successful session operations
[INFO] Session system initialized successfully
[INFO] Redis session connection established

# Performance warnings
[WARNING] Slow session operation detected
[WARNING] High memory usage detected

# Security alerts
[WARNING] Session hijack attempt detected
[ERROR] Invalid session detected

# System errors
[ERROR] Redis connection failed
[ERROR] Session migration failed
```

## Best Practices

### Security Best Practices

1. **Strong Authentication**:
   - Use strong Redis passwords
   - Enable Redis AUTH
   - Restrict network access

2. **Data Protection**:
   - Use HTTPS in production
   - Enable session encryption if needed
   - Implement proper session cleanup

3. **Monitoring**:
   - Monitor for security events
   - Set up alerting for anomalies
   - Regular security audits

### Performance Best Practices

1. **Configuration Optimization**:
   - Use persistent connections
   - Configure appropriate timeouts
   - Optimize Redis memory settings

2. **Data Management**:
   - Minimize session data size
   - Implement session cleanup
   - Monitor memory usage

3. **Scaling Considerations**:
   - Plan for growth
   - Implement Redis clustering
   - Consider session sharding

### Operational Best Practices

1. **Backup and Recovery**:
   - Regular Redis backups
   - Test recovery procedures
   - Document rollback plans

2. **Monitoring and Alerting**:
   - Set up health checks
   - Monitor key metrics
   - Configure appropriate alerts

3. **Maintenance**:
   - Regular security updates
   - Performance monitoring
   - Capacity planning

## API Reference

### SessionManager

```php
class SessionManager
{
    // Initialize session system
    public function initialize(): bool
    
    // Destroy current session
    public function destroySession(): bool
    
    // Regenerate session ID
    public function regenerateId(bool $deleteOldSession = true): bool
    
    // Get session statistics
    public function getStats(): array
    
    // Get active sessions
    public function getActiveSessions(): array
    
    // Perform health check
    public function healthCheck(): array
    
    // Migrate sessions from file storage
    public function migrateSessions(string $sessionPath = null): array
}
```

### SessionMonitor

```php
class SessionMonitor
{
    // Start operation timing
    public function startOperation(string $operation): string
    
    // End operation timing
    public function endOperation(string $operationId, bool $success = true, array $metadata = []): void
    
    // Record session event
    public function recordSessionEvent(string $event, array $data = []): void
    
    // Get current metrics
    public function getMetrics(): array
    
    // Get performance report
    public function getPerformanceReport(): array
    
    // Get security report
    public function getSecurityReport(): array
    
    // Export metrics
    public function exportMetrics(string $format = 'json'): string
}
```

### Helper Functions

```php
// CSRF protection
function csrf_token(): string
function csrf_field(): string
function verify_csrf_request(): bool

// Session utilities
function auth_user(): ?array
function auth_check(): bool
```

## Support and Resources

### Documentation
- [Redis Documentation](https://redis.io/documentation)
- [PHP Session Handling](https://www.php.net/manual/en/book.session.php)
- [Security Guidelines](security-guidelines.md)

### Monitoring Tools
- Redis monitoring: `redis-cli --stat`
- Application metrics: `php tests/session_test.php`
- Performance analysis: Built-in monitoring system

### Community Resources
- Redis community forums
- PHP session handling discussions
- Security best practices guides

---

**Last Updated**: 2024-01-XX  
**Version**: 1.0.0  
**Maintainer**: Development Team