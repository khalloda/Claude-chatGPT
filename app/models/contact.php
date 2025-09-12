<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

final class Contact
{
    public static function all(?string $q = null, ?int $customerId = null, int $limit = 200, int $offset = 0): array
    {
        $sql = "SELECT ct.*, c.name AS customer_name
                  FROM contacts ct
             LEFT JOIN customers c ON c.id = ct.customer_id
                 WHERE 1=1";
        $args = [];
        if ($q) {
            $sql .= " AND (ct.name LIKE ? OR ct.email LIKE ? OR ct.phone LIKE ?)";
            $like = "%$q%"; $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if ($customerId) { $sql .= " AND ct.customer_id = ?"; $args[] = $customerId; }
        $sql .= " ORDER BY ct.created_at DESC, ct.id DESC LIMIT ? OFFSET ?"; $args[] = $limit; $args[] = $offset;
        $st = DB::conn()->prepare($sql); $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = DB::conn()->prepare("SELECT ct.*, c.name AS customer_name FROM contacts ct LEFT JOIN customers c ON c.id=ct.customer_id WHERE ct.id=?");
        $st->execute([$id]); $r=$st->fetch(PDO::FETCH_ASSOC); return $r?:null;
    }

    public static function forCustomer(int $customerId): array
    {
        $st = DB::conn()->prepare("SELECT * FROM contacts WHERE customer_id=? ORDER BY id DESC");
        $st->execute([$customerId]); return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare("INSERT INTO contacts (customer_id, name, email, phone, job_title, note, created_at) VALUES (?,?,?,?,?,?, NOW())");
        $st->execute([
            $data['customer_id'] ?: null,
            $data['name'],
            $data['email'] ?: null,
            $data['phone'] ?: null,
            $data['job_title'] ?: null,
            $data['note'] ?: null,
        ]);
        return (int)DB::conn()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = DB::conn()->prepare("UPDATE contacts SET customer_id=?, name=?, email=?, phone=?, job_title=?, note=?, updated_at=NOW() WHERE id=?");
        $st->execute([
            $data['customer_id'] ?: null,
            $data['name'],
            $data['email'] ?: null,
            $data['phone'] ?: null,
            $data['job_title'] ?: null,
            $data['note'] ?: null,
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare("DELETE FROM contacts WHERE id=?");
        return $st->execute([$id]);
    }
}

