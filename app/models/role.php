<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class Role
{
    public static function all(): array {
        $pdo = DB::conn();
        return $pdo->query('SELECT id, name, slug FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    public static function create(array $d): int {
        $pdo = DB::conn();
        $st = $pdo->prepare('INSERT INTO roles (name, slug) VALUES (?,?)');
        $st->execute([$d['name'], $d['slug']]);
        return (int)$pdo->lastInsertId();
    }
    public static function find(int $id): ?array {
        $st = DB::conn()->prepare('SELECT * FROM roles WHERE id=?');
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public static function update(int $id, array $d): void {
        $st = DB::conn()->prepare('UPDATE roles SET name=?, slug=? WHERE id=?');
        $st->execute([$d['name'], $d['slug'], $id]);
    }
    public static function delete(int $id): bool {
        $pdo = DB::conn();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM user_roles WHERE role_id=?')->execute([$id]);
        return (bool)$pdo->prepare('DELETE FROM roles WHERE id=?')->execute([$id]);
    }
    public static function permissions(int $roleId): array {
        $st = DB::conn()->prepare('SELECT p.* FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=? ORDER BY p.slug');
        $st->execute([$roleId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    public static function setPermissions(int $roleId, array $permIds): void {
        $pdo = DB::conn();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$roleId]);
        if ($permIds) {
            $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)');
            foreach ($permIds as $pid) { $ins->execute([$roleId, (int)$pid]); }
        }
    }
}

