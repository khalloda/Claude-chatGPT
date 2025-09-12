<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use function App\Core\require_auth;

final class NotificationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        $this->view('notifications/index', [
            'page_title' => 'Notifications',
        ]);
    }
}

