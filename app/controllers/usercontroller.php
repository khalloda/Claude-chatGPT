<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use function App\Core\require_auth;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class UserController extends Controller
{
    public function index(): void
    {
        require_auth();
        \App\Core\require_permission('users.view');
        
        // Get search and filter parameters
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $role = trim((string)($_GET['role'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        // Build query with filters
        $whereConditions = [];
        $params = [];
        
        if ($search !== '') {
            $whereConditions[] = '(u.email LIKE ? OR u.id = ?)';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
        }
        
        if ($status !== '') {
            $whereConditions[] = 'u.status = ?';
            $params[] = $status;
        }
        
        if ($role !== '') {
            $whereConditions[] = 'u.role = ?';
            $params[] = $role;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Main query with role names
        $sql = "SELECT u.id, u.email, u.role, u.status, u.last_login_at, u.created_at,
                       GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') as role_names
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                {$whereClause}
                GROUP BY u.id, u.email, u.role, u.status, u.last_login_at, u.created_at
                ORDER BY u.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = \App\Core\DB::conn()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        // Count total for pagination
        $countSql = "SELECT COUNT(DISTINCT u.id) FROM users u {$whereClause}";
        $countStmt = \App\Core\DB::conn()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        // Get distinct statuses and roles for filters
        $statuses = \App\Core\DB::conn()->query("SELECT DISTINCT status FROM users WHERE status IS NOT NULL ORDER BY status")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $roles = \App\Core\DB::conn()->query("SELECT DISTINCT role FROM users ORDER BY role")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        
        $this->view('user/index', [
            'rows' => $rows,
            'page_title' => 'User Management',
            'search' => $search,
            'status' => $status,
            'role' => $role,
            'statuses' => $statuses,
            'roles' => $roles,
            'pagination' => [
                'current' => $page,
                'total' => $total,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function profile(): void
    {
        require_auth();
        $this->view('user/profile', []);
    }

    public function changepassword(): void
    {
        require_auth();

        if (!verify_csrf_request()) {
            http_response_code(419);
            flash_set('error', 'Invalid session token. Please try again.');
            redirect('/profile');
        }

        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        if ($new === '' || $confirm === '' || $current === '') {
            flash_set('error', 'All fields are required.');
            redirect('/profile');
        }
        if ($new !== $confirm) {
            flash_set('error', 'New passwords do not match.');
            redirect('/profile');
        }
        if (strlen($new) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
            redirect('/profile');
        }

        $uid = (int)($_SESSION['user']['id'] ?? 0);
        $stmt = DB::conn()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            flash_set('error', 'Current password is incorrect.');
            redirect('/profile');
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $upd = DB::conn()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $uid]);

        flash_set('success', 'Password updated successfully.');
        redirect('/profile');
    }

    public function create(): void
    {
        require_auth(); \App\Core\require_permission('users.manage');
        // load roles if RBAC present
        $roles = [];
        if (\App\Core\db_table_exists('roles')) { $roles = \App\Models\Role::all(); }
        $this->view('user/form', ['mode'=>'create','item'=>['id'=>0,'email'=>'','role'=>'staff'],'roles'=>$roles]);
    }

    public function store(): void
    {
        require_auth(); 
        \App\Core\require_permission('users.manage');
        
        if (!\App\Core\verify_csrf_request()) { 
            \App\Core\flash_set('error','Invalid session token.'); 
            \App\Core\redirect('/users'); 
        }
        
        // Collect and validate input
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'staff'));
        $status = trim((string)($_POST['status'] ?? 'active'));
        $password = (string)($_POST['password'] ?? '');
        $assignedRoles = $_POST['roles'] ?? [];
        
        // Validation
        $errors = [];
        
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email format is invalid.';
        } else {
            // Check if email already exists
            $stmt = \App\Core\DB::conn()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already in use.';
            }
        }
        
        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
        }
        
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            $errors[] = 'Invalid role selected.';
        }
        
        if (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
            $errors[] = 'Invalid status selected.';
        }
        
        if (!empty($errors)) {
            \App\Core\flash_set('error', implode('<br>', $errors));
            \App\Core\redirect('/users/create');
        }
        
        try {
            $pdo = \App\Core\DB::conn();
            $pdo->beginTransaction();
            
            // Create user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$email, $hash, $role, $status]);
            $userId = (int)$pdo->lastInsertId();
            
            // Assign RBAC roles if provided
            if (!empty($assignedRoles) && \App\Core\db_table_exists('user_roles')) {
                $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
                $roleStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)');
                
                foreach ($assignedRoles as $roleId) {
                    $roleId = (int)$roleId;
                    if ($roleId > 0) {
                        $roleStmt->execute([$userId, $roleId, $currentUserId]);
                    }
                }
            }
            
            $pdo->commit();
            
            // Log the activity
            \App\Core\activity_log('created', 'user', $userId, ['email' => $email, 'role' => $role, 'status' => $status]);
            
            \App\Core\flash_set('success', 'User created successfully.');
            \App\Core\redirect('/users');
            
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            \App\Core\Logger::error('User creation failed', [
                'error' => $e->getMessage(),
                'email' => $email,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            \App\Core\flash_set('error', 'Failed to create user. Please try again.');
            \App\Core\redirect('/users/create');
        }
    }

    public function edit(): void
    {
        require_auth(); \App\Core\require_permission('users.manage');
        $id = (int)($_GET['id'] ?? 0);
        $st = \App\Core\DB::conn()->prepare('SELECT id, email, role FROM users WHERE id=?');
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { \App\Core\flash_set('error','User not found.'); \App\Core\redirect('/users'); }
        $roles = [];$assigned=[];
        if (\App\Core\db_table_exists('roles')) {
            $roles = \App\Models\Role::all();
            if (\App\Core\db_table_exists('user_roles')) {
                $st2 = \App\Core\DB::conn()->prepare('SELECT role_id FROM user_roles WHERE user_id=?');
                $st2->execute([$id]); $assigned = array_map('intval', array_column($st2->fetchAll(\PDO::FETCH_ASSOC)?:[], 'role_id'));
            }
        }
        $this->view('user/form', ['mode'=>'edit','item'=>$row,'roles'=>$roles,'assigned'=>$assigned]);
    }

    public function update(): void
    {
        require_auth(); 
        \App\Core\require_permission('users.manage');
        
        if (!\App\Core\verify_csrf_request()) { 
            \App\Core\flash_set('error','Invalid session token.'); 
            \App\Core\redirect('/users'); 
        }
        
        // Collect and validate input
        $id = (int)($_POST['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'staff'));
        $status = trim((string)($_POST['status'] ?? 'active'));
        $password = (string)($_POST['password'] ?? '');
        $assignedRoles = $_POST['roles'] ?? [];
        
        // Validation
        $errors = [];
        
        if ($id <= 0) {
            $errors[] = 'Invalid user ID.';
        }
        
        // Prevent self-deactivation for current user
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        if ($id === $currentUserId && $status !== 'active') {
            $errors[] = 'You cannot deactivate your own account.';
        }
        
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email format is invalid.';
        } else {
            // Check if email already exists for other users
            $stmt = \App\Core\DB::conn()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already in use by another user.';
            }
        }
        
        if ($password !== '') {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
            }
        }
        
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            $errors[] = 'Invalid role selected.';
        }
        
        if (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
            $errors[] = 'Invalid status selected.';
        }
        
        if (!empty($errors)) {
            \App\Core\flash_set('error', implode('<br>', $errors));
            \App\Core\redirect('/users/edit?id=' . $id);
        }
        
        try {
            $pdo = \App\Core\DB::conn();
            $pdo->beginTransaction();
            
            // Get current user data for logging
            $stmt = $pdo->prepare('SELECT email, role, status FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $currentData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                \App\Core\flash_set('error', 'User not found.');
                \App\Core\redirect('/users');
            }
            
            // Update user basic info
            $updateFields = ['email = ?', 'role = ?', 'status = ?'];
            $updateParams = [$email, $role, $status];
            
            // Add password update if provided
            if ($password !== '') {
                $updateFields[] = 'password_hash = ?';
                $updateParams[] = password_hash($password, PASSWORD_BCRYPT);
            }
            
            $updateParams[] = $id; // for WHERE clause
            
            $sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateParams);
            
            // Handle RBAC role assignments
            if (\App\Core\db_table_exists('user_roles')) {
                // Remove existing role assignments
                $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
                
                // Add new role assignments
                if (!empty($assignedRoles)) {
                    $roleStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)');
                    
                    foreach ($assignedRoles as $roleId) {
                        $roleId = (int)$roleId;
                        if ($roleId > 0) {
                            $roleStmt->execute([$id, $roleId, $currentUserId]);
                        }
                    }
                }
            }
            
            $pdo->commit();
            
            // Log the activity
            $changes = [];
            if ($currentData['email'] !== $email) $changes['email'] = ['from' => $currentData['email'], 'to' => $email];
            if ($currentData['role'] !== $role) $changes['role'] = ['from' => $currentData['role'], 'to' => $role];
            if ($currentData['status'] !== $status) $changes['status'] = ['from' => $currentData['status'], 'to' => $status];
            if ($password !== '') $changes['password'] = 'changed';
            
            \App\Core\activity_log('updated', 'user', $id, ['changes' => $changes]);
            
            \App\Core\flash_set('success', 'User updated successfully.');
            \App\Core\redirect('/users');
            
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            \App\Core\Logger::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'email' => $email,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            \App\Core\flash_set('error', 'Failed to update user. Please try again.');
            \App\Core\redirect('/users/edit?id=' . $id);
        }
    }

    public function destroy(): void
    {
        require_auth(); 
        \App\Core\require_permission('users.manage');
        
        if (!\App\Core\verify_csrf_request()) { 
            \App\Core\flash_set('error','Invalid session token.'); 
            \App\Core\redirect('/users'); 
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        
        if ($id <= 0) {
            \App\Core\flash_set('error','Invalid user ID.'); 
            \App\Core\redirect('/users'); 
        }
        
        if ($id === $currentUserId) { 
            \App\Core\flash_set('error','You cannot delete your own account.'); 
            \App\Core\redirect('/users'); 
        }
        
        try {
            $pdo = \App\Core\DB::conn();
            $pdo->beginTransaction();
            
            // Get user info for logging
            $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userInfo) {
                \App\Core\flash_set('error','User not found.'); 
                \App\Core\redirect('/users'); 
            }
            
            // Remove RBAC assignments first
            if (\App\Core\db_table_exists('user_roles')) { 
                $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]); 
            }
            
            // Delete the user
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            
            $pdo->commit();
            
            // Log the activity
            \App\Core\activity_log('deleted', 'user', $id, ['email' => $userInfo['email']]);
            
            \App\Core\flash_set('success','User deleted successfully.');
            \App\Core\redirect('/users');
            
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            \App\Core\Logger::error('User deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            \App\Core\flash_set('error', 'Failed to delete user. Please try again.');
            \App\Core\redirect('/users');
        }
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus(): void
    {
        require_auth(); 
        \App\Core\require_permission('users.manage');
        
        if (!\App\Core\verify_csrf_request()) { 
            \App\Core\flash_set('error','Invalid session token.'); 
            \App\Core\redirect('/users'); 
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        
        if ($id <= 0) {
            \App\Core\flash_set('error','Invalid user ID.'); 
            \App\Core\redirect('/users'); 
        }
        
        if ($id === $currentUserId && $newStatus !== 'active') { 
            \App\Core\flash_set('error','You cannot deactivate your own account.'); 
            \App\Core\redirect('/users'); 
        }
        
        if (!in_array($newStatus, ['active', 'inactive', 'suspended'], true)) {
            \App\Core\flash_set('error','Invalid status.'); 
            \App\Core\redirect('/users'); 
        }
        
        try {
            $pdo = \App\Core\DB::conn();
            
            // Get current status for logging
            $stmt = $pdo->prepare('SELECT email, status FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userInfo) {
                \App\Core\flash_set('error','User not found.'); 
                \App\Core\redirect('/users'); 
            }
            
            // Update status
            $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            
            // Log the activity
            \App\Core\activity_log('status_changed', 'user', $id, [
                'email' => $userInfo['email'],
                'from' => $userInfo['status'],
                'to' => $newStatus
            ]);
            
            $statusLabel = ucfirst($newStatus);
            \App\Core\flash_set('success', "User status changed to {$statusLabel}.");
            \App\Core\redirect('/users');
            
        } catch (\Throwable $e) {
            \App\Core\Logger::error('User status change failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'new_status' => $newStatus,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            \App\Core\flash_set('error', 'Failed to change user status. Please try again.');
            \App\Core\redirect('/users');
        }
    }

    /**
     * Show detailed user information
     */
    public function show(): void
    {
        require_auth(); 
        \App\Core\require_permission('users.view');
        
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            \App\Core\flash_set('error','Invalid user ID.'); 
            \App\Core\redirect('/users'); 
        }
        
        $pdo = \App\Core\DB::conn();
        
        // Get user with role information
        $sql = "SELECT u.*, 
                       GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') as role_names,
                       GROUP_CONCAT(r.id ORDER BY r.name SEPARATOR ',') as role_ids
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = ?
                GROUP BY u.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            \App\Core\flash_set('error','User not found.'); 
            \App\Core\redirect('/users'); 
        }
        
        // Get user's permissions
        $permissionsSql = "SELECT DISTINCT p.name, p.slug, p.category
                          FROM permissions p
                          JOIN role_permissions rp ON p.id = rp.permission_id
                          JOIN user_roles ur ON rp.role_id = ur.role_id
                          WHERE ur.user_id = ?
                          ORDER BY p.category, p.name";
        
        $stmt = $pdo->prepare($permissionsSql);
        $stmt->execute([$id]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        // Group permissions by category
        $permissionsByCategory = [];
        foreach ($permissions as $permission) {
            $category = $permission['category'] ?? 'general';
            $permissionsByCategory[$category][] = $permission;
        }
        
        $this->view('user/show', [
            'user' => $user,
            'permissions' => $permissionsByCategory,
            'page_title' => 'User Details: ' . htmlspecialchars($user['email'], ENT_QUOTES)
        ]);
    }
}
