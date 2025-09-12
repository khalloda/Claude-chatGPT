<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class Permission
{
    public static function all(): array {
        return DB::conn()->query('SELECT id, name, slug, description, category FROM permissions ORDER BY category, name')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    public static function allGroupedByCategory(): array {
        $permissions = self::all();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $category = $permission['category'] ?? 'general';
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }
    
    public static function create(array $data): int {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('INSERT INTO permissions (name, slug, description, category) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['name'], 
            $data['slug'],
            $data['description'] ?? null,
            $data['category'] ?? 'general'
        ]);
        return (int)$pdo->lastInsertId();
    }
    
    public static function find(int $id): ?array {
        $stmt = DB::conn()->prepare('SELECT * FROM permissions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function update(int $id, array $data): void {
        $stmt = DB::conn()->prepare('UPDATE permissions SET name = ?, slug = ?, description = ?, category = ? WHERE id = ?');
        $stmt->execute([
            $data['name'], 
            $data['slug'],
            $data['description'] ?? null,
            $data['category'] ?? 'general',
            $id
        ]);
    }
    
    public static function delete(int $id): bool {
        $pdo = DB::conn();
        try {
            $pdo->beginTransaction();
            
            // Remove from role-permission associations
            $pdo->prepare('DELETE FROM role_permissions WHERE permission_id = ?')->execute([$id]);
            
            // Delete the permission
            $stmt = $pdo->prepare('DELETE FROM permissions WHERE id = ?');
            $result = $stmt->execute([$id]);
            
            $pdo->commit();
            return $result;
            
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    
    public static function getBySlug(string $slug): ?array {
        $stmt = DB::conn()->prepare('SELECT * FROM permissions WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function getCategories(): array {
        $stmt = DB::conn()->query('SELECT DISTINCT category FROM permissions WHERE category IS NOT NULL ORDER BY category');
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}

