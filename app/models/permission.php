<?php declare(strict_types=1);
namespace App\Models;

use App\Core\DB;

final class Permission
{
    public static function all(): array {
        return DB::conn()->query('SELECT id, name, slug FROM permissions ORDER BY slug')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    public static function create(array $d): int {
        $pdo = DB::conn();
        $st = $pdo->prepare('INSERT INTO permissions (name, slug) VALUES (?,?)');
        $st->execute([$d['name'], $d['slug']]);
        return (int)$pdo->lastInsertId();
    }
    public static function delete(int $id): bool {
        $pdo = DB::conn();
        $pdo->prepare('DELETE FROM role_permissions WHERE permission_id=?')->execute([$id]);
        return (bool)$pdo->prepare('DELETE FROM permissions WHERE id=?')->execute([$id]);
    }
}

