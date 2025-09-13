<?php declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class NotificationTemplate
{
    public static function all(): array
    {
        $st = DB::conn()->prepare(
            'SELECT * FROM notification_templates ORDER BY name ASC'
        );
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getActive(): array
    {
        $st = DB::conn()->prepare(
            'SELECT * FROM notification_templates WHERE is_active = 1 ORDER BY name ASC'
        );
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getBySlug(string $slug): ?array
    {
        $st = DB::conn()->prepare(
            'SELECT * FROM notification_templates WHERE slug = ? AND is_active = 1'
        );
        $st->execute([$slug]);
        $result = $st->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare(
            'INSERT INTO notification_templates (name, slug, title_template, message_template, type, channel, variables, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['name'],
            $data['slug'],
            $data['title_template'],
            $data['message_template'],
            $data['type'] ?? 'info',
            $data['channel'] ?? 'in_app',
            $data['variables'] ? json_encode($data['variables']) : null,
            $data['is_active'] ? 1 : 0
        ]);
        return (int)DB::conn()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        foreach (['name', 'slug', 'title_template', 'message_template', 'type', 'channel', 'variables', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                if ($field === 'variables') {
                    $values[] = $data[$field] ? json_encode($data[$field]) : null;
                } else {
                    $values[] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = 'UPDATE notification_templates SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = DB::conn()->prepare($sql);
        return $st->execute($values);
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM notification_templates WHERE id = ?');
        return $st->execute([$id]);
    }

    public static function renderTemplate(string $slug, array $variables = []): ?array
    {
        $template = self::getBySlug($slug);
        if (!$template) {
            return null;
        }

        $title = $template['title_template'];
        $message = $template['message_template'];

        // Replace variables in templates
        foreach ($variables as $key => $value) {
            $title = str_replace('{' . $key . '}', (string)$value, $title);
            $message = str_replace('{' . $key . '}', (string)$value, $message);
        }

        return [
            'title' => $title,
            'message' => $message,
            'type' => $template['type'],
            'channel' => $template['channel']
        ];
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
}
