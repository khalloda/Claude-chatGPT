<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use function App\Core\require_auth;

final class IntegrationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        $this->view('integrations/index', [
            'page_title' => 'Integrations',
        ]);
    }
}

