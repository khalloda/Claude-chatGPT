<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use PDO;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;
use function App\Core\base_url;

final class BackupsController extends Controller
{
    private string $backupDir;
    private array $allowedExtensions = ['sql', 'gz', 'zip'];

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../storage/backups/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index(): void
    {
        require_auth();
        require_permission('settings.view');
        
        $backups = $this->getBackupList();
        $backupStats = $this->getBackupStats();
        
        $this->view('backups/index', [
            'page_title' => \App\Core\t('backups.backup_management'),
            'backups' => $backups,
            'backup_stats' => $backupStats,
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/backups');
        }

        try {
            $backupType = $_POST['backup_type'] ?? 'full';
            $includeData = isset($_POST['include_data']) && $_POST['include_data'] === '1';
            $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
            
            $filename = $this->createBackup($backupType, $includeData, $compress);
            
            if ($filename) {
                flash_set('success', \App\Core\t('backups.backup_created_successfully', ['filename' => $filename]));
            } else {
                flash_set('error', \App\Core\t('backups.backup_creation_failed'));
            }
        } catch (\Exception $e) {
            flash_set('error', \App\Core\t('backups.backup_error', ['error' => $e->getMessage()]));
        }
        
        redirect('/backups');
    }

    public function download(): void
    {
        require_auth();
        require_permission('settings.view');
        
        $filename = $_GET['file'] ?? '';
        
        if (empty($filename) || !$this->isValidBackupFile($filename)) {
            flash_set('error', \App\Core\t('backups.invalid_backup_file'));
            redirect('/backups');
        }
        
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            flash_set('error', \App\Core\t('backups.backup_file_not_found'));
            redirect('/backups');
        }
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output the file
        readfile($filepath);
        exit;
    }

    public function restore(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/backups');
        }
        
        $filename = $_POST['backup_file'] ?? '';
        
        if (empty($filename) || !$this->isValidBackupFile($filename)) {
            flash_set('error', \App\Core\t('backups.invalid_backup_file'));
            redirect('/backups');
        }
        
        try {
            $result = $this->restoreBackup($filename);
            
            if ($result) {
                flash_set('success', \App\Core\t('backups.backup_restored_successfully', ['filename' => $filename]));
            } else {
                flash_set('error', \App\Core\t('backups.backup_restore_failed'));
            }
        } catch (\Exception $e) {
            flash_set('error', \App\Core\t('backups.restore_error', ['error' => $e->getMessage()]));
        }
        
        redirect('/backups');
    }

    public function delete(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/backups');
        }
        
        $filename = $_POST['backup_file'] ?? '';
        
        if (empty($filename) || !$this->isValidBackupFile($filename)) {
            flash_set('error', \App\Core\t('backups.invalid_backup_file'));
            redirect('/backups');
        }
        
        $filepath = $this->backupDir . $filename;
        
        if (file_exists($filepath) && unlink($filepath)) {
            flash_set('success', \App\Core\t('backups.backup_deleted_successfully', ['filename' => $filename]));
        } else {
            flash_set('error', \App\Core\t('backups.backup_delete_failed'));
        }
        
        redirect('/backups');
    }

    public function cleanup(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/backups');
        }
        
        $days = (int)($_POST['days'] ?? 30);
        $deletedCount = $this->cleanupOldBackups($days);
        
        flash_set('success', \App\Core\t('backups.cleanup_completed', ['count' => $deletedCount, 'days' => $days]));
        redirect('/backups');
    }

    private function getBackupList(): array
    {
        $backups = [];
        
        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || !$this->isValidBackupFile($file)) {
                    continue;
                }
                
                $filepath = $this->backupDir . $file;
                $type = $this->getBackupType($file);
                $size = filesize($filepath);
                
                $backups[] = [
                    'filename' => $file,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'created_at' => filemtime($filepath),
                    'type' => $type,
                    'type_color' => $this->getBackupTypeColor($type),
                ];
            }
            
            // Sort by creation time (newest first)
            usort($backups, function($a, $b) {
                return $b['created_at'] - $a['created_at'];
            });
        }
        
        return $backups;
    }

    private function getBackupStats(): array
    {
        $backups = $this->getBackupList();
        $totalSize = 0;
        $totalCount = count($backups);
        
        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
        }
        
        return [
            'total_count' => $totalCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'oldest_backup' => $totalCount > 0 ? min(array_column($backups, 'created_at')) : null,
            'newest_backup' => $totalCount > 0 ? max(array_column($backups, 'created_at')) : null,
        ];
    }

    private function createBackup(string $type, bool $includeData, bool $compress): ?string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$type}_{$timestamp}.sql";
        
        if ($compress) {
            $filename .= '.gz';
        }
        
        $filepath = $this->backupDir . $filename;
        
        // Get database configuration
        $config = $this->getDatabaseConfig();
        
        // Build mysqldump command
        $command = $this->buildMysqldumpCommand($config, $type, $includeData, $compress, $filepath);
        
        // Execute backup command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($filepath)) {
            return $filename;
        }
        
        return null;
    }

    private function buildMysqldumpCommand(array $config, string $type, bool $includeData, bool $compress, string $filepath): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $database = $config['database'] ?? '';
        
        // Use full path to mysqldump on Windows
        $mysqldumpPath = $this->findMysqldumpPath();
        
        $command = "\"{$mysqldumpPath}\"";
        $command .= " --host={$host}";
        $command .= " --port={$port}";
        $command .= " --user={$username}";
        
        if (!empty($password)) {
            $command .= " --password={$password}";
        }
        
        // Add common options
        $command .= " --single-transaction";
        $command .= " --routines";
        $command .= " --triggers";
        $command .= " --events";
        $command .= " --add-drop-database";
        $command .= " --add-locks";
        $command .= " --create-options";
        $command .= " --extended-insert";
        $command .= " --quick";
        $command .= " --lock-tables=false";
        
        // Add type-specific options
        if ($type === 'structure') {
            $command .= " --no-data";
        } elseif ($type === 'data') {
            $command .= " --no-create-info";
        }
        
        // Add database name
        $command .= " {$database}";
        
        // Add output redirection
        if ($compress) {
            $command .= " | gzip > {$filepath}";
        } else {
            $command .= " > {$filepath}";
        }
        
        return $command;
    }

    private function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Get database configuration
        $config = $this->getDatabaseConfig();
        
        // Build mysql command
        $command = $this->buildMysqlCommand($config, $filepath);
        
        // Execute restore command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        return $returnCode === 0;
    }

    private function buildMysqlCommand(array $config, string $filepath): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $database = $config['database'] ?? '';
        
        // Use full path to mysql on Windows
        $mysqlPath = $this->findMysqlPath();
        
        $command = "\"{$mysqlPath}\"";
        $command .= " --host={$host}";
        $command .= " --port={$port}";
        $command .= " --user={$username}";
        
        if (!empty($password)) {
            $command .= " --password={$password}";
        }
        
        $command .= " {$database}";
        
        // Handle compressed files
        if (strpos($filepath, '.gz') !== false) {
            $command = "gunzip < \"{$filepath}\" | {$command}";
        } else {
            $command .= " < \"{$filepath}\"";
        }
        
        return $command;
    }

    private function cleanupOldBackups(int $days): int
    {
        $deletedCount = 0;
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || !$this->isValidBackupFile($file)) {
                    continue;
                }
                
                $filepath = $this->backupDir . $file;
                
                if (filemtime($filepath) < $cutoffTime) {
                    if (unlink($filepath)) {
                        $deletedCount++;
                    }
                }
            }
        }
        
        return $deletedCount;
    }

    private function isValidBackupFile(string $filename): bool
    {
        // Check if file has valid extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->allowedExtensions)) {
            return false;
        }
        
        // Check if filename matches backup pattern
        return (bool) preg_match('/^backup_(full|structure|data)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql(\.gz)?$/', $filename);
    }

    private function getBackupType(string $filename): string
    {
        if (preg_match('/backup_(full|structure|data)_/', $filename, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getBackupTypeColor(string $type): string
    {
        switch ($type) {
            case 'full':
                return 'primary';
            case 'structure':
                return 'info';
            case 'data':
                return 'success';
            default:
                return 'secondary';
        }
    }

    private function getDatabaseConfig(): array
    {
        $env = \App\Core\Env::class;
        return [
            'host' => $env::get('DB_HOST', 'localhost'),
            'port' => $env::get('DB_PORT', '3306'),
            'username' => $env::get('DB_USER', 'root'),
            'password' => $env::get('DB_PASS', '1234'),
            'database' => $env::get('DB_NAME', 'chatgpt2_mi'),
        ];
    }

    private function findMysqldumpPath(): string
    {
        // Common paths for mysqldump on Windows
        $possiblePaths = [
            'C:\\Program Files\\MySQL\\MySQL Workbench 8.0\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.21\\bin\\mysqldump.exe',
            'mysqldump' // fallback to PATH
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'mysqldump') {
                // Test if mysqldump is in PATH
                $output = [];
                $returnCode = 0;
                exec('mysqldump --version 2>&1', $output, $returnCode);
                if ($returnCode === 0) {
                    return $path;
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        // If nothing found, return the first path as fallback
        return $possiblePaths[0];
    }

    private function findMysqlPath(): string
    {
        // Common paths for mysql on Windows
        $possiblePaths = [
            'C:\\Program Files\\MySQL\\MySQL Workbench 8.0\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.21\\bin\\mysql.exe',
            'mysql' // fallback to PATH
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'mysql') {
                // Test if mysql is in PATH
                $output = [];
                $returnCode = 0;
                exec('mysql --version 2>&1', $output, $returnCode);
                if ($returnCode === 0) {
                    return $path;
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        // If nothing found, return the first path as fallback
        return $possiblePaths[0];
    }
}
