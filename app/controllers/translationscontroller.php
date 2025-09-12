<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use function App\Core\require_auth;

final class TranslationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        $this->view('translations/index', [
            'page_title' => 'Translations',
        ]);
    }
}

