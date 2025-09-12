<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class Role
{
    public static function all(): array {
        $pdo = DB::conn();
        return $pdo->query('SELECT id, name, slug, description FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    public static function create(array $data): int {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)');
        $stmt->execute([
            $data['name'], 
            $data['slug'],
            $data['description'] ?? null
        ]);
        return (int)$pdo->lastInsertId();
    }
    
    public static function find(int $id): ?array {
        $stmt = DB::conn()->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function update(int $id, array $data): void {
        $stmt = DB::conn()->prepare('UPDATE roles SET name = ?, slug = ?, description = ? WHERE id = ?');
        $stmt->execute([
            $data['name'], 
            $data['slug'],
            $data['description'] ?? null,
            $id
        ]);
    }
    
    public static function delete(int $id): bool {
        $pdo = DB::conn();
        try {
            $pdo->beginTransaction();
            
            // Remove role-permission associations
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
            
            // Remove user-role associations
            $pdo->prepare('DELETE FROM user_roles WHERE role_id = ?')->execute([$id]);
            
            // Delete the role
            $stmt = $pdo->prepare('DELETE FROM roles WHERE id = ?');
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
    
    public static function permissions(int $roleId): array {
        $stmt = DB::conn()->prepare('
            SELECT p.* 
            FROM permissions p 
            JOIN role_permissions rp ON rp.permission_id = p.id 
            WHERE rp.role_id = ? 
            ORDER BY p.category, p.name
        ');
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    public static function setPermissions(int $roleId, array $permIds): void {
        $pdo = DB::conn();
        try {
            $pdo->beginTransaction();
            
            // Remove existing permissions
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
            
            // Add new permissions
            if (!empty($permIds)) {
                $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
                foreach ($permIds as $permId) { 
                    $permId = (int)$permId;
                    if ($permId > 0) {
                        $stmt->execute([$roleId, $permId]); 
                    }
                }
            }
            
            $pdo->commit();
            
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    
    public static function getUsersCount(int $roleId): int {
        $stmt = DB::conn()->prepare('SELECT COUNT(*) FROM user_roles WHERE role_id = ?');
        $stmt->execute([$roleId]);
        return (int)$stmt->fetchColumn();
    }
    
    public static function getBySlug(string $slug): ?array {
        $stmt = DB::conn()->prepare('SELECT * FROM roles WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}