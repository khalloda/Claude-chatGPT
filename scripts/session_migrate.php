<?php declare(strict_types=1);

/**
 * Session Migration Utility
 * 
 * Migrates sessions from file-based storage to Redis backend
 * with comprehensive validation and rollback capabilities.
 */

require_once dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Services\SessionManager;
use App\Services\RedisSessionHandler;
use App\Core\Logger;
use App\Core\Env;

class SessionMigrator
{
    private array $stats = [
        'total_files' => 0,
        'migrated' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    private bool $dryRun = false;
    private bool $backup = true;
    private string $backupDir = '';
    private ?RedisSessionHandler $redisHandler = null;
    
    public function __construct(bool $dryRun = false, bool $backup = true)
    {
        $this->dryRun = $dryRun;
        $this->backup = $backup;
        $this->backupDir = '/tmp/session_backup_' . date('Y-m-d_H-i-s');
        
        if ($this->backup && !$this->dryRun) {
            if (!mkdir($this->backupDir, 0700, true)) {
                throw new Exception("Failed to create backup directory: {$this->backupDir}");
            }
        }
    }
    
    /**
     * Run migration process
     */
    public function migrate(string $sessionPath = null): array
    {
        $this->log("Starting session migration" . ($this->dryRun ? " (DRY RUN)" : ""));
        
        try {
            // Get session path
            $sessionPath = $sessionPath ?? $this->getSessionPath();
            $this->log("Session path: {$sessionPath}");
            
            // Initialize Redis handler
            $this->initializeRedisHandler();
            
            // Validate Redis connection
            $this->validateRedisConnection();
            
            // Get session files
            $sessionFiles = $this->getSessionFiles($sessionPath);
            $this->stats['total_files'] = count($sessionFiles);
            $this->log("Found {$this->stats['total_files']} session files");
            
            // Process each session file
            foreach ($sessionFiles as $file) {
                $this->processSessionFile($file);
            }
            
            // Generate migration report
            $report = $this->generateReport();
            $this->log("Migration completed: {$this->stats['migrated']} migrated, {$this->stats['failed']} failed, {$this->stats['skipped']} skipped");
            
            return $report;
            
        } catch (Exception $e) {
            $this->stats['errors'][] = $e->getMessage();
            $this->log("Migration failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Get current session save path
     */
    private function getSessionPath(): string
    {
        $path = session_save_path();
        
        if (empty($path)) {
            $path = sys_get_temp_dir();
        }
        
        if (!is_dir($path)) {
            throw new Exception("Session path does not exist: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new Exception("Session path is not readable: {$path}");
        }
        
        return $path;
    }
    
    /**
     * Initialize Redis session handler
     */
    private function initializeRedisHandler(): void
    {
        // Load Redis configuration
        $configFile = dirname(__DIR__) . '/config/redis.php';
        $config = file_exists($configFile) ? require $configFile : [];
        
        $sessionConfig = [
            'host' => $config['session']['host'] ?? Env::get('REDIS_SESSION_HOST', '127.0.0.1'),
            'port' => $config['session']['port'] ?? (int)Env::get('REDIS_SESSION_PORT', '6379'),
            'password' => $config['session']['password'] ?? Env::get('REDIS_SESSION_PASSWORD', null),
            'database' => $config['session']['database'] ?? (int)Env::get('REDIS_SESSION_DATABASE', '1'),
            'prefix' => $config['session']['prefix'] ?? Env::get('REDIS_SESSION_PREFIX', 'sess:'),
        ];
        
        $this->redisHandler = new RedisSessionHandler($sessionConfig);
        $this->log("Redis session handler initialized");
    }
    
    /**
     * Validate Redis connection
     */
    private function validateRedisConnection(): void
    {
        $health = $this->redisHandler->healthCheck();
        
        if ($health['status'] !== 'healthy') {
            throw new Exception("Redis connection is not healthy: " . implode(', ', $health['errors']));
        }
        
        $this->log("Redis connection validated successfully");
    }
    
    /**
     * Get session files from directory
     */
    private function getSessionFiles(string $sessionPath): array
    {
        $pattern = $sessionPath . '/sess_*';
        $files = glob($pattern);
        
        if ($files === false) {
            throw new Exception("Failed to read session files from: {$sessionPath}");
        }
        
        // Filter out invalid files
        $validFiles = [];
        foreach ($files as $file) {
            if (is_file($file) && is_readable($file)) {
                $validFiles[] = $file;
            } else {
                $this->log("Skipping invalid file: {$file}", 'warning');
                $this->stats['skipped']++;
            }
        }
        
        return $validFiles;
    }
    
    /**
     * Process individual session file
     */
    private function processSessionFile(string $filePath): void
    {
        try {
            $fileName = basename($filePath);
            
            // Extract session ID from filename
            if (!preg_match('/^sess_(.+)$/', $fileName, $matches)) {
                $this->stats['skipped']++;
                $this->log("Invalid session filename format: {$fileName}", 'warning');
                return;
            }
            
            $sessionId = $matches[1];
            
            // Validate session ID
            if (!$this->isValidSessionId($sessionId)) {
                $this->stats['skipped']++;
                $this->log("Invalid session ID: {$sessionId}", 'warning');
                return;
            }
            
            // Read session data
            $sessionData = file_get_contents($filePath);
            if ($sessionData === false) {
                $this->stats['failed']++;
                $this->stats['errors'][] = "Failed to read session file: {$filePath}";
                return;
            }
            
            // Validate session data
            if (!$this->isValidSessionData($sessionData)) {
                $this->stats['skipped']++;
                $this->log("Invalid session data in file: {$fileName}", 'warning');
                return;
            }
            
            // Backup original file if enabled
            if ($this->backup && !$this->dryRun) {
                $this->backupSessionFile($filePath);
            }
            
            // Migrate to Redis
            if (!$this->dryRun) {
                if ($this->redisHandler->write($sessionId, $sessionData)) {
                    $this->stats['migrated']++;
                    $this->log("Migrated session: {$sessionId} ({$fileName})");
                    
                    // Verify migration
                    if ($this->verifyMigration($sessionId, $sessionData)) {
                        $this->log("Verified migration: {$sessionId}");
                    } else {
                        $this->log("Migration verification failed: {$sessionId}", 'warning');
                    }
                } else {
                    $this->stats['failed']++;
                    $this->stats['errors'][] = "Failed to write session to Redis: {$sessionId}";
                }
            } else {
                $this->stats['migrated']++;
                $this->log("Would migrate session: {$sessionId} ({$fileName}) [DRY RUN]");
            }
            
        } catch (Exception $e) {
            $this->stats['failed']++;
            $this->stats['errors'][] = "Error processing {$filePath}: " . $e->getMessage();
            $this->log("Error processing session file {$filePath}: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Validate session ID format
     */
    private function isValidSessionId(string $sessionId): bool
    {
        // PHP session IDs are typically 26-40 characters of alphanumeric + comma + dash
        return preg_match('/^[a-zA-Z0-9,-]{26,128}$/', $sessionId) === 1;
    }
    
    /**
     * Validate session data
     */
    private function isValidSessionData(string $data): bool
    {
        // Check if data looks like serialized PHP data
        if (empty($data)) {
            return true; // Empty sessions are valid
        }
        
        // Try to unserialize to validate format
        $unserialized = @unserialize($data);
        return $unserialized !== false || $data === 'b:0;'; // Handle serialized false
    }
    
    /**
     * Backup session file
     */
    private function backupSessionFile(string $filePath): void
    {
        $fileName = basename($filePath);
        $backupPath = $this->backupDir . '/' . $fileName;
        
        if (!copy($filePath, $backupPath)) {
            throw new Exception("Failed to backup session file: {$filePath}");
        }
        
        $this->log("Backed up session file: {$fileName}");
    }
    
    /**
     * Verify migration by reading back from Redis
     */
    private function verifyMigration(string $sessionId, string $originalData): bool
    {
        try {
            $redisData = $this->redisHandler->read($sessionId);
            return $redisData === $originalData;
        } catch (Exception $e) {
            $this->log("Verification error for session {$sessionId}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Generate migration report
     */
    private function generateReport(): array
    {
        $report = [
            'migration_time' => date('Y-m-d H:i:s'),
            'dry_run' => $this->dryRun,
            'backup_enabled' => $this->backup,
            'backup_directory' => $this->backup ? $this->backupDir : null,
            'statistics' => $this->stats,
            'success_rate' => $this->stats['total_files'] > 0 ? 
                round(($this->stats['migrated'] / $this->stats['total_files']) * 100, 2) : 0
        ];
        
        // Add Redis health check
        if ($this->redisHandler) {
            $report['redis_health'] = $this->redisHandler->healthCheck();
            $report['redis_stats'] = $this->redisHandler->getStats();
        }
        
        return $report;
    }
    
    /**
     * Clean up old session files after successful migration
     */
    public function cleanupOldSessions(string $sessionPath = null, int $maxAge = 86400): array
    {
        $this->log("Starting cleanup of old session files");
        
        $sessionPath = $sessionPath ?? $this->getSessionPath();
        $cleaned = 0;
        $errors = [];
        
        try {
            $sessionFiles = $this->getSessionFiles($sessionPath);
            $cutoffTime = time() - $maxAge;
            
            foreach ($sessionFiles as $file) {
                $fileTime = filemtime($file);
                if ($fileTime !== false && $fileTime < $cutoffTime) {
                    if (unlink($file)) {
                        $cleaned++;
                        $this->log("Cleaned up old session file: " . basename($file));
                    } else {
                        $errors[] = "Failed to delete: " . basename($file);
                    }
                }
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        $this->log("Cleanup completed: {$cleaned} files removed");
        
        return [
            'cleaned_files' => $cleaned,
            'errors' => $errors
        ];
    }
    
    /**
     * Log message with timestamp
     */
    private function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] {$message}";
        
        echo $formattedMessage . PHP_EOL;
        
        // Also log to application logger
        switch ($level) {
            case 'error':
                Logger::error($message);
                break;
            case 'warning':
                Logger::warning($message);
                break;
            default:
                Logger::info($message);
                break;
        }
    }
}

// CLI interface
if (PHP_SAPI === 'cli') {
    $options = getopt('hdbc:', ['help', 'dry-run', 'no-backup', 'cleanup:']);
    
    if (isset($options['h']) || isset($options['help'])) {
        echo "Session Migration Utility\n\n";
        echo "Usage: php session_migrate.php [options]\n\n";
        echo "Options:\n";
        echo "  -h, --help         Show this help message\n";
        echo "  -d, --dry-run      Perform a dry run (no actual migration)\n";
        echo "  -b, --no-backup    Skip backing up original session files\n";
        echo "  -c, --cleanup=AGE  Clean up old session files (age in seconds)\n\n";
        echo "Examples:\n";
        echo "  php session_migrate.php                    # Migrate with backup\n";
        echo "  php session_migrate.php --dry-run          # Test migration\n";
        echo "  php session_migrate.php --cleanup=86400    # Clean up files older than 1 day\n";
        exit(0);
    }
    
    try {
        $dryRun = isset($options['d']) || isset($options['dry-run']);
        $backup = !isset($options['b']) && !isset($options['no-backup']);
        
        $migrator = new SessionMigrator($dryRun, $backup);
        
        if (isset($options['c']) || isset($options['cleanup'])) {
            $maxAge = (int)($options['c'] ?? $options['cleanup'] ?? 86400);
            $result = $migrator->cleanupOldSessions(null, $maxAge);
            echo "\nCleanup Results:\n";
            echo "Files cleaned: {$result['cleaned_files']}\n";
            if (!empty($result['errors'])) {
                echo "Errors: " . implode(', ', $result['errors']) . "\n";
            }
        } else {
            $report = $migrator->migrate();
            
            echo "\nMigration Report:\n";
            echo "================\n";
            echo "Migration Time: {$report['migration_time']}\n";
            echo "Dry Run: " . ($report['dry_run'] ? 'Yes' : 'No') . "\n";
            echo "Backup Enabled: " . ($report['backup_enabled'] ? 'Yes' : 'No') . "\n";
            echo "Total Files: {$report['statistics']['total_files']}\n";
            echo "Migrated: {$report['statistics']['migrated']}\n";
            echo "Failed: {$report['statistics']['failed']}\n";
            echo "Skipped: {$report['statistics']['skipped']}\n";
            echo "Success Rate: {$report['success_rate']}%\n";
            
            if (!empty($report['statistics']['errors'])) {
                echo "\nErrors:\n";
                foreach ($report['statistics']['errors'] as $error) {
                    echo "  - {$error}\n";
                }
            }
            
            if ($report['backup_enabled'] && !$report['dry_run']) {
                echo "\nBackup Directory: {$report['backup_directory']}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
