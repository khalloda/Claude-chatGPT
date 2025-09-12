<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Contact;
use PDO;

use function App\Core\require_auth;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class ContactsController extends Controller
{
    public function index(): void
    {
        require_auth();
        try {
            $q  = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
            $cid= isset($_GET['customer_id']) && $_GET['customer_id'] !== '' ? (int)$_GET['customer_id'] : null;
            $items = Contact::all($q ?: null, $cid ?: null);
            $customers = DB::conn()->query('SELECT id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $this->view('contacts/index', ['items'=>$items,'q'=>$q,'customer_id'=>$cid,'customers'=>$customers]);
        } catch (\Throwable $e) {
            flash_set('error', 'Contacts table missing. Please apply migration scripts/2025-09-11_create_contacts.sql');
            $this->view('contacts/index', ['items'=>[],'q'=>null,'customer_id'=>null,'customers'=>[]]);
        }
    }

    public function create(): void
    {
        require_auth();
        $customers = DB::conn()->query('SELECT id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->view('contacts/form', ['mode'=>'create','item'=>[],'customers'=>$customers]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/contacts'); }
        $data = $this->readForm();
        if ($data['name'] === '') { flash_set('error','Name is required.'); redirect('/contacts/create'); }
        Contact::create($data);
        flash_set('success','Contact saved.');
        redirect('/contacts');
    }

    public function edit(): void
    {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        $item = Contact::find($id);
        if (!$item) { flash_set('error','Contact not found.'); redirect('/contacts'); }
        $customers = DB::conn()->query('SELECT id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->view('contacts/form', ['mode'=>'edit','item'=>$item,'customers'=>$customers]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/contacts'); }
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->readForm();
        if ($id<=0) { flash_set('error','Invalid id.'); redirect('/contacts'); }
        if ($data['name'] === '') { flash_set('error','Name is required.'); redirect('/contacts/edit?id='.$id); }
        Contact::update($id, $data); flash_set('success','Contact updated.');
        redirect('/contacts');
    }

    public function destroy(): void
    {
        require_auth();
        if (!verify_csrf_request()) { flash_set('error','Invalid session.'); redirect('/contacts'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) { Contact::delete($id); flash_set('success','Contact deleted.'); }
        redirect('/contacts');
    }

    private function readForm(): array
    {
        return [
            'customer_id' => isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null,
            'name'        => trim((string)($_POST['name'] ?? '')),
            'email'       => trim((string)($_POST['email'] ?? '')),
            'phone'       => trim((string)($_POST['phone'] ?? '')),
            'job_title'   => trim((string)($_POST['job_title'] ?? '')),
            'note'        => trim((string)($_POST['note'] ?? '')),
        ];
    }
}

