<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class SystemSetting
{
    private static array $cache = [];
    
    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        if (!isset(self::$cache[$key])) {
            $stmt = DB::conn()->prepare('SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result) {
                self::$cache[$key] = $default;
                return $default;
            }
            
            // Type conversion based on setting_type
            $value = $result['setting_value'];
            switch ($result['setting_type']) {
                case 'number':
                    $value = is_numeric($value) ? (float)$value : $default;
                    break;
                case 'boolean':
                    $value = in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                    break;
                case 'json':
                    $decoded = json_decode($value, true);
                    $value = ($decoded !== null) ? $decoded : $default;
                    break;
                default:
                    // string type - return as is
                    break;
            }
            
            self::$cache[$key] = $value;
        }
        
        return self::$cache[$key];
    }
    
    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string', string $category = 'general', string $description = ''): bool
    {
        try {
            // Convert value based on type
            switch ($type) {
                case 'boolean':
                    $value = $value ? 'true' : 'false';
                    break;
                case 'json':
                    $value = json_encode($value);
                    break;
                default:
                    $value = (string)$value;
                    break;
            }
            
            $stmt = DB::conn()->prepare('
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    category = VALUES(category),
                    description = VALUES(description),
                    updated_at = CURRENT_TIMESTAMP
            ');
            
            $result = $stmt->execute([$key, $value, $type, $category, $description]);
            
            // Clear cache for this key
            unset(self::$cache[$key]);
            
            return $result;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get all settings by category
     */
    public static function getByCategory(string $category): array
    {
        $stmt = DB::conn()->prepare('
            SELECT setting_key, setting_value, setting_type, description 
            FROM system_settings 
            WHERE category = ? 
            ORDER BY setting_key
        ');
        $stmt->execute([$category]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            
            // Type conversion
            switch ($row['setting_type']) {
                case 'number':
                    $value = is_numeric($value) ? (float)$value : 0;
                    break;
                case 'boolean':
                    $value = in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                    break;
                case 'json':
                    $decoded = json_decode($value, true);
                    $value = ($decoded !== null) ? $decoded : [];
                    break;
            }
            
            $settings[$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type'],
                'description' => $row['description']
            ];
        }
        
        return $settings;
    }
    
    /**
     * Get all settings grouped by category
     */
    public static function getAllGrouped(): array
    {
        $stmt = DB::conn()->query('
            SELECT setting_key, setting_value, setting_type, category, description 
            FROM system_settings 
            ORDER BY category, setting_key
        ');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        $grouped = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            
            // Type conversion
            switch ($row['setting_type']) {
                case 'number':
                    $value = is_numeric($value) ? (float)$value : 0;
                    break;
                case 'boolean':
                    $value = in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                    break;
                case 'json':
                    $decoded = json_decode($value, true);
                    $value = ($decoded !== null) ? $decoded : [];
                    break;
            }
            
            $grouped[$row['category']][$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type'],
                'description' => $row['description']
            ];
        }
        
        return $grouped;
    }
    
    /**
     * Delete a setting
     */
    public static function delete(string $key): bool
    {
        try {
            $stmt = DB::conn()->prepare('DELETE FROM system_settings WHERE setting_key = ?');
            $result = $stmt->execute([$key]);
            
            // Clear cache
            unset(self::$cache[$key]);
            
            return $result;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Clear the settings cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
    
    /**
     * Get company information settings
     */
    public static function getCompanyInfo(): array
    {
        return self::getByCategory('company');
    }
    
    /**
     * Get currency settings
     */
    public static function getCurrencySettings(): array
    {
        return self::getByCategory('currency');
    }
    
    /**
     * Get tax settings
     */
    public static function getTaxSettings(): array
    {
        return self::getByCategory('tax');
    }
}
