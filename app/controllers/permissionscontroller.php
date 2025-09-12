<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Permission;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class PermissionsController extends Controller
{
    public function index(): void {
        require_auth(); require_permission('permissions.manage');
        $this->view('users/permissions', ['perms'=>Permission::all()]);
    }
    public function store(): void {
        require_auth(); require_permission('permissions.manage');
        if (!verify_csrf_request()) { flash_set('error','Invalid session'); redirect('/permissions'); }
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($name===''||$slug===''){ flash_set('error','Name and slug required'); redirect('/permissions'); }
        Permission::create(['name'=>$name,'slug'=>strtolower($slug)]);
        flash_set('success','Permission created'); redirect('/permissions');
    }
    public function destroy(): void {
        require_auth(); require_permission('permissions.manage');
        if (!verify_csrf_request()) { flash_set('error','Invalid session'); redirect('/permissions'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) { Permission::delete($id); flash_set('success','Permission deleted'); }
        redirect('/permissions');
    }
}

