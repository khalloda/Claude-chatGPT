<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Env;
use Exception;

/**
 * Session Monitor
 * 
 * Provides comprehensive session monitoring, performance tracking,
 * and security analysis for Redis-based session storage.
 */
class SessionMonitor
{
    private static ?SessionMonitor $instance = null;
    private array $metrics = [];
    private array $alerts = [];
    private array $config = [];
    private float $startTime = 0;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize session monitor
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->loadConfig();
        $this->initializeMetrics();
    }
    
    /**
     * Load monitoring configuration
     */
    private function loadConfig(): void
    {
        $this->config = [
            'enabled' => Env::get('SESSION_MONITORING_ENABLED', 'true') === 'true',
            'log_level' => Env::get('SESSION_MONITORING_LOG_LEVEL', 'info'),
            'alert_thresholds' => [
                'high_memory_usage' => (float)Env::get('SESSION_ALERT_MEMORY_THRESHOLD', '0.8'),
                'slow_operation' => (float)Env::get('SESSION_ALERT_SLOW_THRESHOLD', '1.0'), // seconds
                'error_rate' => (float)Env::get('SESSION_ALERT_ERROR_RATE', '0.1'), // 10%
                'concurrent_sessions' => (int)Env::get('SESSION_ALERT_CONCURRENT_LIMIT', '1000')
            ],
            'retention_period' => (int)Env::get('SESSION_METRICS_RETENTION', '86400'), // 24 hours
            'sample_rate' => (float)Env::get('SESSION_MONITORING_SAMPLE_RATE', '1.0') // 100%
        ];
    }
    
    /**
     * Initialize metrics structure
     */
    private function initializeMetrics(): void
    {
        $this->metrics = [
            'operations' => [
                'read' => ['count' => 0, 'total_time' => 0, 'errors' => 0],
                'write' => ['count' => 0, 'total_time' => 0, 'errors' => 0],
                'destroy' => ['count' => 0, 'total_time' => 0, 'errors' => 0],
                'gc' => ['count' => 0, 'total_time' => 0, 'errors' => 0]
            ],
            'sessions' => [
                'active_count' => 0,
                'created_count' => 0,
                'destroyed_count' => 0,
                'expired_count' => 0
            ],
            'performance' => [
                'avg_response_time' => 0,
                'peak_memory_usage' => 0,
                'total_requests' => 0
            ],
            'security' => [
                'hijack_attempts' => 0,
                'invalid_sessions' => 0,
                'regenerations' => 0
            ],
            'redis' => [
                'connection_failures' => 0,
                'timeouts' => 0,
                'memory_usage' => 0
            ]
        ];
    }
    
    /**
     * Start operation timing
     */
    public function startOperation(string $operation): string
    {
        if (!$this->config['enabled'] || !$this->shouldSample()) {
            return '';
        }
        
        $operationId = uniqid($operation . '_', true);
        $this->metrics['_timers'][$operationId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
        
        return $operationId;
    }
    
    /**
     * End operation timing and record metrics
     */
    public function endOperation(string $operationId, bool $success = true, array $metadata = []): void
    {
        if (!$this->config['enabled'] || empty($operationId) || !isset($this->metrics['_timers'][$operationId])) {
            return;
        }
        
        $timer = $this->metrics['_timers'][$operationId];
        $operation = $timer['operation'];
        $duration = microtime(true) - $timer['start_time'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];
        
        // Record operation metrics
        if (isset($this->metrics['operations'][$operation])) {
            $this->metrics['operations'][$operation]['count']++;
            $this->metrics['operations'][$operation]['total_time'] += $duration;
            
            if (!$success) {
                $this->metrics['operations'][$operation]['errors']++;
            }
        }
        
        // Update performance metrics
        $this->metrics['performance']['total_requests']++;
        $totalTime = $this->metrics['operations'][$operation]['total_time'] ?? 0;
        $totalCount = $this->metrics['operations'][$operation]['count'] ?? 1;
        $this->metrics['performance']['avg_response_time'] = $totalTime / $totalCount;
        
        if ($memoryUsed > $this->metrics['performance']['peak_memory_usage']) {
            $this->metrics['performance']['peak_memory_usage'] = $memoryUsed;
        }
        
        // Check for performance alerts
        $this->checkPerformanceAlerts($operation, $duration, $success, $metadata);
        
        // Log detailed operation if needed
        if ($this->config['log_level'] === 'debug') {
            Logger::debug("Session operation completed", [
                'operation' => $operation,
                'duration' => round($duration * 1000, 2) . 'ms',
                'memory_used' => $this->formatBytes($memoryUsed),
                'success' => $success,
                'metadata' => $metadata
            ]);
        }
        
        unset($this->metrics['_timers'][$operationId]);
    }
    
    /**
     * Record session event
     */
    public function recordSessionEvent(string $event, array $data = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }
        
        switch ($event) {
            case 'session_created':
                $this->metrics['sessions']['created_count']++;
                $this->metrics['sessions']['active_count']++;
                break;
                
            case 'session_destroyed':
                $this->metrics['sessions']['destroyed_count']++;
                $this->metrics['sessions']['active_count'] = max(0, $this->metrics['sessions']['active_count'] - 1);
                break;
                
            case 'session_expired':
                $this->metrics['sessions']['expired_count']++;
                $this->metrics['sessions']['active_count'] = max(0, $this->metrics['sessions']['active_count'] - 1);
                break;
                
            case 'session_hijack_attempt':
                $this->metrics['security']['hijack_attempts']++;
                $this->createAlert('security', 'Session hijack attempt detected', $data);
                break;
                
            case 'invalid_session':
                $this->metrics['security']['invalid_sessions']++;
                break;
                
            case 'session_regenerated':
                $this->metrics['security']['regenerations']++;
                break;
                
            case 'redis_connection_failure':
                $this->metrics['redis']['connection_failures']++;
                $this->createAlert('redis', 'Redis connection failure', $data);
                break;
                
            case 'redis_timeout':
                $this->metrics['redis']['timeouts']++;
                break;
        }
        
        // Log security events
        if (strpos($event, 'hijack') !== false || strpos($event, 'invalid') !== false) {
            Logger::warning("Security event: {$event}", $data);
        }
    }
    
    /**
     * Update Redis metrics
     */
    public function updateRedisMetrics(array $redisStats): void
    {
        if (!$this->config['enabled']) {
            return;
        }
        
        if (isset($redisStats['redis_info']['used_memory'])) {
            $this->metrics['redis']['memory_usage'] = $redisStats['redis_info']['used_memory'];
        }
        
        // Check memory usage alerts
        if (isset($redisStats['redis_info']['used_memory'], $redisStats['redis_info']['maxmemory'])) {
            $memoryUsage = $redisStats['redis_info']['used_memory'] / $redisStats['redis_info']['maxmemory'];
            if ($memoryUsage > $this->config['alert_thresholds']['high_memory_usage']) {
                $this->createAlert('redis', 'High memory usage detected', [
                    'usage_percent' => round($memoryUsage * 100, 2),
                    'used_memory' => $this->formatBytes($redisStats['redis_info']['used_memory']),
                    'max_memory' => $this->formatBytes($redisStats['redis_info']['maxmemory'])
                ]);
            }
        }
    }
    
    /**
     * Check for performance alerts
     */
    private function checkPerformanceAlerts(string $operation, float $duration, bool $success, array $metadata): void
    {
        // Slow operation alert
        if ($duration > $this->config['alert_thresholds']['slow_operation']) {
            $this->createAlert('performance', 'Slow session operation detected', [
                'operation' => $operation,
                'duration' => round($duration * 1000, 2) . 'ms',
                'metadata' => $metadata
            ]);
        }
        
        // Error rate alert
        if (isset($this->metrics['operations'][$operation])) {
            $stats = $this->metrics['operations'][$operation];
            $errorRate = $stats['count'] > 0 ? $stats['errors'] / $stats['count'] : 0;
            
            if ($errorRate > $this->config['alert_thresholds']['error_rate'] && $stats['count'] >= 10) {
                $this->createAlert('performance', 'High error rate detected', [
                    'operation' => $operation,
                    'error_rate' => round($errorRate * 100, 2) . '%',
                    'total_operations' => $stats['count'],
                    'errors' => $stats['errors']
                ]);
            }
        }
        
        // Concurrent sessions alert
        if ($this->metrics['sessions']['active_count'] > $this->config['alert_thresholds']['concurrent_sessions']) {
            $this->createAlert('sessions', 'High concurrent sessions detected', [
                'active_sessions' => $this->metrics['sessions']['active_count'],
                'threshold' => $this->config['alert_thresholds']['concurrent_sessions']
            ]);
        }
    }
    
    /**
     * Create alert
     */
    private function createAlert(string $category, string $message, array $data = []): void
    {
        $alert = [
            'id' => uniqid('alert_', true),
            'timestamp' => time(),
            'category' => $category,
            'message' => $message,
            'data' => $data,
            'severity' => $this->getAlertSeverity($category, $message)
        ];
        
        $this->alerts[] = $alert;
        
        // Keep only recent alerts
        $this->alerts = array_filter($this->alerts, function($alert) {
            return (time() - $alert['timestamp']) < $this->config['retention_period'];
        });
        
        // Log alert
        $logMethod = $alert['severity'] === 'critical' ? 'error' : 'warning';
        Logger::$logMethod("Session monitoring alert: {$message}", [
            'category' => $category,
            'severity' => $alert['severity'],
            'data' => $data
        ]);
    }
    
    /**
     * Determine alert severity
     */
    private function getAlertSeverity(string $category, string $message): string
    {
        if (strpos($message, 'hijack') !== false || strpos($message, 'failure') !== false) {
            return 'critical';
        }
        
        if (strpos($message, 'high') !== false || strpos($message, 'slow') !== false) {
            return 'warning';
        }
        
        return 'info';
    }
    
    /**
     * Get current metrics
     */
    public function getMetrics(): array
    {
        $metrics = $this->metrics;
        
        // Calculate derived metrics
        $metrics['derived'] = [
            'uptime' => time() - (int)$this->startTime,
            'avg_operations_per_minute' => $this->calculateOperationsPerMinute(),
            'error_rates' => $this->calculateErrorRates(),
            'memory_efficiency' => $this->calculateMemoryEfficiency()
        ];
        
        // Add alerts
        $metrics['alerts'] = [
            'active_alerts' => array_filter($this->alerts, function($alert) {
                return (time() - $alert['timestamp']) < 3600; // Last hour
            }),
            'total_alerts' => count($this->alerts)
        ];
        
        // Clean up internal timers from output
        unset($metrics['_timers']);
        
        return $metrics;
    }
    
    /**
     * Calculate operations per minute
     */
    private function calculateOperationsPerMinute(): array
    {
        $uptime = microtime(true) - $this->startTime;
        $minutes = max($uptime / 60, 1);
        
        $rates = [];
        foreach ($this->metrics['operations'] as $operation => $stats) {
            $rates[$operation] = round($stats['count'] / $minutes, 2);
        }
        
        return $rates;
    }
    
    /**
     * Calculate error rates
     */
    private function calculateErrorRates(): array
    {
        $rates = [];
        foreach ($this->metrics['operations'] as $operation => $stats) {
            $rates[$operation] = $stats['count'] > 0 ? 
                round(($stats['errors'] / $stats['count']) * 100, 2) : 0;
        }
        
        return $rates;
    }
    
    /**
     * Calculate memory efficiency
     */
    private function calculateMemoryEfficiency(): array
    {
        $totalOperations = array_sum(array_column($this->metrics['operations'], 'count'));
        $peakMemory = $this->metrics['performance']['peak_memory_usage'];
        
        return [
            'memory_per_operation' => $totalOperations > 0 ? 
                $this->formatBytes($peakMemory / $totalOperations) : '0 B',
            'peak_memory' => $this->formatBytes($peakMemory)
        ];
    }
    
    /**
     * Get performance report
     */
    public function getPerformanceReport(): array
    {
        $metrics = $this->getMetrics();
        
        return [
            'summary' => [
                'total_operations' => array_sum(array_column($metrics['operations'], 'count')),
                'total_errors' => array_sum(array_column($metrics['operations'], 'errors')),
                'avg_response_time' => round($metrics['performance']['avg_response_time'] * 1000, 2) . 'ms',
                'active_sessions' => $metrics['sessions']['active_count'],
                'uptime' => $this->formatDuration($metrics['derived']['uptime'])
            ],
            'operations' => $metrics['operations'],
            'performance' => $metrics['performance'],
            'alerts' => $metrics['alerts']['active_alerts']
        ];
    }
    
    /**
     * Get security report
     */
    public function getSecurityReport(): array
    {
        return [
            'security_events' => $this->metrics['security'],
            'security_alerts' => array_filter($this->alerts, function($alert) {
                return $alert['category'] === 'security' && 
                       (time() - $alert['timestamp']) < 86400; // Last 24 hours
            }),
            'recommendations' => $this->getSecurityRecommendations()
        ];
    }
    
    /**
     * Get security recommendations
     */
    private function getSecurityRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->metrics['security']['hijack_attempts'] > 0) {
            $recommendations[] = 'Consider implementing stricter session validation';
            $recommendations[] = 'Review IP-based session binding configuration';
        }
        
        if ($this->metrics['security']['invalid_sessions'] > 10) {
            $recommendations[] = 'High number of invalid sessions detected - review session cleanup';
        }
        
        if ($this->metrics['sessions']['active_count'] > 1000) {
            $recommendations[] = 'Consider implementing session limiting per user';
        }
        
        return $recommendations;
    }
    
    /**
     * Export metrics for external monitoring
     */
    public function exportMetrics(string $format = 'json'): string
    {
        $metrics = $this->getMetrics();
        
        switch ($format) {
            case 'prometheus':
                return $this->formatPrometheus($metrics);
            case 'csv':
                return $this->formatCsv($metrics);
            default:
                return json_encode($metrics, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Format metrics for Prometheus
     */
    private function formatPrometheus(array $metrics): string
    {
        $output = [];
        
        foreach ($metrics['operations'] as $operation => $stats) {
            $output[] = "session_operations_total{operation=\"{$operation}\"} {$stats['count']}";
            $output[] = "session_operations_errors_total{operation=\"{$operation}\"} {$stats['errors']}";
            $output[] = "session_operations_duration_seconds{operation=\"{$operation}\"} {$stats['total_time']}";
        }
        
        $output[] = "session_active_sessions {$metrics['sessions']['active_count']}";
        $output[] = "session_memory_peak_bytes {$metrics['performance']['peak_memory_usage']}";
        
        return implode("\n", $output);
    }
    
    /**
     * Format metrics as CSV
     */
    private function formatCsv(array $metrics): string
    {
        $csv = "timestamp,metric,value\n";
        $timestamp = time();
        
        foreach ($metrics['operations'] as $operation => $stats) {
            $csv .= "{$timestamp},operations_{$operation}_count,{$stats['count']}\n";
            $csv .= "{$timestamp},operations_{$operation}_errors,{$stats['errors']}\n";
        }
        
        $csv .= "{$timestamp},active_sessions,{$metrics['sessions']['active_count']}\n";
        $csv .= "{$timestamp},peak_memory,{$metrics['performance']['peak_memory_usage']}\n";
        
        return $csv;
    }
    
    /**
     * Reset metrics
     */
    public function resetMetrics(): void
    {
        $this->initializeMetrics();
        $this->alerts = [];
        $this->startTime = microtime(true);
        
        Logger::info('Session monitoring metrics reset');
    }
    
    /**
     * Check if operation should be sampled
     */
    private function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config['sample_rate'];
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
     * Format duration for human reading
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . ' minutes';
        } else {
            return round($seconds / 3600, 1) . ' hours';
        }
    }
}
