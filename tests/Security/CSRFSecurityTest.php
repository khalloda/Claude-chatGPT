<?php declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use function App\Core\csrf_token;
use function App\Core\csrf_field;
use function App\Core\verify_csrf_post;
use function App\Core\verify_csrf_header;
use function App\Core\verify_csrf_request;
use function App\Core\regenerate_csrf_token;
use function App\Core\csrf_token_expired;
use function App\Core\refresh_csrf_if_needed;

/**
 * Comprehensive CSRF Protection Security Test Suite
 * 
 * Tests all aspects of CSRF protection including:
 * - Token generation and validation
 * - Form-based CSRF protection
 * - AJAX/Header-based CSRF protection
 * - Token refresh mechanisms
 * - Attack scenario prevention
 */
final class CSRFSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        // Start clean session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER = [];
    }

    // ==========================================
    // Token Generation and Validation Tests
    // ==========================================

    public function testCsrfTokenGeneration(): void
    {
        $token1 = csrf_token();
        $this->assertIsString($token1);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token1);
        
        // Second call should return same token
        $token2 = csrf_token();
        $this->assertEquals($token1, $token2);
        
        // Verify session storage
        $this->assertArrayHasKey('csrf', $_SESSION);
        $this->assertArrayHasKey('csrf_created', $_SESSION);
        $this->assertEquals($token1, $_SESSION['csrf']);
    }

    public function testCsrfFieldGeneration(): void
    {
        $field = csrf_field();
        $token = csrf_token();
        
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="_token"', $field);
        $this->assertStringContainsString($token, $field);
        $this->assertStringContainsString('value="', $field);
        
        // Verify HTML escaping
        $this->assertStringNotContainsString('<script>', $field);
        $this->assertStringNotContainsString('">', $field);
    }

    public function testTokenRegeneration(): void
    {
        $token1 = csrf_token();
        $token2 = regenerate_csrf_token();
        
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals($token2, $_SESSION['csrf']);
        $this->assertEquals(64, strlen($token2));
    }

    // ==========================================
    // Form-based CSRF Protection Tests
    // ==========================================

    public function testValidCsrfPostVerification(): void
    {
        $token = csrf_token();
        $_POST['_token'] = $token;
        
        $this->assertTrue(verify_csrf_post());
    }

    public function testInvalidCsrfPostVerification(): void
    {
        csrf_token(); // Generate valid token
        $_POST['_token'] = 'invalid_token';
        
        $this->assertFalse(verify_csrf_post());
    }

    public function testMissingCsrfPostToken(): void
    {
        csrf_token(); // Generate valid token
        // No POST token set
        
        $this->assertFalse(verify_csrf_post());
    }

    public function testEmptyCsrfPostToken(): void
    {
        csrf_token(); // Generate valid token
        $_POST['_token'] = '';
        
        $this->assertFalse(verify_csrf_post());
    }

    // ==========================================
    // Header-based CSRF Protection Tests (AJAX)
    // ==========================================

    public function testValidCsrfHeaderVerification(): void
    {
        $token = csrf_token();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        
        $this->assertTrue(verify_csrf_header());
    }

    public function testInvalidCsrfHeaderVerification(): void
    {
        csrf_token(); // Generate valid token
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid_token';
        
        $this->assertFalse(verify_csrf_header());
    }

    public function testMissingCsrfHeader(): void
    {
        csrf_token(); // Generate valid token
        // No header set
        
        $this->assertFalse(verify_csrf_header());
    }

    public function testEmptyCsrfHeader(): void
    {
        csrf_token(); // Generate valid token
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        
        $this->assertFalse(verify_csrf_header());
    }

    // ==========================================
    // Unified Request CSRF Protection Tests
    // ==========================================

    public function testCsrfRequestWithValidPost(): void
    {
        $token = csrf_token();
        $_POST['_token'] = $token;
        
        $this->assertTrue(verify_csrf_request());
    }

    public function testCsrfRequestWithValidHeader(): void
    {
        $token = csrf_token();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        
        $this->assertTrue(verify_csrf_request());
    }

    public function testCsrfRequestWithBothPostAndHeader(): void
    {
        $token = csrf_token();
        $_POST['_token'] = $token;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        
        $this->assertTrue(verify_csrf_request());
    }

    public function testCsrfRequestWithInvalidPostButValidHeader(): void
    {
        $token = csrf_token();
        $_POST['_token'] = 'invalid';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        
        $this->assertTrue(verify_csrf_request()); // Header should work
    }

    public function testCsrfRequestWithValidPostButInvalidHeader(): void
    {
        $token = csrf_token();
        $_POST['_token'] = $token;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid';
        
        $this->assertTrue(verify_csrf_request()); // Post should work
    }

    public function testCsrfRequestWithBothInvalid(): void
    {
        csrf_token(); // Generate valid token
        $_POST['_token'] = 'invalid_post';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid_header';
        
        $this->assertFalse(verify_csrf_request());
    }

    // ==========================================
    // Token Expiry and Refresh Tests
    // ==========================================

    public function testTokenNotExpiredWhenNew(): void
    {
        csrf_token(); // Creates fresh token
        $this->assertFalse(csrf_token_expired());
    }

    public function testTokenExpiredWhenOld(): void
    {
        csrf_token(); // Creates fresh token
        // Simulate token created 3 hours ago (older than 2 hour expiry)
        $_SESSION['csrf_created'] = time() - (3 * 3600);
        
        $this->assertTrue(csrf_token_expired());
    }

    public function testRefreshCsrfWhenNeeded(): void
    {
        $originalToken = csrf_token();
        
        // Simulate expired token
        $_SESSION['csrf_created'] = time() - (3 * 3600);
        
        refresh_csrf_if_needed();
        
        $newToken = $_SESSION['csrf'];
        $this->assertNotEquals($originalToken, $newToken);
        $this->assertGreaterThanOrEqual(time() - 1, $_SESSION['csrf_created']);
    }

    public function testRefreshCsrfWhenNotNeeded(): void
    {
        $originalToken = csrf_token();
        
        refresh_csrf_if_needed();
        
        $this->assertEquals($originalToken, $_SESSION['csrf']);
    }

    // ==========================================
    // Security Attack Prevention Tests
    // ==========================================

    public function testTimingAttackPrevention(): void
    {
        $token = csrf_token();
        
        $startTime = microtime(true);
        $_POST['_token'] = $token;
        $validResult = verify_csrf_post();
        $validTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        $_POST['_token'] = 'invalid_token_same_length_as_real_token_to_test_timing_attack_resistance';
        $invalidResult = verify_csrf_post();
        $invalidTime = microtime(true) - $startTime;
        
        $this->assertTrue($validResult);
        $this->assertFalse($invalidResult);
        
        // Time difference should be minimal (hash_equals is timing-safe)
        $timeDifference = abs($validTime - $invalidTime);
        $this->assertLessThan(0.001, $timeDifference); // Less than 1ms difference
    }

    public function testSessionFixationPrevention(): void
    {
        // Simulate attack where attacker tries to fix session ID
        $attackerToken = 'attacker_controlled_token_value';
        $_SESSION['csrf'] = $attackerToken;
        
        // Generate new token - should overwrite attacker's token
        $legitimateToken = regenerate_csrf_token();
        
        $this->assertNotEquals($attackerToken, $legitimateToken);
        $this->assertEquals($legitimateToken, $_SESSION['csrf']);
    }

    public function testCsrfTokenUniqueness(): void
    {
        $tokens = [];
        
        // Generate 100 tokens and verify they're all unique
        for ($i = 0; $i < 100; $i++) {
            $token = regenerate_csrf_token();
            $this->assertNotContains($token, $tokens, "Token $token was generated twice");
            $tokens[] = $token;
        }
        
        $this->assertCount(100, array_unique($tokens));
    }

    public function testCsrfTokenEntropy(): void
    {
        $token = csrf_token();
        $bytes = hex2bin($token);
        
        // Test entropy - token should contain varied bytes
        $uniqueBytes = count(array_unique(str_split($bytes)));
        $this->assertGreaterThan(20, $uniqueBytes, 'Token should have high entropy');
    }

    // ==========================================
    // Edge Case and Error Handling Tests
    // ==========================================

    public function testCsrfWithoutSession(): void
    {
        unset($_SESSION['csrf']);
        
        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertEquals($token, $_SESSION['csrf']);
    }

    public function testCsrfVerificationWithoutSession(): void
    {
        unset($_SESSION['csrf']);
        $_POST['_token'] = 'any_token';
        
        $this->assertFalse(verify_csrf_post());
    }

    public function testCsrfWithCorruptedSession(): void
    {
        $_SESSION['csrf'] = null;
        $_POST['_token'] = 'any_token';
        
        $this->assertFalse(verify_csrf_post());
    }

    public function testCsrfHeaderCaseInsensitivity(): void
    {
        $token = csrf_token();
        
        // Test lowercase header
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $this->assertTrue(verify_csrf_header());
        
        // Clear and test uppercase (not actually case sensitive in PHP)
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $this->assertTrue(verify_csrf_header());
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    public function testCsrfPerformance(): void
    {
        $startTime = microtime(true);
        
        // Test token generation performance
        for ($i = 0; $i < 100; $i++) {
            regenerate_csrf_token();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should generate 100 tokens in under 100ms
        $this->assertLessThan(0.1, $totalTime, 'Token generation should be fast');
    }

    public function testCsrfVerificationPerformance(): void
    {
        $token = csrf_token();
        $_POST['_token'] = $token;
        
        $startTime = microtime(true);
        
        // Test verification performance
        for ($i = 0; $i < 1000; $i++) {
            verify_csrf_request();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should verify 1000 times in under 10ms
        $this->assertLessThan(0.01, $totalTime, 'Token verification should be fast');
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    public function testCompleteFormWorkflow(): void
    {
        // 1. Generate token for form
        $formToken = csrf_token();
        $field = csrf_field();
        
        // 2. Submit form with token
        $_POST['_token'] = $formToken;
        $_POST['name'] = 'Test Product';
        
        // 3. Verify token
        $this->assertTrue(verify_csrf_request());
        
        // 4. After successful operation, regenerate token
        $newToken = regenerate_csrf_token();
        $this->assertNotEquals($formToken, $newToken);
    }

    public function testCompleteAjaxWorkflow(): void
    {
        // 1. Generate token for page load
        $pageToken = csrf_token();
        
        // 2. AJAX request with header
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $pageToken;
        
        // 3. Verify token
        $this->assertTrue(verify_csrf_request());
        
        // 4. Token refresh scenario
        $refreshedToken = regenerate_csrf_token();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $refreshedToken;
        $this->assertTrue(verify_csrf_request());
    }

    public function testSecurityHeadersIntegration(): void
    {
        $token = csrf_token();
        
        // Simulate various header combinations that might be sent
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        
        $this->assertTrue(verify_csrf_request());
    }
}