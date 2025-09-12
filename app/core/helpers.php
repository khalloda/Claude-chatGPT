<?php declare(strict_types=1);

namespace App\Core;

function base_url(string $path = ''): string
{
    $base = Env::get('APP_URL', '/');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function format_note_html(string $text): string {
    // 1) escape HTML
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 2) linkify http/https URLs (very conservative pattern)
    $safe = preg_replace(
        '~(https?://[^\s<]+)~i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $safe
    );

    // 3) newlines -> <br>
    return nl2br($safe, false);
}

/**
 * Build a URL to current path with a replaced/added query param.
 * Minimal helper for pagination/checkbox toggles.
 */
function url_with_query(array $pairs): string {
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    parse_str($parts['query'] ?? '', $q);
    foreach ($pairs as $k => $v) {
        if ($v === null) unset($q[$k]); else $q[$k] = $v;
    }
    $qs = http_build_query($q);
    return $path . ($qs ? ('?' . $qs) : '');
}
function redirect(string $to): void
{
    // If already absolute (http/https), use as-is
    if (preg_match('~^https?://~i', $to)) {
        header('Location: ' . $to);
    } else {
        header('Location: ' . base_url($to));
    }
    exit;
}
function activity_log(string $action, string $entity_type, int $entity_id, array $meta = []): void
{
    try {
        if (!isset($_SESSION)) { session_start(); }
        $actor = $_SESSION['user']['email'] ?? 'system';
        $pdo = DB::conn();
        $st = $pdo->prepare("INSERT INTO activity_log (actor, action, entity_type, entity_id, meta)
                             VALUES (?,?,?,?,?)");
        $st->execute([$actor, $action, $entity_type, $entity_id, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
    } catch (\Throwable $e) {
        // logging is best-effort; ignore failures
    }
}
/** CSRF utilities */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_created'] = time();
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_post(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    return isset($_POST['_token'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_token']);
}

function verify_csrf_header(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf']) && $token !== '' && hash_equals($_SESSION['csrf'], $token);
}

function verify_csrf_request(): bool
{
    // Check POST form data first, then header
    return verify_csrf_post() || verify_csrf_header();
}

function regenerate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_token_expired(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    // Check if token was created more than 2 hours ago
    $tokenTime = $_SESSION['csrf_created'] ?? 0;
    return (time() - $tokenTime) > 7200; // 2 hours
}

function refresh_csrf_if_needed(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (csrf_token_expired() || empty($_SESSION['csrf'])) {
        regenerate_csrf_token();
        $_SESSION['csrf_created'] = time();
    }
}

/** Simple auth helpers */
function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}
function auth_check(): bool
{
    return isset($_SESSION['user']);
}

/** Authorization helpers (role/permission based)
 * Supports two modes:
 *  - Legacy: users.role column with value 'admin' grants all.
 *  - RBAC tables (pending migration): roles, permissions, user_roles, role_permissions.
 */
function db_table_exists(string $name): bool {
    try {
        $pdo = DB::conn();
        $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
        $st->execute([$name]);
        return (bool)$st->fetchColumn();
    } catch (\Throwable $e) { return false; }
}

function user_has_permission(string $permission): bool {
    if (!auth_check()) return false;
    $u = auth_user();
    // Legacy shortcut: admin role column grants all
    $legacyRole = strtolower((string)($u['role'] ?? ''));
    if ($legacyRole === 'admin' || $legacyRole === 'superadmin') return true;

    // If RBAC tables are unavailable, default to deny (non-admin)
    if (!db_table_exists('roles') || !db_table_exists('permissions') || !db_table_exists('user_roles') || !db_table_exists('role_permissions')) {
        return false;
    }

    try {
        $pdo = DB::conn();
        $sql = "SELECT 1
                FROM user_roles ur
                JOIN role_permissions rp ON rp.role_id = ur.role_id
                JOIN permissions p ON p.id = rp.permission_id
                WHERE ur.user_id = ? AND p.slug = ? LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([(int)$u['id'], $permission]);
        return (bool)$st->fetchColumn();
    } catch (\Throwable $e) { return false; }
}

function require_permission(string $permission): void {
    require_auth();
    if (!user_has_permission($permission)) {
        flash_set('error', 'You do not have permission to perform this action.');
        redirect('/');
    }
}
