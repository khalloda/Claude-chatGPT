<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class TaxRate
{
    /**
     * Get all tax rates
     */
    public static function all(): array
    {
        $stmt = DB::conn()->query('
            SELECT id, name, rate, type, is_default, is_active, description, 
                   effective_from, effective_to, created_at, updated_at
            FROM tax_rates 
            ORDER BY type, name
        ');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get active tax rates
     */
    public static function getActive(): array
    {
        $stmt = DB::conn()->query('
            SELECT id, name, rate, type, is_default, description, 
                   effective_from, effective_to
            FROM tax_rates 
            WHERE is_active = TRUE 
            AND (effective_from IS NULL OR effective_from <= CURDATE())
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY type, name
        ');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get tax rates by type
     */
    public static function getByType(string $type): array
    {
        $stmt = DB::conn()->prepare('
            SELECT id, name, rate, is_default, description, 
                   effective_from, effective_to
            FROM tax_rates 
            WHERE type = ? AND is_active = TRUE
            AND (effective_from IS NULL OR effective_from <= CURDATE())
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY name
        ');
        $stmt->execute([$type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get default tax rate for a type
     */
    public static function getDefault(string $type = 'sales'): ?array
    {
        $stmt = DB::conn()->prepare('
            SELECT id, name, rate, description
            FROM tax_rates 
            WHERE type = ? AND is_default = TRUE AND is_active = TRUE
            AND (effective_from IS NULL OR effective_from <= CURDATE())
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            LIMIT 1
        ');
        $stmt->execute([$type]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Find tax rate by ID
     */
    public static function find(int $id): ?array
    {
        $stmt = DB::conn()->prepare('
            SELECT id, name, rate, type, is_default, is_active, description,
                   effective_from, effective_to, created_at, updated_at
            FROM tax_rates 
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Create new tax rate
     */
    public static function create(array $data): int
    {
        $pdo = DB::conn();
        
        try {
            $pdo->beginTransaction();
            
            // If this is being set as default, unset other defaults of the same type
            if (!empty($data['is_default'])) {
                $pdo->prepare('UPDATE tax_rates SET is_default = FALSE WHERE type = ?')
                    ->execute([$data['type'] ?? 'sales']);
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO tax_rates (name, rate, type, is_default, is_active, 
                                     description, effective_from, effective_to)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $data['name'],
                (float)($data['rate'] ?? 0),
                $data['type'] ?? 'sales',
                !empty($data['is_default']),
                !empty($data['is_active']),
                $data['description'] ?? null,
                $data['effective_from'] ?? null,
                $data['effective_to'] ?? null
            ]);
            
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Update tax rate
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = DB::conn();
        
        try {
            $pdo->beginTransaction();
            
            // If this is being set as default, unset other defaults of the same type
            if (!empty($data['is_default'])) {
                $type = $data['type'] ?? 'sales';
                $pdo->prepare('UPDATE tax_rates SET is_default = FALSE WHERE type = ? AND id != ?')
                    ->execute([$type, $id]);
            }
            
            $stmt = $pdo->prepare('
                UPDATE tax_rates 
                SET name = ?, rate = ?, type = ?, is_default = ?, is_active = ?,
                    description = ?, effective_from = ?, effective_to = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            
            $result = $stmt->execute([
                $data['name'],
                (float)($data['rate'] ?? 0),
                $data['type'] ?? 'sales',
                !empty($data['is_default']),
                !empty($data['is_active']),
                $data['description'] ?? null,
                $data['effective_from'] ?? null,
                $data['effective_to'] ?? null,
                $id
            ]);
            
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Delete tax rate
     */
    public static function delete(int $id): bool
    {
        try {
            $stmt = DB::conn()->prepare('DELETE FROM tax_rates WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Calculate tax amount
     */
    public static function calculateTax(float $amount, float $rate, bool $inclusive = false): array
    {
        if ($inclusive) {
            // Tax is included in the amount
            $taxAmount = $amount * ($rate / (100 + $rate));
            $netAmount = $amount - $taxAmount;
        } else {
            // Tax is additional to the amount
            $netAmount = $amount;
            $taxAmount = $amount * ($rate / 100);
        }
        
        return [
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'gross_amount' => round($netAmount + $taxAmount, 2),
            'tax_rate' => $rate
        ];
    }
    
    /**
     * Get available tax types
     */
    public static function getTypes(): array
    {
        return [
            'sales' => 'Sales Tax',
            'purchase' => 'Purchase Tax',
            'vat' => 'Value Added Tax (VAT)',
            'service' => 'Service Tax',
            'import' => 'Import Duty',
            'export' => 'Export Tax'
        ];
    }
}
