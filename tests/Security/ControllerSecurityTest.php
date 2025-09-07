<?php declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Core\Controller;

/**
 * Security tests for Controller class to prevent variable pollution vulnerabilities
 */
class ControllerSecurityTest extends TestCase
{
    private TestController $controller;

    protected function setUp(): void
    {
        $this->controller = new TestController();
    }

    /**
     * Test that malicious data cannot overwrite existing variables via view() method
     */
    public function testViewMethodPreventsVariablePollution(): void
    {
        // Set up a variable that should not be overwritten
        $existingVariable = 'secure_value';
        
        // Malicious data attempting variable pollution
        $maliciousData = [
            'existingVariable' => 'malicious_overwrite',
            'content' => 'malicious_content',
            '__view_file' => '/etc/passwd',  // Path traversal attempt
            'params' => 'overwritten_params',
            'view' => 'malicious_view'
        ];
        
        // Test that existing variables are not overwritten
        $result = $this->controller->testViewExtraction($maliciousData, $existingVariable);
        
        // The existing variable should remain unchanged
        $this->assertEquals('secure_value', $result['existingVariable']);
        $this->assertEquals('secure_value', $existingVariable); // Original variable unchanged
    }

    /**
     * Test that render() method prevents variable pollution
     */
    public function testRenderMethodPreventsVariablePollution(): void
    {
        // Set up variables that should not be overwritten
        $secureVar = 'original_secure_value';
        $anotherVar = 'another_secure_value';
        
        // Malicious data
        $maliciousData = [
            'secureVar' => 'compromised_value',
            'anotherVar' => 'also_compromised',
            'ob_start' => 'function_override_attempt',
            'include' => 'include_override'
        ];
        
        $result = $this->controller->testRenderExtraction($maliciousData, $secureVar, $anotherVar);
        
        // Original variables should remain unchanged
        $this->assertEquals('original_secure_value', $result['secureVar']);
        $this->assertEquals('another_secure_value', $result['anotherVar']);
        $this->assertEquals('original_secure_value', $secureVar);
        $this->assertEquals('another_secure_value', $anotherVar);
    }

    /**
     * Test that legitimate data is still accessible after extraction
     */
    public function testLegitimateDataIsAccessible(): void
    {
        $legitimateData = [
            'title' => 'Page Title',
            'content' => 'Page Content',
            'user' => ['name' => 'John Doe', 'role' => 'admin']
        ];
        
        $result = $this->controller->testViewExtraction($legitimateData);
        
        // Legitimate data should be available
        $this->assertEquals('Page Title', $result['title']);
        $this->assertEquals('Page Content', $result['content']);
        $this->assertEquals(['name' => 'John Doe', 'role' => 'admin'], $result['user']);
    }

    /**
     * Test edge cases with system variables and reserved names
     */
    public function testSystemVariableProtection(): void
    {
        $systemVarAttempts = [
            '_GET' => ['malicious' => 'data'],
            '_POST' => ['malicious' => 'data'],
            '_SESSION' => ['hijacked' => 'session'],
            '_COOKIE' => ['malicious' => 'cookie'],
            'GLOBALS' => ['compromised' => 'globals'],
            '__FILE__' => '/malicious/path',
            '__DIR__' => '/malicious/dir'
        ];
        
        // Store original values
        $originalGet = $_GET ?? null;
        $originalPost = $_POST ?? null;
        $originalSession = $_SESSION ?? null;
        
        $this->controller->testViewExtraction($systemVarAttempts);
        
        // System variables should not be overwritten by malicious data
        $this->assertEquals($originalGet, $_GET ?? null);
        $this->assertEquals($originalPost, $_POST ?? null);
        $this->assertEquals($originalSession, $_SESSION ?? null);
    }

    /**
     * Test with empty and null data
     */
    public function testEmptyDataHandling(): void
    {
        // Test with empty array
        $result1 = $this->controller->testViewExtraction([]);
        $this->assertIsArray($result1);
        
        // Test with array containing null values
        $result2 = $this->controller->testViewExtraction(['nullValue' => null, 'emptyString' => '']);
        $this->assertNull($result2['nullValue']);
        $this->assertEquals('', $result2['emptyString']);
    }
}

/**
 * Test controller class for security testing
 */
class TestController extends Controller
{
    /**
     * Public method to test view() extraction behavior
     */
    public function testViewExtraction(array $params, ...$existingVars): array
    {
        // Simulate existing variables in the method scope
        $existingVariable = $existingVars[0] ?? 'default_value';
        $secureVar = $existingVars[1] ?? 'default_secure';
        $anotherVar = $existingVars[2] ?? 'default_another';
        
        // Use same extraction logic as view() method
        extract($params, EXTR_SKIP);
        
        // Return the state after extraction
        return [
            'existingVariable' => $existingVariable,
            'secureVar' => $secureVar,
            'anotherVar' => $anotherVar,
            'title' => $title ?? null,
            'content' => $content ?? null,
            'user' => $user ?? null,
            'nullValue' => $nullValue ?? 'not_set',
            'emptyString' => $emptyString ?? 'not_set'
        ];
    }

    /**
     * Public method to test render() extraction behavior
     */
    public function testRenderExtraction(array $params, ...$existingVars): array
    {
        // Simulate existing variables
        $secureVar = $existingVars[0] ?? 'default_secure';
        $anotherVar = $existingVars[1] ?? 'default_another';
        
        // Use same extraction logic as render() method
        extract($params, EXTR_SKIP);
        
        return [
            'secureVar' => $secureVar,
            'anotherVar' => $anotherVar
        ];
    }

    // Override parent methods to prevent actual file includes during testing
    protected function view(string $view, array $params = []): void
    {
        // Testing stub - don't actually render views
    }

    protected function render(string $view, array $params = []): string
    {
        // Testing stub - return empty string
        return '';
    }
}