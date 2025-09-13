<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Notification
{
    public static function all(): array
    {
        $st = DB::conn()->prepare(
            'SELECT n.*, u.email as created_by_email
             FROM notifications n
             LEFT JOIN users u ON n.created_by = u.id
             ORDER BY n.created_at DESC'
        );
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getActive(): array
    {
        $st = DB::conn()->prepare(
            'SELECT n.*, u.email as created_by_email
             FROM notifications n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE n.is_active = 1
             ORDER BY n.created_at DESC'
        );
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getForUser(int $userId): array
    {
        $st = DB::conn()->prepare(
            'SELECT n.*, u.email as created_by_email
             FROM notifications n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE (n.target_type = "all" OR (n.target_type = "user" AND n.target_id = ?))
             AND n.is_active = 1
             ORDER BY n.created_at DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getUnreadForUser(int $userId): array
    {
        $st = DB::conn()->prepare(
            'SELECT n.*, u.email as created_by_email
             FROM notifications n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE (n.target_type = "all" OR (n.target_type = "user" AND n.target_id = ?))
             AND n.is_active = 1 AND n.is_read = 0
             ORDER BY n.created_at DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare(
            'INSERT INTO notifications (title, message, type, channel, target_type, target_id, scheduled_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['channel'] ?? 'in_app',
            $data['target_type'] ?? 'all',
            $data['target_id'] ?? null,
            $data['scheduled_at'] ?? null,
            $data['created_by']
        ]);
        return (int)DB::conn()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        foreach (['title', 'message', 'type', 'channel', 'target_type', 'target_id', 'scheduled_at', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = 'UPDATE notifications SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = DB::conn()->prepare($sql);
        return $st->execute($values);
    }

    public static function markAsRead(int $id): bool
    {
        $st = DB::conn()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        return $st->execute([$id]);
    }

    public static function markAsUnread(int $id): bool
    {
        $st = DB::conn()->prepare('UPDATE notifications SET is_read = 0 WHERE id = ?');
        return $st->execute([$id]);
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM notifications WHERE id = ?');
        return $st->execute([$id]);
    }

    public static function getTypes(): array
    {
        return [
            'info' => 'Information',
            'success' => 'Success',
            'warning' => 'Warning',
            'error' => 'Error'
        ];
    }

    public static function getChannels(): array
    {
        return [
            'in_app' => 'In-App',
            'email' => 'Email',
            'sms' => 'SMS',
            'webhook' => 'Webhook'
        ];
    }

    public static function getTargetTypes(): array
    {
        return [
            'all' => 'All Users',
            'user' => 'Specific User',
            'role' => 'User Role',
            'group' => 'User Group'
        ];
    }

    public static function getStats(): array
    {
        $st = DB::conn()->prepare(
            'SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN scheduled_at IS NOT NULL AND scheduled_at > NOW() THEN 1 ELSE 0 END) as scheduled
             FROM notifications'
        );
        $st->execute();
        return $st->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
