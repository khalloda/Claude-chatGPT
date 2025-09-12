<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Role;
use App\Models\Permission;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class RolesController extends Controller
{
    public function index(): void {
        require_auth(); require_permission('roles.manage');
        $this->view('users/roles', [
            'roles' => Role::all(),
        ]);
    }

    public function edit(): void {
        require_auth(); require_permission('roles.manage');
        $id = (int)($_GET['id'] ?? 0);
        $role = Role::find($id);
        if (!$role) { flash_set('error','Role not found'); redirect('/roles'); }
        $perms = Permission::all();
        $assigned = array_column(Role::permissions($id), 'id');
        $this->view('users/role_form', [ 'role'=>$role, 'perms'=>$perms, 'assigned'=>$assigned ]);
    }

    public function create(): void {
        require_auth(); require_permission('roles.manage');
        $this->view('users/role_form', [ 'role'=>['id'=>0,'name'=>'','slug'=>''], 'perms'=>Permission::all(), 'assigned'=>[] ]);
    }

    public function store(): void {
        require_auth(); require_permission('roles.manage');
        if (!verify_csrf_request()) { flash_set('error','Invalid session'); redirect('/roles'); }
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($name===''||$slug===''){ flash_set('error','Name and slug required'); redirect('/roles/create'); }
        $id = Role::create(['name'=>$name,'slug'=>strtolower($slug)]);
        $permIds = array_map('intval', $_POST['perm'] ?? []);
        Role::setPermissions($id, $permIds);
        flash_set('success','Role created'); redirect('/roles');
    }

    public function update(): void {
        require_auth(); require_permission('roles.manage');
        if (!verify_csrf_request()) { flash_set('error','Invalid session'); redirect('/roles'); }
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($id<=0){ flash_set('error','Bad id'); redirect('/roles'); }
        Role::update($id, ['name'=>$name,'slug'=>strtolower($slug)]);
        $permIds = array_map('intval', $_POST['perm'] ?? []);
        Role::setPermissions($id, $permIds);
        flash_set('success','Role updated'); redirect('/roles');
    }

    public function destroy(): void {
        require_auth(); require_permission('roles.manage');
        if (!verify_csrf_request()) { flash_set('error','Invalid session'); redirect('/roles'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id<=0){ flash_set('error','Bad id'); redirect('/roles'); }
        if (!Role::delete($id)) { flash_set('error','Delete failed'); } else { flash_set('success','Role deleted'); }
        redirect('/roles');
    }
}

