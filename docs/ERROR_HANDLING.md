# Error Handling and Logging Implementation Guide

## Overview

This application implements a comprehensive error handling and logging system designed for enterprise-grade applications. The system provides structured logging, production-safe error display, security-focused monitoring, and robust error recovery mechanisms.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Logging System](#logging-system)
3. [Error Handling System](#error-handling-system)
4. [Production Safety](#production-safety)
5. [Security Considerations](#security-considerations)
6. [Performance Monitoring](#performance-monitoring)
7. [Configuration Guide](#configuration-guide)
8. [Testing and Validation](#testing-and-validation)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Architecture Overview

The error handling and logging system consists of two main components:

### Component Structure
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Application   │───▶│   ErrorHandler   │───▶│   Logger        │
│   (Controllers, │    │   (Exception     │    │   (Structured   │
│    Models, etc.)│    │    Management)   │    │    Logging)     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │   Error Views    │
                       │   (Safe Display) │
                       └──────────────────┘
```

### Key Features
- **PSR-3 Compatible Logging**: Standard logging levels and interfaces
- **Structured JSON Logging**: Machine-readable logs with contextual data
- **Production-Safe Error Display**: No sensitive data exposure
- **Security-Focused Monitoring**: Special handling for security events
- **Performance Tracking**: Built-in performance monitoring
- **Automatic Error Recovery**: Graceful degradation and retry mechanisms

---

## Logging System

### Core Logger Class (`App\Core\Logger`)

The enhanced Logger class provides comprehensive structured logging with multiple severity levels and contextual information.

#### PSR-3 Log Levels

```php
use App\Core\Logger;

// Emergency: System is unusable
Logger::emergency('Database server is down');

// Alert: Action must be taken immediately
Logger::alert('Disk space critically low');

// Critical: Critical conditions
Logger::critical('Payment gateway unavailable');

// Error: Error conditions
Logger::error('Failed to process order', ['order_id' => 12345]);

// Warning: Warning conditions  
Logger::warning('Slow database query detected', ['duration' => 2.5]);

// Notice: Normal but significant condition
Logger::notice('User password changed', ['user_id' => 789]);

// Info: Informational messages
Logger::info('User login successful', ['user_id' => 456]);

// Debug: Debug-level messages
Logger::debug('Cache miss', ['key' => 'user_session_456']);
```

#### Specialized Logging Methods

##### Security Logging
```php
// Log security-related events with additional context
Logger::security('Unauthorized access attempt', [
    'endpoint' => '/admin/users',
    'ip' => '192.168.1.100',
    'user_agent' => 'Suspicious Bot'
]);

// Authentication events
Logger::authentication('Login failed', [
    'email' => 'user@example.com',
    'reason' => 'invalid_password',
    'attempts' => 3
]);
```

##### Performance Logging
```php
// Track operation performance
$startTime = microtime(true);
// ... perform operation ...
$duration = microtime(true) - $startTime;

Logger::performance('Product search', $duration, [
    'search_term' => 'brake pads',
    'results_count' => 45
]);

// Database query logging with automatic performance classification
Logger::database('SELECT * FROM products WHERE category_id = ?', 0.15, [
    'category_id' => 5,
    'result_count' => 23
]);
```

##### HTTP Request Logging
```php
// Automatic request logging (integrated in index.php)
Logger::request('POST', '/api/orders', 201, 0.25);
```

### Log Structure

Each log entry contains structured JSON data:

```json
{
    "timestamp": "2025-09-08T14:30:25+00:00",
    "level": "INFO",
    "message": "User login successful",
    "context": {
        "user_id": 456,
        "email": "user@example.com"
    },
    "system": {
        "php_version": "8.1.0",
        "server_software": "Apache/2.4.41",
        "load_average": [0.5, 0.7, 0.9]
    },
    "request": {
        "method": "POST",
        "uri": "/login",
        "query_string": "",
        "content_type": "application/x-www-form-urlencoded"
    },
    "user": {
        "user": {
            "id": 456,
            "email": "user@example.com",
            "role": "customer"
        },
        "session_id": "abc123def456",
        "ip": "192.168.1.50"
    },
    "memory_usage": 2097152,
    "memory_peak": 3145728
}
```

### Log File Management

#### File Segmentation
- **Error Logs**: `error-YYYY-MM-DD.log` (Emergency, Alert, Critical, Error)
- **Debug Logs**: `debug-YYYY-MM-DD.log` (Debug level only)
- **Application Logs**: `app-YYYY-MM-DD.log` (Warning, Notice, Info)
- **Critical Log**: `critical.log` (Special handling for critical errors)

#### Log Rotation
- **Daily Rotation**: New log files created daily
- **Archive After**: 7 days (files renamed with `.old` extension)
- **Delete After**: 30 days (automatic cleanup)

---

## Error Handling System

### ErrorHandler Class (`App\Core\ErrorHandler`)

Provides comprehensive error handling with production-safe error display and security-focused error management.

#### Initialization
```php
use App\Core\ErrorHandler;

// Automatic initialization in bootstrap.php
ErrorHandler::init();
```

#### Error Types Handled

##### PHP Errors and Exceptions
```php
// Automatically handles:
// - Fatal errors (E_ERROR)
// - Parse errors (E_PARSE) 
// - Warnings (E_WARNING)
// - Notices (E_NOTICE)
// - Uncaught exceptions
// - Shutdown errors
```

##### Application-Specific Errors
```php
// Handle validation errors
ErrorHandler::handleValidationError([
    'email' => ['Email is required'],
    'password' => ['Password must be at least 8 characters']
], ['form' => 'login']);

// Handle security errors
ErrorHandler::handleSecurityError('CSRF token mismatch', [
    'endpoint' => '/admin/users',
    'expected_token' => 'abc123',
    'received_token' => 'def456'
]);

// Handle database errors
try {
    // Database operation
} catch (PDOException $e) {
    ErrorHandler::handleDatabaseError($e, 'SELECT * FROM users WHERE id = ?');
}
```

### Error Display

#### Production Mode
- **Generic Error Messages**: No sensitive information exposed
- **Error IDs**: Unique identifiers for tracking
- **User-Friendly Interface**: Professional error pages
- **Safe Fallbacks**: Graceful degradation when templates fail

#### Development Mode
- **Detailed Error Information**: Full exception details
- **Stack Traces**: Complete call stack with file/line numbers
- **Debug Information**: Variable states and context
- **Interactive Debugging**: Toggle detailed information

### Error Templates

#### Available Templates
- **500.php**: Generic internal server error
- **403.php**: Access denied/security error
- **database.php**: Database connectivity error
- **Generic Fallback**: Inline HTML when templates fail

#### Template Variables
```php
// Available in error templates:
$errorType        // Error category (string)
$errorMessage     // Safe error message (string)
$errorId          // Unique error identifier (string)  
$showDetails      // Whether to show debug info (boolean)
$exception        // Exception object (if available)
```

---

## Production Safety

### Sensitive Data Protection

#### Information Filtering
```php
// Query sanitization (automatic)
$query = "UPDATE users SET password = 'secret123' WHERE id = 1";
// Logged as: "UPDATE users SET password = '***' WHERE id = 1"

// Stack trace sanitization
// Arguments containing 'password', 'secret', 'token' are replaced with '***'
// Long strings are truncated to prevent log bloat
// Objects/arrays are summarized as [Object(ClassName)] or [Array(5)]
```

#### Error Message Sanitization
```php
// Production error messages
if (isProduction()) {
    return 'An unexpected error occurred.'; // Generic
} else {
    return $exception->getMessage(); // Detailed
}
```

### Security Headers

Automatic security headers on error pages:
```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY  
X-XSS-Protection: 1; mode=block
```

---

## Security Considerations

### Security Event Logging

#### Automatic Security Logging
```php
// CSRF token failures
Logger::security('CSRF token mismatch', [
    'endpoint' => $_SERVER['REQUEST_URI'],
    'ip' => getClientIP(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);

// Authentication failures  
Logger::authentication('Login failed', [
    'email' => $email,
    'reason' => 'invalid_credentials',
    'attempt_count' => getFailedAttempts($email)
]);

// Access control violations
Logger::security('Unauthorized access attempt', [
    'resource' => '/admin/panel',
    'required_role' => 'admin',
    'user_role' => 'customer'
]);
```

#### Security Monitoring Integration
```php
// Critical security events trigger special handling
Logger::critical('SECURITY: Multiple failed login attempts', [
    'ip' => $suspiciousIP,
    'attempts' => 10,
    'timeframe' => '5 minutes'
]);
// Automatically logged to critical.log with full context
```

### Attack Prevention

#### Log Injection Prevention
- All log data is JSON encoded
- Special characters are properly escaped
- Log format validation prevents injection

#### Information Disclosure Prevention
- Stack traces sanitized in production
- Database credentials masked in logs
- User passwords never logged
- Session IDs obfuscated in public logs

---

## Performance Monitoring

### Built-in Performance Tracking

#### Request Performance
```php
// Automatic request timing (in index.php)
$requestStart = microtime(true);
// ... handle request ...
Logger::request($method, $uri, $responseCode, microtime(true) - $requestStart);
```

#### Database Performance
```php
// Automatic query performance logging
$startTime = microtime(true);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
Logger::database($query, microtime(true) - $startTime, [
    'affected_rows' => $stmt->rowCount()
]);
```

#### Operation Performance
```php
// Custom operation timing
Logger::performance('Product search', $duration, [
    'search_term' => $term,
    'result_count' => count($results),
    'filters_applied' => $filterCount
]);
```

### Performance Thresholds

Automatic performance classification:
- **Database queries > 0.5s**: Logged as WARNING
- **HTTP requests > 1.0s**: Logged as WARNING  
- **Operations > 1.0s**: Logged as WARNING
- **Memory usage tracking**: Included in all log entries

---

## Configuration Guide

### Environment Variables

```ini
# .env configuration
APP_ENV=production          # production|development
APP_DEBUG=false            # true|false
LOG_LEVEL=info             # debug|info|notice|warning|error|critical|alert|emergency
APP_TIMEZONE=UTC           # Timezone for timestamps
```

### Log Level Configuration

```php
// Set minimum log level
putenv('LOG_LEVEL=warning');

// Only WARNING, ERROR, CRITICAL, ALERT, EMERGENCY will be logged
Logger::info('This will be filtered out');
Logger::warning('This will be logged');
```

### Custom Log Directory

```php
// Override default log directory
Logger::init('/custom/log/path');
```

---

## Testing and Validation

### Test Suite Location
`tests/Unit/Core/LoggerTest.php`

### Running Tests
```bash
# Run all logging tests
./vendor/bin/phpunit tests/Unit/Core/LoggerTest.php

# Run specific test category
./vendor/bin/phpunit --filter testSecurityLogging tests/Unit/Core/LoggerTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage tests/Unit/Core/LoggerTest.php
```

### Test Coverage Areas

#### Functional Testing
- All PSR-3 log levels
- Log level filtering
- Structured log format
- File segmentation
- Log rotation

#### Security Testing  
- Query sanitization
- Sensitive data filtering
- Stack trace sanitization
- Error message sanitization

#### Performance Testing
- Log write performance
- Memory usage tracking
- File size management
- Rotation efficiency

#### Integration Testing
- Error handler integration
- Controller integration
- Template rendering
- Bootstrap initialization

---

## Troubleshooting

### Common Issues

#### "Permission Denied" Errors
```bash
# Check log directory permissions
ls -la storage/logs/

# Fix permissions
chmod 775 storage/logs/
chown www-data:www-data storage/logs/
```

#### High Log Volume
```php
// Increase log level to reduce volume
putenv('LOG_LEVEL=warning'); // Only log warnings and above

// Check log statistics
$stats = Logger::getStats();
echo "Total log size: " . formatBytes($stats['total_size']);
echo "File count: " . $stats['file_count'];
```

#### Missing Log Entries
```php
// Check if logging is initialized
ErrorHandler::getStatus(); // Shows initialization status

// Verify log level settings
Logger::debug('Test debug message');  // Won't appear if LOG_LEVEL > debug
Logger::error('Test error message');  // Should always appear
```

#### Error Template Issues
```php
// Check template file permissions
ls -la app/views/errors/

// Verify template syntax
php -l app/views/errors/500.php

// Test fallback rendering
// If templates fail, inline HTML is used automatically
```

### Debug Mode

Enable detailed error information:
```ini
# .env
APP_ENV=development
APP_DEBUG=true
```

This enables:
- Detailed stack traces
- Variable dumps
- Template debugging
- Verbose error messages

### Log Analysis

#### Find Errors by ID
```bash
# Search all logs for specific error ID
grep -r "20250908-abc12345" storage/logs/

# Search for security events
grep -r "SECURITY:" storage/logs/
```

#### Analyze Performance
```bash
# Find slow operations
grep -r "WARNING.*PERF:" storage/logs/

# Find database issues
grep -r "DB:" storage/logs/ | grep "WARNING\|ERROR"
```

---

## Best Practices

### Development Guidelines

#### 1. Use Appropriate Log Levels
```php
// ✅ CORRECT - Use appropriate levels
Logger::debug('Cache key generated', ['key' => $key]);
Logger::info('User action completed', ['action' => 'update_profile']);
Logger::warning('Rate limit approaching', ['requests' => 950, 'limit' => 1000]);
Logger::error('Payment processing failed', ['order_id' => 123]);
Logger::critical('Database connection lost');

// ❌ INCORRECT - Wrong levels
Logger::error('User clicked button'); // Should be debug/info
Logger::debug('Payment failed');      // Should be error
```

#### 2. Provide Meaningful Context
```php
// ✅ CORRECT - Rich context
Logger::error('Order processing failed', [
    'order_id' => $order->id,
    'customer_id' => $order->customer_id,
    'payment_method' => $order->payment_method,
    'error_code' => $exception->getCode(),
    'step' => 'payment_processing'
]);

// ❌ INCORRECT - Minimal context  
Logger::error('Order failed');
```

#### 3. Handle Errors Gracefully
```php
// ✅ CORRECT - Comprehensive error handling
try {
    $result = $paymentGateway->process($order);
    Logger::info('Payment processed successfully', [
        'order_id' => $order->id,
        'transaction_id' => $result->id
    ]);
    return $result;
} catch (PaymentException $e) {
    Logger::error('Payment processing failed', [
        'order_id' => $order->id,
        'gateway_error' => $e->getGatewayMessage(),
        'error_code' => $e->getCode()
    ]);
    
    // Provide user-friendly feedback
    flash_set('error', 'Payment could not be processed. Please try again.');
    redirect('/checkout');
} catch (\Throwable $e) {
    Logger::critical('Unexpected error during payment processing', [
        'order_id' => $order->id,
        'exception' => get_class($e),
        'message' => $e->getMessage()
    ]);
    
    // Graceful degradation
    flash_set('error', 'An unexpected error occurred. Please contact support.');
    redirect('/orders');
}
```

#### 4. Security-First Logging
```php
// ✅ CORRECT - Security-aware logging
Logger::authentication('Password change requested', [
    'user_id' => $user->id,
    'ip' => getClientIP(),
    'verification_method' => 'email'
    // Never log the actual passwords
]);

// ❌ INCORRECT - Security risk
Logger::info('Password changed', [
    'old_password' => $oldPass,  // Never log passwords!
    'new_password' => $newPass   // Security violation
]);
```

### Production Considerations

#### 1. Log Level Management
```php
// Production: Only log important events
// LOG_LEVEL=warning

// Staging: More verbose for debugging
// LOG_LEVEL=info

// Development: Everything for debugging
// LOG_LEVEL=debug
```

#### 2. Performance Impact
```php
// Minimize performance impact
if (Logger::shouldLog(Logger::DEBUG)) {
    $expensiveContext = generateDetailedContext(); // Only if needed
    Logger::debug('Detailed debug info', $expensiveContext);
}
```

#### 3. Storage Management
```php
// Monitor log storage
$stats = Logger::getStats();
if ($stats['total_size'] > 100 * 1024 * 1024) { // 100MB
    Logger::warning('Log storage exceeds 100MB', $stats);
    // Consider more aggressive rotation
}
```

### Monitoring Integration

#### 1. External Monitoring
```php
// Integration with external monitoring services
if (Logger::isCritical($level)) {
    // Send to external service (Sentry, DataDog, etc.)
    ExternalMonitor::send($message, $context);
}
```

#### 2. Alerting
```php
// Critical error alerting
Logger::critical('Database server unreachable', [
    'server' => $dbHost,
    'last_success' => $lastSuccessTime
]);
// This automatically triggers special handling
```

---

## Summary

This comprehensive error handling and logging system provides:

✅ **Enterprise-Grade Logging**: PSR-3 compliance with structured JSON logging  
✅ **Production Safety**: No sensitive data exposure with user-friendly error pages  
✅ **Security Monitoring**: Specialized security event logging and monitoring  
✅ **Performance Tracking**: Built-in performance monitoring and optimization insights  
✅ **Robust Error Handling**: Graceful error recovery with comprehensive coverage  
✅ **Comprehensive Testing**: Full test suite ensuring reliability and security  
✅ **Developer Experience**: Rich debugging information and clear error messages  

The system is production-ready and provides enterprise-grade error handling suitable for high-availability applications while maintaining excellent developer experience and comprehensive security monitoring.

---

*Last Updated: September 2025*  
*Version: 1.0*  
*Status: Production Ready*