<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class Currency
{
    /**
     * Get all currencies
     */
    public static function all(): array
    {
        $stmt = DB::conn()->query('
            SELECT id, code, name, symbol, exchange_rate, is_base, is_active, 
                   decimal_places, created_at, updated_at
            FROM currencies 
            ORDER BY is_base DESC, code
        ');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get active currencies
     */
    public static function getActive(): array
    {
        $stmt = DB::conn()->query('
            SELECT id, code, name, symbol, exchange_rate, is_base, decimal_places
            FROM currencies 
            WHERE is_active = TRUE 
            ORDER BY is_base DESC, code
        ');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get base currency
     */
    public static function getBase(): ?array
    {
        $stmt = DB::conn()->query('
            SELECT id, code, name, symbol, exchange_rate, decimal_places
            FROM currencies 
            WHERE is_base = TRUE AND is_active = TRUE 
            LIMIT 1
        ');
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Find currency by ID
     */
    public static function find(int $id): ?array
    {
        $stmt = DB::conn()->prepare('
            SELECT id, code, name, symbol, exchange_rate, is_base, is_active, 
                   decimal_places, created_at, updated_at
            FROM currencies 
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Find currency by code
     */
    public static function findByCode(string $code): ?array
    {
        $stmt = DB::conn()->prepare('
            SELECT id, code, name, symbol, exchange_rate, is_base, is_active, 
                   decimal_places, created_at, updated_at
            FROM currencies 
            WHERE code = ?
        ');
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Create new currency
     */
    public static function create(array $data): int
    {
        $pdo = DB::conn();
        
        try {
            $pdo->beginTransaction();
            
            // If this is being set as base, unset other base currencies
            if (!empty($data['is_base'])) {
                $pdo->query('UPDATE currencies SET is_base = FALSE');
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO currencies (code, name, symbol, exchange_rate, is_base, 
                                      is_active, decimal_places)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                strtoupper($data['code']),
                $data['name'],
                $data['symbol'] ?? '',
                (float)($data['exchange_rate'] ?? 1.0),
                !empty($data['is_base']),
                !empty($data['is_active']),
                (int)($data['decimal_places'] ?? 2)
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
     * Update currency
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = DB::conn();
        
        try {
            $pdo->beginTransaction();
            
            // If this is being set as base, unset other base currencies
            if (!empty($data['is_base'])) {
                $pdo->prepare('UPDATE currencies SET is_base = FALSE WHERE id != ?')
                    ->execute([$id]);
            }
            
            $stmt = $pdo->prepare('
                UPDATE currencies 
                SET code = ?, name = ?, symbol = ?, exchange_rate = ?, is_base = ?,
                    is_active = ?, decimal_places = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            
            $result = $stmt->execute([
                strtoupper($data['code']),
                $data['name'],
                $data['symbol'] ?? '',
                (float)($data['exchange_rate'] ?? 1.0),
                !empty($data['is_base']),
                !empty($data['is_active']),
                (int)($data['decimal_places'] ?? 2),
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
     * Delete currency (only if not base and not used)
     */
    public static function delete(int $id): bool
    {
        try {
            $pdo = DB::conn();
            
            // Check if it's the base currency
            $stmt = $pdo->prepare('SELECT is_base FROM currencies WHERE id = ?');
            $stmt->execute([$id]);
            $currency = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$currency || $currency['is_base']) {
                return false; // Cannot delete base currency
            }
            
            $stmt = $pdo->prepare('DELETE FROM currencies WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }
        
        $fromCurrency = self::findByCode($fromCode);
        $toCurrency = self::findByCode($toCode);
        
        if (!$fromCurrency || !$toCurrency) {
            return $amount; // Return original if currencies not found
        }
        
        // Convert to base currency first, then to target currency
        $baseAmount = $amount / $fromCurrency['exchange_rate'];
        return $baseAmount * $toCurrency['exchange_rate'];
    }
    
    /**
     * Format amount with currency
     */
    public static function format(float $amount, string $currencyCode = null): string
    {
        if (!$currencyCode) {
            $base = self::getBase();
            $currencyCode = $base['code'] ?? 'USD';
        }
        
        $currency = self::findByCode($currencyCode);
        if (!$currency) {
            return number_format($amount, 2);
        }
        
        $decimalPlaces = (int)($currency['decimal_places'] ?? 2);
        $formattedAmount = number_format($amount, $decimalPlaces);
        $symbol = $currency['symbol'] ?? $currency['code'];
        
        // Get currency position from settings
        $position = \App\Models\SystemSetting::get('currency_position', 'after');
        
        return $position === 'before' 
            ? $symbol . ' ' . $formattedAmount 
            : $formattedAmount . ' ' . $symbol;
    }
    
    /**
     * Add exchange rate history record
     */
    public static function addRateHistory(int $currencyId, float $rate, string $date = null, string $source = null): bool
    {
        try {
            $stmt = DB::conn()->prepare('
                INSERT INTO exchange_rate_history (currency_id, rate, effective_date, source)
                VALUES (?, ?, ?, ?)
            ');
            
            return $stmt->execute([
                $currencyId,
                $rate,
                $date ?? date('Y-m-d'),
                $source
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get exchange rate history for a currency
     */
    public static function getRateHistory(int $currencyId, int $limit = 50): array
    {
        $stmt = DB::conn()->prepare('
            SELECT rate, effective_date, source, created_at
            FROM exchange_rate_history 
            WHERE currency_id = ?
            ORDER BY effective_date DESC, created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$currencyId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
