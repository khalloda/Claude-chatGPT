<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use function App\Core\require_auth;

final class SettingsController extends Controller
{
    public function taxcurrency(): void
    {
        require_auth();
        $this->view('settings/tax_currency', [
            'page_title' => 'Taxes & Currency',
        ]);
    }

    public function unitssequences(): void
    {
        require_auth();
        $this->view('settings/units_sequences', [
            'page_title' => 'Units & Sequences',
        ]);
    }
}

