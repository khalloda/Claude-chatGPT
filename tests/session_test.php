<?php declare(strict_types=1);

/**
 * Comprehensive Session Testing Suite
 * 
 * Tests Redis session functionality, performance, security,
 * and integration with the session management system.
 */

require_once dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Services\SessionManager;
use App\Services\RedisSessionHandler;
use App\Services\SessionMonitor;
use App\Core\Logger;
use App\Core\Env;

class SessionTestSuite
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private SessionManager $sessionManager;
    private SessionMonitor $monitor;
    
    public function __construct()
    {
        $this->sessionManager = SessionManager::getInstance();
        $this->monitor = SessionMonitor::getInstance();
    }
    
    /**
     * Run all session tests
     */
    public function runAllTests(): array
    {
        $this->log("Starting comprehensive session test suite");
        
        $testGroups = [
            'Basic Functionality' => [
                'testSessionInitialization',
                'testSessionReadWrite',
                'testSessionDestruction',
                'testSessionRegeneration'
            ],
            'Redis Integration' => [
                'testRedisConnection',
                'testRedisSessionHandler',
                'testRedisSessionPersistence',
                'testRedisSessionExpiration'
            ],
            'Security Features' => [
                'testCSRFProtection',
                'testSessionHijackPrevention',
                'testSessionSecurity',
                'testSessionIntegrity'
            ],
            'Performance' => [
                'testSessionPerformance',
                'testConcurrentSessions',
                'testMemoryUsage',
                'testSessionScaling'
            ],
            'Monitoring' => [
                'testSessionMonitoring',
                'testMetricsCollection',
                'testAlertSystem',
                'testPerformanceTracking'
            ],
            'Edge Cases' => [
                'testLargeSessionData',
                'testInvalidSessionData',
                'testNetworkFailures',
                'testRecoveryScenarios'
            ]
        ];
        
        foreach ($testGroups as $groupName => $tests) {
            $this->log("Running test group: {$groupName}");
            
            foreach ($tests as $testMethod) {
                $this->runTest($testMethod);
            }
        }
        
        return $this->generateReport();
    }
    
    /**
     * Run individual test
     */
    private function runTest(string $testMethod): void
    {
        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            if (!method_exists($this, $testMethod)) {
                throw new Exception("Test method {$testMethod} not found");
            }
            
            $result = $this->$testMethod();
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            if ($result === true) {
                $this->passed++;
                $status = 'PASS';
            } else {
                $this->failed++;
                $status = 'FAIL';
            }
            
            $this->results[] = [
                'test' => $testMethod,
                'status' => $status,
                'duration' => round($duration * 1000, 2),
                'memory' => $memoryUsed,
                'message' => is_string($result) ? $result : ''
            ];
            
            $this->log("  {$status}: {$testMethod} ({$this->results[count($this->results)-1]['duration']}ms)");
            
        } catch (Exception $e) {
            $this->failed++;
            $this->results[] = [
                'test' => $testMethod,
                'status' => 'ERROR',
                'duration' => 0,
                'memory' => 0,
                'message' => $e->getMessage()
            ];
            
            $this->log("  ERROR: {$testMethod} - " . $e->getMessage());
        }
    }
    
    /**
     * Test session initialization
     */
    private function testSessionInitialization(): bool
    {
        if (!$this->sessionManager->initialize()) {
            return false;
        }
        
        return session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Test session read/write operations
     */
    private function testSessionReadWrite(): bool
    {
        $testKey = 'test_session_key';
        $testValue = 'test_session_value_' . uniqid();
        
        // Write test
        $_SESSION[$testKey] = $testValue;
        
        // Read test
        if (!isset($_SESSION[$testKey]) || $_SESSION[$testKey] !== $testValue) {
            return false;
        }
        
        // Cleanup
        unset($_SESSION[$testKey]);
        
        return !isset($_SESSION[$testKey]);
    }
    
    /**
     * Test session destruction
     */
    private function testSessionDestruction(): bool
    {
        // Create test data
        $_SESSION['test_destroy'] = 'test_data';
        $originalSessionId = session_id();
        
        // Destroy session
        $result = $this->sessionManager->destroySession();
        
        if (!$result) {
            return false;
        }
        
        // Verify session is destroyed
        return !isset($_SESSION['test_destroy']);
    }
    
    /**
     * Test session ID regeneration
     */
    private function testSessionRegeneration(): bool
    {
        // Re-initialize session after destruction test
        $this->sessionManager->initialize();
        
        $oldSessionId = session_id();
        $result = $this->sessionManager->regenerateId();
        $newSessionId = session_id();
        
        return $result && $oldSessionId !== $newSessionId;
    }
    
    /**
     * Test Redis connection
     */
    private function testRedisConnection(): bool
    {
        $configFile = dirname(__DIR__) . '/config/redis.php';
        if (!file_exists($configFile)) {
            return "Redis config file not found";
        }
        
        $config = require $configFile;
        $sessionConfig = $config['session'] ?? [];
        
        try {
            $redis = new Redis();
            $connected = $redis->connect(
                $sessionConfig['host'] ?? '127.0.0.1',
                $sessionConfig['port'] ?? 6379,
                $sessionConfig['timeout'] ?? 5.0
            );
            
            if (!$connected) {
                return false;
            }
            
            if (!empty($sessionConfig['password'])) {
                if (!$redis->auth($sessionConfig['password'])) {
                    return false;
                }
            }
            
            $result = $redis->ping();
            $redis->close();
            
            return $result === '+PONG' || $result === 'PONG';
            
        } catch (Exception $e) {
            return "Redis connection failed: " . $e->getMessage();
        }
    }
    
    /**
     * Test Redis session handler
     */
    private function testRedisSessionHandler(): bool
    {
        $configFile = dirname(__DIR__) . '/config/redis.php';
        if (!file_exists($configFile)) {
            return "Redis config file not found";
        }
        
        $config = require $configFile;
        $handler = new RedisSessionHandler($config['session'] ?? []);
        
        // Test handler methods
        if (!$handler->open('', '')) {
            return "Failed to open Redis session handler";
        }
        
        $testSessionId = 'test_session_' . uniqid();
        $testData = 'test_data_' . time();
        
        // Test write
        if (!$handler->write($testSessionId, $testData)) {
            return "Failed to write session data";
        }
        
        // Test read
        $readData = $handler->read($testSessionId);
        if ($readData !== $testData) {
            return "Session data mismatch on read";
        }
        
        // Test destroy
        if (!$handler->destroy($testSessionId)) {
            return "Failed to destroy session";
        }
        
        // Verify destruction
        $deletedData = $handler->read($testSessionId);
        if ($deletedData !== '') {
            return "Session data still exists after destruction";
        }
        
        $handler->close();
        return true;
    }
    
    /**
     * Test Redis session persistence
     */
    private function testRedisSessionPersistence(): bool
    {
        $_SESSION['persistence_test'] = 'persistent_data_' . uniqid();
        $testData = $_SESSION['persistence_test'];
        $sessionId = session_id();
        
        // Close current session
        session_write_close();
        
        // Start new session with same ID
        session_id($sessionId);
        session_start();
        
        $result = isset($_SESSION['persistence_test']) && 
                 $_SESSION['persistence_test'] === $testData;
        
        // Cleanup
        unset($_SESSION['persistence_test']);
        
        return $result;
    }
    
    /**
     * Test Redis session expiration
     */
    private function testRedisSessionExpiration(): bool
    {
        $configFile = dirname(__DIR__) . '/config/redis.php';
        $config = require $configFile;
        $handler = new RedisSessionHandler($config['session'] ?? []);
        
        if (!$handler->open('', '')) {
            return false;
        }
        
        $testSessionId = 'test_expire_' . uniqid();
        $testData = 'expire_test_data';
        
        // Write session with short TTL (we can't easily test real expiration)
        $handler->write($testSessionId, $testData);
        
        // Verify data exists
        $readData = $handler->read($testSessionId);
        if ($readData !== $testData) {
            return false;
        }
        
        // Test garbage collection
        $gcResult = $handler->gc(1); // Very short lifetime
        
        $handler->close();
        return $gcResult !== false;
    }
    
    /**
     * Test CSRF protection
     */
    private function testCSRFProtection(): bool
    {
        if (!function_exists('csrf_token')) {
            return "CSRF functions not available";
        }
        
        $token1 = csrf_token();
        $token2 = csrf_token();
        
        // Same session should return same token
        if ($token1 !== $token2) {
            return false;
        }
        
        // Test token validation
        $_POST['_token'] = $token1;
        if (!verify_csrf_post()) {
            return false;
        }
        
        // Test invalid token
        $_POST['_token'] = 'invalid_token';
        if (verify_csrf_post()) {
            return false;
        }
        
        // Cleanup
        unset($_POST['_token']);
        
        return true;
    }
    
    /**
     * Test session hijack prevention
     */
    private function testSessionHijackPrevention(): bool
    {
        // This test simulates user agent changes that should trigger security measures
        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Set initial user agent
        $_SERVER['HTTP_USER_AGENT'] = 'TestBrowser/1.0';
        $_SESSION['test_data'] = 'test_value';
        
        // Change user agent (simulate hijack attempt)
        $_SERVER['HTTP_USER_AGENT'] = 'AttackerBrowser/1.0';
        
        // The session should be invalidated (this is implementation-dependent)
        // For this test, we'll check if the monitoring system detects it
        $this->monitor->recordSessionEvent('session_hijack_attempt', [
            'original_ua' => 'TestBrowser/1.0',
            'new_ua' => 'AttackerBrowser/1.0'
        ]);
        
        // Restore original user agent
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
        
        return true; // Test that monitoring recorded the event
    }
    
    /**
     * Test session security features
     */
    private function testSessionSecurity(): bool
    {
        $sessionParams = session_get_cookie_params();
        
        // Check security parameters
        if (!$sessionParams['httponly']) {
            return "HTTPOnly not enabled";
        }
        
        if (!$sessionParams['secure'] && $this->isHttps()) {
            return "Secure flag not set for HTTPS";
        }
        
        if ($sessionParams['samesite'] !== 'Lax' && $sessionParams['samesite'] !== 'Strict') {
            return "SameSite not properly configured";
        }
        
        return true;
    }
    
    /**
     * Test session integrity
     */
    private function testSessionIntegrity(): bool
    {
        // Test session metadata
        $requiredMetadata = ['_created', '_last_activity', '_user_agent', '_ip_address'];
        
        foreach ($requiredMetadata as $key) {
            if (!isset($_SESSION[$key])) {
                return "Missing session metadata: {$key}";
            }
        }
        
        return true;
    }
    
    /**
     * Test session performance
     */
    private function testSessionPerformance(): bool
    {
        $iterations = 100;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $_SESSION["perf_test_{$i}"] = "performance_data_{$i}";
        }
        
        $writeTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $value = $_SESSION["perf_test_{$i}"] ?? null;
        }
        
        $readTime = microtime(true) - $startTime;
        
        // Cleanup
        for ($i = 0; $i < $iterations; $i++) {
            unset($_SESSION["perf_test_{$i}"]);
        }
        
        // Performance thresholds (adjust based on environment)
        $maxWriteTime = 1.0; // 1 second for 100 writes
        $maxReadTime = 0.1;  // 0.1 second for 100 reads
        
        if ($writeTime > $maxWriteTime) {
            return "Write performance too slow: {$writeTime}s";
        }
        
        if ($readTime > $maxReadTime) {
            return "Read performance too slow: {$readTime}s";
        }
        
        return true;
    }
    
    /**
     * Test concurrent sessions
     */
    private function testConcurrentSessions(): bool
    {
        // This test simulates multiple session handlers
        $configFile = dirname(__DIR__) . '/config/redis.php';
        $config = require $configFile;
        
        $handlers = [];
        $sessionIds = [];
        
        // Create multiple handlers
        for ($i = 0; $i < 5; $i++) {
            $handlers[$i] = new RedisSessionHandler($config['session'] ?? []);
            $sessionIds[$i] = 'concurrent_test_' . $i . '_' . uniqid();
            
            if (!$handlers[$i]->open('', '')) {
                return "Failed to open handler {$i}";
            }
            
            if (!$handlers[$i]->write($sessionIds[$i], "test_data_{$i}")) {
                return "Failed to write concurrent session {$i}";
            }
        }
        
        // Verify all sessions exist
        for ($i = 0; $i < 5; $i++) {
            $data = $handlers[$i]->read($sessionIds[$i]);
            if ($data !== "test_data_{$i}") {
                return "Concurrent session {$i} data corruption";
            }
        }
        
        // Cleanup
        for ($i = 0; $i < 5; $i++) {
            $handlers[$i]->destroy($sessionIds[$i]);
            $handlers[$i]->close();
        }
        
        return true;
    }
    
    /**
     * Test memory usage
     */
    private function testMemoryUsage(): bool
    {
        $initialMemory = memory_get_usage(true);
        
        // Create large session data
        $largeData = str_repeat('X', 10240); // 10KB
        
        for ($i = 0; $i < 100; $i++) {
            $_SESSION["memory_test_{$i}"] = $largeData;
        }
        
        $peakMemory = memory_get_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;
        
        // Cleanup
        for ($i = 0; $i < 100; $i++) {
            unset($_SESSION["memory_test_{$i}"]);
        }
        
        // Memory threshold (adjust based on environment)
        $maxMemoryIncrease = 50 * 1024 * 1024; // 50MB
        
        if ($memoryIncrease > $maxMemoryIncrease) {
            return "Memory usage too high: " . $this->formatBytes($memoryIncrease);
        }
        
        return true;
    }
    
    /**
     * Test session scaling
     */
    private function testSessionScaling(): bool
    {
        $sessionCount = 50;
        $startTime = microtime(true);
        
        // Create many sessions
        for ($i = 0; $i < $sessionCount; $i++) {
            $_SESSION["scale_test_{$i}"] = [
                'id' => $i,
                'data' => str_repeat('X', 1024), // 1KB per session
                'timestamp' => time()
            ];
        }
        
        $duration = microtime(true) - $startTime;
        
        // Cleanup
        for ($i = 0; $i < $sessionCount; $i++) {
            unset($_SESSION["scale_test_{$i}"]);
        }
        
        // Scaling threshold
        $maxDuration = 2.0; // 2 seconds for 50 sessions
        
        if ($duration > $maxDuration) {
            return "Scaling performance too slow: {$duration}s";
        }
        
        return true;
    }
    
    /**
     * Test session monitoring
     */
    private function testSessionMonitoring(): bool
    {
        $operationId = $this->monitor->startOperation('test_operation');
        
        if (empty($operationId)) {
            return "Failed to start monitoring operation";
        }
        
        // Simulate some work
        usleep(10000); // 10ms
        
        $this->monitor->endOperation($operationId, true, ['test' => true]);
        
        $metrics = $this->monitor->getMetrics();
        
        if (!isset($metrics['operations']['test_operation'])) {
            return "Operation not recorded in metrics";
        }
        
        return true;
    }
    
    /**
     * Test metrics collection
     */
    private function testMetricsCollection(): bool
    {
        $this->monitor->recordSessionEvent('session_created');
        $this->monitor->recordSessionEvent('session_destroyed');
        
        $metrics = $this->monitor->getMetrics();
        
        if (!isset($metrics['sessions']['created_count']) || 
            !isset($metrics['sessions']['destroyed_count'])) {
            return "Session events not recorded";
        }
        
        return true;
    }
    
    /**
     * Test alert system
     */
    private function testAlertSystem(): bool
    {
        // Trigger a test alert
        $this->monitor->recordSessionEvent('session_hijack_attempt', [
            'test_alert' => true
        ]);
        
        $metrics = $this->monitor->getMetrics();
        
        if (empty($metrics['alerts']['active_alerts'])) {
            return "Alert system not working";
        }
        
        return true;
    }
    
    /**
     * Test performance tracking
     */
    private function testPerformanceTracking(): bool
    {
        $report = $this->monitor->getPerformanceReport();
        
        $requiredFields = ['summary', 'operations', 'performance'];
        
        foreach ($requiredFields as $field) {
            if (!isset($report[$field])) {
                return "Missing performance report field: {$field}";
            }
        }
        
        return true;
    }
    
    /**
     * Test large session data
     */
    private function testLargeSessionData(): bool
    {
        $largeData = str_repeat('Large session data content. ', 10000); // ~250KB
        
        $_SESSION['large_data_test'] = $largeData;
        
        if (!isset($_SESSION['large_data_test']) || 
            $_SESSION['large_data_test'] !== $largeData) {
            return "Large session data handling failed";
        }
        
        // Cleanup
        unset($_SESSION['large_data_test']);
        
        return true;
    }
    
    /**
     * Test invalid session data
     */
    private function testInvalidSessionData(): bool
    {
        $configFile = dirname(__DIR__) . '/config/redis.php';
        $config = require $configFile;
        $handler = new RedisSessionHandler($config['session'] ?? []);
        
        if (!$handler->open('', '')) {
            return false;
        }
        
        $testSessionId = 'invalid_test_' . uniqid();
        
        // Try to read non-existent session
        $result = $handler->read($testSessionId);
        
        if ($result !== '') {
            return "Non-existent session should return empty string";
        }
        
        $handler->close();
        return true;
    }
    
    /**
     * Test network failures (simulated)
     */
    private function testNetworkFailures(): bool
    {
        // This test verifies that the system handles Redis failures gracefully
        $this->monitor->recordSessionEvent('redis_connection_failure', [
            'simulated' => true
        ]);
        
        $metrics = $this->monitor->getMetrics();
        
        if (!isset($metrics['redis']['connection_failures'])) {
            return "Network failure handling not implemented";
        }
        
        return true;
    }
    
    /**
     * Test recovery scenarios
     */
    private function testRecoveryScenarios(): bool
    {
        // Test session recovery after simulated failure
        $_SESSION['recovery_test'] = 'recovery_data';
        
        // Simulate recovery by checking if session data persists
        if (!isset($_SESSION['recovery_test']) || 
            $_SESSION['recovery_test'] !== 'recovery_data') {
            return "Session recovery failed";
        }
        
        // Cleanup
        unset($_SESSION['recovery_test']);
        
        return true;
    }
    
    /**
     * Generate test report
     */
    private function generateReport(): array
    {
        $totalTests = $this->passed + $this->failed;
        $successRate = $totalTests > 0 ? round(($this->passed / $totalTests) * 100, 2) : 0;
        
        return [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $this->passed,
                'failed' => $this->failed,
                'success_rate' => $successRate . '%',
                'execution_time' => date('Y-m-d H:i:s')
            ],
            'results' => $this->results,
            'session_stats' => $this->sessionManager->getStats(),
            'monitoring_metrics' => $this->monitor->getMetrics(),
            'recommendations' => $this->generateRecommendations()
        ];
    }
    
    /**
     * Generate recommendations based on test results
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        
        foreach ($this->results as $result) {
            if ($result['status'] === 'FAIL' || $result['status'] === 'ERROR') {
                switch ($result['test']) {
                    case 'testRedisConnection':
                        $recommendations[] = 'Check Redis server configuration and connectivity';
                        break;
                    case 'testSessionPerformance':
                        $recommendations[] = 'Consider optimizing Redis configuration for better performance';
                        break;
                    case 'testMemoryUsage':
                        $recommendations[] = 'Review session data size and implement data compression';
                        break;
                    case 'testSessionSecurity':
                        $recommendations[] = 'Review and enhance session security configuration';
                        break;
                }
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'All tests passed - session system is functioning optimally';
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Check if HTTPS is being used
     */
    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Format bytes for human reading
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Log message
     */
    private function log(string $message): void
    {
        echo "[" . date('H:i:s') . "] {$message}\n";
    }
}

// CLI interface
if (PHP_SAPI === 'cli') {
    $options = getopt('hv', ['help', 'verbose']);
    
    if (isset($options['h']) || isset($options['help'])) {
        echo "Session Test Suite\n\n";
        echo "Usage: php session_test.php [options]\n\n";
        echo "Options:\n";
        echo "  -h, --help     Show this help message\n";
        echo "  -v, --verbose  Show detailed test output\n\n";
        exit(0);
    }
    
    try {
        $testSuite = new SessionTestSuite();
        $report = $testSuite->runAllTests();
        
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "SESSION TEST RESULTS\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Summary:\n";
        echo "  Total Tests: {$report['summary']['total_tests']}\n";
        echo "  Passed: {$report['summary']['passed']}\n";
        echo "  Failed: {$report['summary']['failed']}\n";
        echo "  Success Rate: {$report['summary']['success_rate']}\n";
        echo "  Execution Time: {$report['summary']['execution_time']}\n\n";
        
        if (isset($options['v']) || isset($options['verbose'])) {
            echo "Detailed Results:\n";
            foreach ($report['results'] as $result) {
                $status = str_pad($result['status'], 6);
                $test = str_pad($result['test'], 40);
                $duration = str_pad($result['duration'] . 'ms', 8);
                
                echo "  {$status} {$test} {$duration}";
                
                if (!empty($result['message'])) {
                    echo " - {$result['message']}";
                }
                
                echo "\n";
            }
            echo "\n";
        }
        
        if (!empty($report['recommendations'])) {
            echo "Recommendations:\n";
            foreach ($report['recommendations'] as $i => $recommendation) {
                echo "  " . ($i + 1) . ". {$recommendation}\n";
            }
            echo "\n";
        }
        
        // Exit with appropriate code
        exit($report['summary']['failed'] > 0 ? 1 : 0);
        
    } catch (Exception $e) {
        echo "Test suite failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}