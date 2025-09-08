# CSRF Protection Implementation Guide

## Overview

This application implements comprehensive Cross-Site Request Forgery (CSRF) protection using a multi-layered approach that supports both traditional form submissions and modern AJAX requests. The CSRF protection system provides defense against unauthorized actions being performed on behalf of authenticated users.

## Table of Contents

1. [Security Architecture](#security-architecture)
2. [Implementation Components](#implementation-components)
3. [Usage Guidelines](#usage-guidelines)
4. [JavaScript Integration](#javascript-integration)
5. [Testing and Validation](#testing-and-validation)
6. [Troubleshooting](#troubleshooting)
7. [Security Best Practices](#security-best-practices)

---

## Security Architecture

### Defense Layers

Our CSRF protection implements multiple layers of security:

1. **Token-based Verification**: Every state-changing request requires a valid CSRF token
2. **Dual Verification Methods**: Supports both form-based and header-based token submission
3. **Automatic Token Refresh**: Long-running sessions automatically refresh tokens
4. **Timing Attack Protection**: Uses `hash_equals()` for secure token comparison
5. **Entropy Validation**: Tokens use cryptographically secure random generation

### Token Lifecycle

```
Page Load → Token Generation → User Action → Token Verification → Token Refresh (if needed)
     ↓              ↓               ↓              ↓                    ↓
Meta Tag &     Session Storage  Form/AJAX    hash_equals()      New Token Generated
Form Fields    + Timestamp      Submission   Verification       + Session Update
```

---

## Implementation Components

### Core Functions (`app/core/helpers.php`)

#### Token Generation
```php
csrf_token(): string
```
- Generates a cryptographically secure 64-character hexadecimal token
- Stores token and creation timestamp in session
- Returns existing token if already generated for current session

#### Form Integration
```php
csrf_field(): string
```
- Returns HTML input field with CSRF token
- Automatically escapes token value for HTML safety
- Ready for direct inclusion in forms

#### Token Verification
```php
verify_csrf_request(): bool     // Recommended for all new code
verify_csrf_post(): bool        // Legacy form-only verification
verify_csrf_header(): bool      // AJAX header-only verification
```

#### Token Management
```php
regenerate_csrf_token(): string  // Forces new token generation
csrf_token_expired(): bool       // Checks if token is older than 2 hours
refresh_csrf_if_needed(): void   // Conditionally refreshes expired tokens
```

### Route Integration

#### CSRF Refresh Endpoint (`/csrf-refresh`)
- **Method**: GET
- **Purpose**: Provides fresh CSRF tokens for AJAX applications
- **Response**: JSON with new token
- **Usage**: Automatically called by JavaScript framework

```php
// Endpoint returns:
{
    "token": "new_64_character_hex_token"
}
```

---

## Usage Guidelines

### Traditional Form Protection

For standard HTML forms, include the CSRF field immediately after the opening form tag:

```html
<?php use function App\Core\csrf_field; ?>

<form method="post" action="/your-endpoint">
    <?= csrf_field() ?>
    <!-- Your form fields here -->
    <button type="submit">Submit</button>
</form>
```

### Controller Verification

In your controller methods, verify CSRF tokens for all state-changing operations:

```php
use function App\Core\verify_csrf_request;

public function store(): void
{
    require_auth();
    
    // Verify CSRF token (supports both form and AJAX)
    if (!verify_csrf_request()) {
        flash_set('error', 'Invalid session token.');
        redirect('/your-page');
        return;
    }
    
    // Process the request...
}
```

### AJAX Request Protection

The JavaScript framework automatically includes CSRF tokens in AJAX requests:

```javascript
// Automatic CSRF protection for all state-changing requests
const response = await App.fetchJson('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'Product Name'
    })
});
```

---

## JavaScript Integration

### Automatic Token Management

The `App.js` framework provides automatic CSRF protection:

#### Token Retrieval
```javascript
const token = App.getCsrfToken(); // Gets token from meta tag
```

#### Token Refresh
```javascript
const newToken = await App.refreshCsrfToken(); // Fetches fresh token from server
```

#### Automatic Request Enhancement
- All POST, PUT, PATCH, DELETE requests automatically include CSRF tokens
- Supports both FormData and JSON request bodies
- Includes fallback and retry logic for expired tokens

### Session Management

#### Automatic Refresh Schedule
- **Interval**: Every 90 minutes (1.5 hours)
- **Trigger**: Page visibility change (tab switching)
- **Fallback**: Manual refresh on 419 HTTP responses

```javascript
// Automatic refresh configuration (already implemented)
setInterval(() => {
    if (typeof App !== 'undefined' && App.refreshCsrfToken) {
        App.refreshCsrfToken();
    }
}, 90 * 60 * 1000); // 90 minutes
```

---

## Testing and Validation

### Test Suite Location
`tests/Security/CSRFSecurityTest.php`

### Test Coverage Areas

#### Core Functionality Tests
- Token generation and uniqueness
- Token validation (form and header)
- Token expiration and refresh
- HTML field generation

#### Security Tests
- Timing attack prevention
- Session fixation prevention
- Invalid token handling
- Missing token scenarios

#### Integration Tests
- Complete form workflow
- AJAX request workflow
- Performance benchmarks

### Running Tests

```bash
# Run all CSRF tests
./vendor/bin/phpunit tests/Security/CSRFSecurityTest.php

# Run with verbose output
./vendor/bin/phpunit --verbose tests/Security/CSRFSecurityTest.php

# Run specific test method
./vendor/bin/phpunit --filter testCsrfTokenGeneration tests/Security/CSRFSecurityTest.php
```

---

## Troubleshooting

### Common Issues and Solutions

#### "Invalid session token" Errors

**Symptom**: Users receive CSRF token validation errors
**Causes**: 
- Expired tokens (>2 hours old)
- Session issues
- Multiple tab interference

**Solutions**:
```php
// 1. Check token expiration
if (csrf_token_expired()) {
    refresh_csrf_if_needed();
}

// 2. Verify session is active
if (!isset($_SESSION['csrf'])) {
    session_start();
    csrf_token(); // Generate fresh token
}
```

#### AJAX Requests Failing

**Symptom**: 419 HTTP responses on AJAX calls
**Causes**:
- Missing meta tag
- JavaScript framework not loaded
- Custom AJAX not using App.fetchJson()

**Solutions**:
```html
<!-- Ensure meta tag is present -->
<meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<!-- Use the framework -->
<script src="/assets/js/app.js" defer></script>
```

#### Token Refresh Failures

**Symptom**: Automatic refresh not working
**Causes**:
- JavaScript errors
- Network issues
- Server-side problems

**Debugging**:
```javascript
// Manual token refresh test
App.refreshCsrfToken().then(token => {
    console.log('New token:', token);
}).catch(error => {
    console.error('Refresh failed:', error);
});
```

### Debug Mode

Enable debug logging for CSRF operations:

```php
// In development environment
if (getenv('APP_DEBUG') === 'true') {
    error_log('CSRF token verification: ' . (verify_csrf_request() ? 'PASS' : 'FAIL'));
    error_log('Token from session: ' . ($_SESSION['csrf'] ?? 'NONE'));
    error_log('Token from POST: ' . ($_POST['_token'] ?? 'NONE'));
    error_log('Token from header: ' . ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'NONE'));
}
```

---

## Security Best Practices

### Development Guidelines

#### 1. Always Use CSRF Protection
```php
// ✅ CORRECT - Always verify CSRF for state changes
public function delete(): void
{
    if (!verify_csrf_request()) {
        // Handle invalid token
    }
    // Process deletion
}

// ❌ INCORRECT - No CSRF verification
public function delete(): void
{
    // Direct processing without verification
}
```

#### 2. Use Unified Verification Function
```php
// ✅ CORRECT - Supports both form and AJAX
if (!verify_csrf_request()) { /* handle error */ }

// ❌ OUTDATED - Only supports forms
if (!verify_csrf_post()) { /* handle error */ }
```

#### 3. Proper Error Handling
```php
// ✅ CORRECT - Clear error messaging
if (!verify_csrf_request()) {
    flash_set('error', 'Invalid session token. Please try again.');
    redirect('/safe-page');
    return;
}

// ❌ INCORRECT - Generic or missing error
if (!verify_csrf_request()) {
    die('Error');
}
```

### Production Considerations

#### 1. Session Security
```php
// Ensure secure session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
```

#### 2. HTTPS Requirements
- CSRF protection requires HTTPS in production
- Tokens transmitted over HTTP are vulnerable to interception

#### 3. Token Rotation
- Tokens automatically expire after 2 hours
- Implement regular token rotation for high-security applications

### Monitoring and Alerting

#### Security Metrics to Track
- CSRF validation failure rate
- Token refresh frequency
- Session duration statistics
- Failed request patterns

#### Log Analysis
```php
// Log CSRF failures for security monitoring
if (!verify_csrf_request()) {
    Logger::security('CSRF verification failed', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
        'timestamp' => time()
    ]);
}
```

---

## Advanced Configuration

### Custom Token Expiration
Modify token expiration time in `csrf_token_expired()`:

```php
function csrf_token_expired(): bool
{
    $tokenTime = $_SESSION['csrf_created'] ?? 0;
    return (time() - $tokenTime) > 3600; // 1 hour instead of 2
}
```

### High-Security Environments
For applications requiring maximum security:

```php
// Generate new token for every request
function strict_csrf_mode(): void
{
    regenerate_csrf_token();
}

// Call in sensitive controllers
public function criticalAction(): void
{
    strict_csrf_mode(); // New token every time
    if (!verify_csrf_request()) { /* handle */ }
    // Process critical action
}
```

### Custom Error Responses
Customize CSRF error handling for API endpoints:

```php
function handle_csrf_failure(): void
{
    if (is_ajax_request()) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'CSRF token mismatch',
            'new_token' => regenerate_csrf_token()
        ]);
        exit;
    } else {
        flash_set('error', 'Invalid session token.');
        redirect('/login');
    }
}
```

---

## Migration Guide

### Updating Existing Controllers

Replace old verification patterns:

```php
// OLD PATTERN
use function App\Core\verify_csrf_post;

if (!verify_csrf_post()) {
    // error handling
}

// NEW PATTERN
use function App\Core\verify_csrf_request;

if (!verify_csrf_request()) {
    // error handling
}
```

### JavaScript Migration

Update custom AJAX calls to use the framework:

```javascript
// OLD PATTERN
fetch('/endpoint', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
});

// NEW PATTERN
App.fetchJson('/endpoint', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
});
```

---

## Summary

This CSRF protection implementation provides:

✅ **Complete Coverage**: All forms and AJAX requests protected  
✅ **Automatic Management**: Token generation, refresh, and validation  
✅ **Security Hardened**: Timing-safe comparison and secure token generation  
✅ **Developer Friendly**: Simple integration with clear error messages  
✅ **Performance Optimized**: Minimal overhead with efficient validation  
✅ **Test Coverage**: Comprehensive security test suite  

The system is production-ready and provides enterprise-grade CSRF protection suitable for high-security applications while maintaining ease of use for developers.

---

*Last Updated: September 2025*  
*Version: 1.0*  
*Status: Production Ready*