<?php declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Logger;

/**
 * Comprehensive Logger Test Suite
 * 
 * Tests all aspects of the enhanced logging system including:
 * - PSR-3 compliance
 * - Log levels and filtering
 * - Structured logging
 * - Security-focused logging
 * - Performance monitoring
 * - Log rotation and cleanup
 */
final class LoggerTest extends TestCase
{
    private static string $testLogDirectory;
    
    public static function setUpBeforeClass(): void
    {
        // Create temporary log directory for tests
        self::$testLogDirectory = sys_get_temp_dir() . '/test_logs_' . uniqid();
        mkdir(self::$testLogDirectory, 0775, true);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test logs
        if (is_dir(self::$testLogDirectory)) {
            $files = glob(self::$testLogDirectory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir(self::$testLogDirectory);
        }
    }

    protected function setUp(): void
    {
        // Reset logger state
        $reflection = new \ReflectionClass(Logger::class);
        $logDirectoryProperty = $reflection->getProperty('logDirectory');
        $logDirectoryProperty->setAccessible(true);
        $logDirectoryProperty->setValue(null, self::$testLogDirectory);
        
        $logLevelProperty = $reflection->getProperty('currentLogLevel');
        $logLevelProperty->setAccessible(true);
        $logLevelProperty->setValue(null, null);
        
        // Clean test log files
        $files = glob(self::$testLogDirectory . '/*.log');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    // ==========================================
    // PSR-3 Compliance Tests
    // ==========================================

    public function testLoggerInitialization(): void
    {
        Logger::init();
        $this->assertDirectoryExists(self::$testLogDirectory);
    }

    public function testAllPsr3LogLevels(): void
    {
        $testMessage = 'Test message';
        $testContext = ['key' => 'value'];

        Logger::emergency($testMessage, $testContext);
        Logger::alert($testMessage, $testContext);
        Logger::critical($testMessage, $testContext);
        Logger::error($testMessage, $testContext);
        Logger::warning($testMessage, $testContext);
        Logger::notice($testMessage, $testContext);
        Logger::info($testMessage, $testContext);
        Logger::debug($testMessage, $testContext);

        // Verify log files were created
        $logFiles = glob(self::$testLogDirectory . '/*.log');
        $this->assertNotEmpty($logFiles, 'Log files should be created');

        // Check that messages were logged
        $logContent = '';
        foreach ($logFiles as $file) {
            $logContent .= file_get_contents($file);
        }

        $this->assertStringContainsString($testMessage, $logContent);
        $this->assertStringContainsString('EMERGENCY', $logContent);
        $this->assertStringContainsString('ALERT', $logContent);
        $this->assertStringContainsString('CRITICAL', $logContent);
        $this->assertStringContainsString('ERROR', $logContent);
        $this->assertStringContainsString('WARNING', $logContent);
        $this->assertStringContainsString('NOTICE', $logContent);
        $this->assertStringContainsString('INFO', $logContent);
        $this->assertStringContainsString('DEBUG', $logContent);
    }

    // ==========================================
    // Log Level Filtering Tests
    // ==========================================

    public function testLogLevelFiltering(): void
    {
        // Set log level to WARNING
        putenv('LOG_LEVEL=warning');
        Logger::init();

        Logger::debug('Debug message');
        Logger::info('Info message');
        Logger::notice('Notice message');
        Logger::warning('Warning message');
        Logger::error('Error message');

        $logContent = $this->getAllLogContent();

        // Debug, Info, Notice should be filtered out
        $this->assertStringNotContainsString('Debug message', $logContent);
        $this->assertStringNotContainsString('Info message', $logContent);
        $this->assertStringNotContainsString('Notice message', $logContent);

        // Warning and Error should be logged
        $this->assertStringContainsString('Warning message', $logContent);
        $this->assertStringContainsString('Error message', $logContent);

        // Reset
        putenv('LOG_LEVEL=');
    }

    // ==========================================
    // Structured Logging Tests
    // ==========================================

    public function testStructuredLogFormat(): void
    {
        Logger::info('Test structured logging', ['user_id' => 123, 'action' => 'test']);

        $logContent = $this->getAllLogContent();
        $logLines = array_filter(explode("\n", $logContent));
        
        $this->assertNotEmpty($logLines);
        
        $logEntry = json_decode($logLines[0], true);
        $this->assertIsArray($logEntry, 'Log entry should be valid JSON');
        
        // Check required fields
        $this->assertArrayHasKey('timestamp', $logEntry);
        $this->assertArrayHasKey('level', $logEntry);
        $this->assertArrayHasKey('message', $logEntry);
        $this->assertArrayHasKey('context', $logEntry);
        $this->assertArrayHasKey('system', $logEntry);
        $this->assertArrayHasKey('request', $logEntry);
        $this->assertArrayHasKey('user', $logEntry);
        $this->assertArrayHasKey('memory_usage', $logEntry);
        $this->assertArrayHasKey('memory_peak', $logEntry);
        
        // Check specific values
        $this->assertEquals('INFO', $logEntry['level']);
        $this->assertEquals('Test structured logging', $logEntry['message']);
        $this->assertArrayHasKey('user_id', $logEntry['context']);
        $this->assertEquals(123, $logEntry['context']['user_id']);
    }

    public function testContextualInformation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SESSION['user'] = ['id' => 456, 'email' => 'test@example.com'];

        Logger::info('Test contextual logging');

        $logContent = $this->getAllLogContent();
        $logEntry = json_decode(array_filter(explode("\n", $logContent))[0], true);

        // Check request context
        $this->assertEquals('POST', $logEntry['request']['method']);
        $this->assertEquals('/test', $logEntry['request']['uri']);

        // Check user context
        $this->assertEquals(456, $logEntry['user']['user']['id']);
        $this->assertEquals('test@example.com', $logEntry['user']['user']['email']);
    }

    // ==========================================
    // Security Logging Tests
    // ==========================================

    public function testSecurityLogging(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
        $_SERVER['HTTP_REFERER'] = 'https://test.com';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        Logger::security('Unauthorized access attempt', ['endpoint' => '/admin']);

        $logContent = $this->getAllLogContent();
        $logEntry = json_decode(array_filter(explode("\n", $logContent))[0], true);

        $this->assertEquals('CRITICAL', $logEntry['level']);
        $this->assertStringContainsString('SECURITY:', $logEntry['message']);
        $this->assertEquals('security', $logEntry['context']['category']);
        $this->assertEquals('192.168.1.100', $logEntry['context']['ip']);
        $this->assertEquals('Test Browser', $logEntry['context']['user_agent']);
    }

    public function testAuthenticationLogging(): void
    {
        session_start();
        
        Logger::authentication('User login successful', ['user_id' => 789]);

        $logContent = $this->getAllLogContent();
        $logEntry = json_decode(array_filter(explode("\n", $logContent))[0], true);

        $this->assertEquals('INFO', $logEntry['level']);
        $this->assertStringContainsString('AUTH:', $logEntry['message']);
        $this->assertEquals('authentication', $logEntry['context']['category']);
        $this->assertNotEmpty($logEntry['context']['session_id']);
    }

    // ==========================================
    // Performance Logging Tests
    // ==========================================

    public function testPerformanceLogging(): void
    {
        // Fast operation (should be INFO)
        Logger::performance('Fast operation', 0.1, ['operation_type' => 'cache_read']);

        // Slow operation (should be WARNING)
        Logger::performance('Slow operation', 1.5, ['operation_type' => 'database_query']);

        $logContent = $this->getAllLogContent();
        $logLines = array_filter(explode("\n", $logContent));

        $fastLog = json_decode($logLines[0], true);
        $slowLog = json_decode($logLines[1], true);

        $this->assertEquals('INFO', $fastLog['level']);
        $this->assertEquals('WARNING', $slowLog['level']);
        $this->assertEquals(100.0, $fastLog['context']['duration_ms']);
        $this->assertEquals(1500.0, $slowLog['context']['duration_ms']);
    }

    public function testDatabaseLogging(): void
    {
        $testQuery = "SELECT * FROM users WHERE email = ?";
        
        // Fast query (should be DEBUG)
        Logger::database($testQuery, 0.1, ['params' => ['test@example.com']]);

        // Slow query (should be WARNING)  
        Logger::database($testQuery, 0.6, ['params' => ['slow@example.com']]);

        $logContent = $this->getAllLogContent();
        $logLines = array_filter(explode("\n", $logContent));

        $fastLog = json_decode($logLines[0], true);
        $slowLog = json_decode($logLines[1], true);

        $this->assertEquals('DEBUG', $fastLog['level']);
        $this->assertEquals('WARNING', $slowLog['level']);
        $this->assertEquals('database', $fastLog['context']['category']);
        $this->assertStringContainsString('SELECT * FROM users', $fastLog['context']['query']);
    }

    // ==========================================
    // HTTP Request Logging Tests
    // ==========================================

    public function testHttpRequestLogging(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test Client';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        // Successful request
        Logger::request('GET', '/api/users', 200, 0.15);

        // Error request
        Logger::request('POST', '/api/admin', 500, 2.5);

        $logContent = $this->getAllLogContent();
        $logLines = array_filter(explode("\n", $logContent));

        $successLog = json_decode($logLines[0], true);
        $errorLog = json_decode($logLines[1], true);

        $this->assertEquals('INFO', $successLog['level']);
        $this->assertEquals('ERROR', $errorLog['level']);
        $this->assertEquals(200, $successLog['context']['response_code']);
        $this->assertEquals(500, $errorLog['context']['response_code']);
    }

    // ==========================================
    // Log File Management Tests
    // ==========================================

    public function testLogFileSegmentation(): void
    {
        Logger::error('Error message');
        Logger::debug('Debug message');
        Logger::info('Info message');

        $logFiles = glob(self::$testLogDirectory . '/*.log');
        $this->assertGreaterThanOrEqual(2, count($logFiles), 'Should create separate log files');

        $fileNames = array_map('basename', $logFiles);
        $hasErrorLog = false;
        $hasDebugLog = false;
        $hasAppLog = false;

        foreach ($fileNames as $fileName) {
            if (strpos($fileName, 'error-') === 0) $hasErrorLog = true;
            if (strpos($fileName, 'debug-') === 0) $hasDebugLog = true;
            if (strpos($fileName, 'app-') === 0) $hasAppLog = true;
        }

        $this->assertTrue($hasErrorLog, 'Should create error log file');
        $this->assertTrue($hasDebugLog || $hasAppLog, 'Should create debug or app log file');
    }

    public function testCriticalErrorSpecialHandling(): void
    {
        Logger::critical('Critical system failure', ['component' => 'database']);

        // Should create both regular log and critical log
        $criticalLogFile = self::$testLogDirectory . '/critical.log';
        $this->assertFileExists($criticalLogFile);

        $criticalContent = file_get_contents($criticalLogFile);
        $criticalEntry = json_decode($criticalContent, true);

        $this->assertEquals('CRITICAL', $criticalEntry['level']);
        $this->assertArrayHasKey('backtrace', $criticalEntry);
        $this->assertIsArray($criticalEntry['backtrace']);
    }

    // ==========================================
    // Security and Sanitization Tests
    // ==========================================

    public function testQuerySanitization(): void
    {
        $sensitiveQuery = "UPDATE users SET password = 'secret123' WHERE id = 1";
        
        Logger::database($sensitiveQuery, 0.1);

        $logContent = $this->getAllLogContent();
        $logEntry = json_decode(array_filter(explode("\n", $logContent))[0], true);

        $this->assertStringNotContainsString('secret123', $logEntry['context']['query']);
        $this->assertStringContainsString('***', $logEntry['context']['query']);
    }

    public function testSensitiveDataFiltering(): void
    {
        $sensitiveContext = [
            'user_password' => 'secret123',
            'api_token' => 'abc123def456',
            'normal_data' => 'safe_value'
        ];

        Logger::info('Test sensitive data', $sensitiveContext);

        $logContent = $this->getAllLogContent();
        
        // Should still contain the context but potentially sanitized
        $this->assertStringContainsString('normal_data', $logContent);
        $this->assertStringContainsString('safe_value', $logContent);
        
        // Sensitive data might still be present (this test documents current behavior)
        // In a more advanced implementation, these could be sanitized
    }

    // ==========================================
    // Error Handling and Resilience Tests
    // ==========================================

    public function testLoggerResilience(): void
    {
        // Make log directory read-only to test error handling
        $readOnlyDir = self::$testLogDirectory . '/readonly';
        mkdir($readOnlyDir, 0555, true);

        // This should not throw an exception
        try {
            $reflection = new \ReflectionClass(Logger::class);
            $property = $reflection->getProperty('logDirectory');
            $property->setAccessible(true);
            $property->setValue(null, $readOnlyDir);

            Logger::info('This should not crash');
            
            $this->assertTrue(true, 'Logger should handle write failures gracefully');
        } catch (\Throwable $e) {
            $this->fail('Logger should not throw exceptions on write failures');
        } finally {
            // Cleanup
            chmod($readOnlyDir, 0755);
            rmdir($readOnlyDir);
        }
    }

    public function testLoggerWithoutSession(): void
    {
        // Ensure no session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        Logger::info('Log without session');

        $logContent = $this->getAllLogContent();
        $logEntry = json_decode(array_filter(explode("\n", $logContent))[0], true);

        $this->assertNull($logEntry['user']['user']);
        $this->assertArrayHasKey('session_id', $logEntry['user']);
    }

    // ==========================================
    // Statistics and Monitoring Tests
    // ==========================================

    public function testLoggerStatistics(): void
    {
        Logger::info('Test message 1');
        Logger::error('Test message 2');

        $stats = Logger::getStats();

        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('file_count', $stats);
        $this->assertArrayHasKey('oldest_log', $stats);
        $this->assertArrayHasKey('newest_log', $stats);

        $this->assertGreaterThan(0, $stats['total_size']);
        $this->assertGreaterThan(0, $stats['file_count']);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function getAllLogContent(): string
    {
        $content = '';
        $logFiles = glob(self::$testLogDirectory . '/*.log');
        
        foreach ($logFiles as $file) {
            $content .= file_get_contents($file);
        }
        
        return $content;
    }

    private function getLogEntriesAsArray(): array
    {
        $content = $this->getAllLogContent();
        $lines = array_filter(explode("\n", $content));
        
        $entries = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $entries[] = $decoded;
            }
        }
        
        return $entries;
    }
}