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
        \App\Core\require_permission('users.manage');
        $rows = \App\Core\DB::conn()->query('SELECT id, email, role, created_at FROM users ORDER BY id ASC')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->view('user/index', ['rows' => $rows, 'page_title' => 'Users & Roles']);
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
        require_auth(); \App\Core\require_permission('users.manage');
        if (!\App\Core\verify_csrf_request()) { \App\Core\flash_set('error','Invalid session.'); \App\Core\redirect('/users'); }
        $email = trim((string)($_POST['email'] ?? ''));
        $role  = trim((string)($_POST['role'] ?? 'staff'));
        $pass  = (string)($_POST['password'] ?? '');
        if ($email==='' || $pass==='') { \App\Core\flash_set('error','Email and password are required.'); \App\Core\redirect('/users/create'); }
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo = \App\Core\DB::conn();
        $st = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?,?,?)');
        $st->execute([$email, $hash, $role]);
        $uid = (int)$pdo->lastInsertId();
        // Assign roles in RBAC if provided
        $assign = $_POST['roles'] ?? [];
        if ($assign && \App\Core\db_table_exists('user_roles')) {
            $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
            foreach ($assign as $rid) { $ins->execute([$uid, (int)$rid]); }
        }
        \App\Core\flash_set('success','User created.');
        \App\Core\redirect('/users');
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
        require_auth(); \App\Core\require_permission('users.manage');
        if (!\App\Core\verify_csrf_request()) { \App\Core\flash_set('error','Invalid session.'); \App\Core\redirect('/users'); }
        $id = (int)($_POST['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $role  = trim((string)($_POST['role'] ?? 'staff'));
        if ($id<=0 || $email==='') { \App\Core\flash_set('error','Invalid input.'); \App\Core\redirect('/users'); }
        $pdo = \App\Core\DB::conn();
        $pdo->prepare('UPDATE users SET email=?, role=? WHERE id=?')->execute([$email,$role,$id]);
        if (isset($_POST['password']) && $_POST['password']!=='') {
            $hash = password_hash((string)$_POST['password'], PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash,$id]);
        }
        // RBAC assignments
        if (\App\Core\db_table_exists('user_roles')) {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
            $assign = $_POST['roles'] ?? [];
            if ($assign) {
                $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
                foreach ($assign as $rid) { $ins->execute([$id,(int)$rid]); }
            }
        }
        \App\Core\flash_set('success','User updated.');
        \App\Core\redirect('/users');
    }

    public function destroy(): void
    {
        require_auth(); \App\Core\require_permission('users.manage');
        if (!\App\Core\verify_csrf_request()) { \App\Core\flash_set('error','Invalid session.'); \App\Core\redirect('/users'); }
        $id = (int)($_POST['id'] ?? 0);
        $me = (int)($_SESSION['user']['id'] ?? 0);
        if ($id<=0 || $id===$me) { \App\Core\flash_set('error','Cannot delete this user.'); \App\Core\redirect('/users'); }
        $pdo = \App\Core\DB::conn();
        if (\App\Core\db_table_exists('user_roles')) { $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]); }
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        \App\Core\flash_set('success','User deleted.');
        \App\Core\redirect('/users');
    }
}
