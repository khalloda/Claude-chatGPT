# Security Guidelines
## Secure Coding Practices for Spare Parts Management System

---

## Table of Contents

1. [Controller Security](#controller-security)
2. [Variable Pollution Prevention](#variable-pollution-prevention)
3. [View Data Handling](#view-data-handling)
4. [Security Best Practices](#security-best-practices)
5. [Testing Security Features](#testing-security-features)

---

## Controller Security

### Variable Pollution Prevention

**Critical Security Fix - September 2025**: The application has been secured against variable pollution attacks by replacing `EXTR_OVERWRITE` with `EXTR_SKIP` in controller methods.

#### The Vulnerability (FIXED)
Previously, the base Controller class used dangerous extraction:
```php
// ❌ VULNERABLE - BEFORE FIX
extract($params, EXTR_OVERWRITE); // Could overwrite existing variables
```

#### The Fix (IMPLEMENTED)
Now uses secure extraction:
```php
// ✅ SECURE - CURRENT IMPLEMENTATION
extract($params, EXTR_SKIP); // Preserves existing variables
```

### What is Variable Pollution?

Variable pollution occurs when user-controlled data can overwrite existing variables in the current scope. This can lead to:

- **Authentication Bypass**: Overwriting authentication status variables
- **Authorization Bypass**: Changing user role or permission variables  
- **Data Tampering**: Modifying critical application variables
- **Security Control Bypass**: Overwriting security flags or tokens

#### Example Attack Scenario (NOW PREVENTED)
```php
// Hypothetical vulnerable code (no longer present)
function vulnerableController() {
    $isAdmin = false;
    $userId = getCurrentUserId();
    
    // Attacker sends: ['isAdmin' => true, 'userId' => 1]
    extract($_POST, EXTR_OVERWRITE); // ❌ DANGEROUS
    
    if ($isAdmin) {
        // Attacker gains admin access!
        performAdminAction();
    }
}
```

### Secure Implementation

#### Current Secure Pattern
```php
// In app/core/controller.php
protected function view(string $view, array $params = []): void
{
    // ✅ SECURE: Won't overwrite existing variables
    extract($params, EXTR_SKIP);
    
    $content = $this->render($view, $params);
    include __DIR__ . '/../views/layouts/main.php';
}
```

---

## View Data Handling

### Safe Data Passing to Views

#### Recommended Approach
```php
// In your controller methods
public function showProduct(int $id): void
{
    // Fetch data securely
    $product = Product::find($id);
    $categories = Category::all();
    
    // Pass only necessary data
    $viewData = [
        'product' => $product,
        'categories' => $categories,
        'pageTitle' => 'Product Details'
    ];
    
    // Secure view rendering
    $this->view('products/show', $viewData);
}
```

#### Data Validation Before Views
```php
public function editProduct(int $id): void
{
    // Validate and sanitize input
    $id = (int) $id;
    if ($id <= 0) {
        Flash::error('Invalid product ID');
        redirect('/products');
        return;
    }
    
    $product = Product::find($id);
    if (!$product) {
        Flash::error('Product not found');
        redirect('/products');
        return;
    }
    
    // Safe data passing
    $this->view('products/edit', [
        'product' => $product,
        'categories' => Category::all(),
        'csrfToken' => csrf_token()
    ]);
}
```

### Reserved Variable Names to Avoid

When passing data to views, avoid these variable names that could conflict with system variables:

```php
// ❌ AVOID these variable names in view data:
$dangerousViewData = [
    '_GET' => $data,           // Superglobal
    '_POST' => $data,          // Superglobal  
    '_SESSION' => $data,       // Superglobal
    '_COOKIE' => $data,        // Superglobal
    'GLOBALS' => $data,        // Superglobal
    '__FILE__' => $data,       // Magic constant
    '__DIR__' => $data,        // Magic constant
    'params' => $data,         // May conflict with method parameter
    'view' => $data,           // May conflict with view name
    'content' => $data,        // May conflict with rendered content
];

// ✅ USE descriptive, safe names:
$safeViewData = [
    'productData' => $product,
    'categoryList' => $categories,
    'userInfo' => $currentUser,
    'pageMetadata' => $metadata,
    'formToken' => $csrfToken
];
```

---

## Security Best Practices

### 1. Input Validation
Always validate data before passing to views:

```php
public function createProduct(): void
{
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        Flash::error('Invalid security token');
        redirect('/products');
        return;
    }
    
    // Validate and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    
    if (empty($name)) {
        Flash::error('Product name is required');
        redirect('/products/create');
        return;
    }
    
    // Safe data usage...
}
```

### 2. Output Escaping in Views
Always escape output in view templates:

```php
<!-- In view files -->
<h1><?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($productData['description'], ENT_QUOTES, 'UTF-8') ?></p>

<!-- For URLs -->
<a href="/products/<?= urlencode($productData['id']) ?>">View Product</a>

<!-- For JavaScript data -->
<script>
var productData = <?= json_encode($productData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
```

### 3. Authentication Checks
Ensure proper authentication in all controllers:

```php
abstract class SecureController extends Controller 
{
    public function __construct() 
    {
        // Require authentication for all actions
        require_auth();
        
        // Optional: Check specific permissions
        if (!current_user_can('manage_products')) {
            Flash::error('Insufficient permissions');
            redirect('/dashboard');
            return;
        }
    }
}
```

### 4. Error Handling
Handle errors securely without information disclosure:

```php
public function sensitiveAction(): void
{
    try {
        // Sensitive operation
        $result = performSensitiveTask();
        
        Flash::success('Operation completed successfully');
        
    } catch (Exception $e) {
        // Log detailed error for developers
        Logger::error('Sensitive operation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => get_user()['id']
        ]);
        
        // Show generic error to user
        Flash::error('Operation failed. Please try again.');
    }
    
    redirect('/dashboard');
}
```

---

## Testing Security Features

### Running Security Tests
```bash
# Run all security tests
php vendor/bin/phpunit tests/Security/

# Run specific controller security tests
php vendor/bin/phpunit tests/Security/ControllerSecurityTest.php

# Run with verbose output
php vendor/bin/phpunit tests/Security/ControllerSecurityTest.php --testdox
```

### Writing Security Tests
When adding new controller methods, include security tests:

```php
<?php
class NewControllerSecurityTest extends TestCase
{
    public function testNewMethodPreventsVariablePollution(): void
    {
        // Set up existing variables
        $secureVariable = 'secure_value';
        
        // Attempt variable pollution
        $maliciousData = [
            'secureVariable' => 'malicious_override'
        ];
        
        // Test your method
        $controller = new YourController();
        $result = $controller->yourMethod($maliciousData);
        
        // Assert security is maintained
        $this->assertEquals('secure_value', $secureVariable);
    }
}
```

### Security Test Coverage Requirements
All controllers must have security tests covering:

1. **Variable pollution prevention**
2. **Input validation bypass attempts**
3. **Authentication and authorization checks**
4. **CSRF token validation**
5. **Data sanitization verification**

---

## Compliance and Auditing

### Security Audit Checklist
- [ ] All `extract()` calls use `EXTR_SKIP`
- [ ] No user data directly included in views without validation
- [ ] All outputs properly escaped
- [ ] CSRF protection on all forms
- [ ] Authentication required for protected actions
- [ ] Input validation on all user inputs
- [ ] Error messages don't expose sensitive information
- [ ] Security tests cover all critical paths

### Regular Security Reviews
- **Monthly**: Review new code for security issues
- **Quarterly**: Run automated security scans
- **Annually**: External security audit
- **After incidents**: Immediate security review and fixes

### Documentation Requirements
All security-related changes must be documented with:
- **What**: Description of the security change
- **Why**: Security risk being addressed
- **How**: Technical implementation details  
- **Testing**: How the fix was verified
- **Impact**: Effect on existing functionality

---

**Last Updated**: September 2025  
**Next Review**: December 2025  
**Security Contact**: [Your Security Team]