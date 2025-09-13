<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class NotificationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_permission('users.view');
        
        $notifications = Notification::all();
        $stats = Notification::getStats();
        $templates = NotificationTemplate::getActive();
        
        $this->view('notifications/index', [
            'page_title' => \App\Core\t('notifications.notifications'),
            'notifications' => $notifications,
            'stats' => $stats,
            'templates' => $templates
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_permission('users.manage');
        
        $types = Notification::getTypes();
        $channels = Notification::getChannels();
        $targetTypes = Notification::getTargetTypes();
        $users = \App\Core\DB::conn()->query('SELECT id, email FROM users ORDER BY email')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        $this->view('notifications/form', [
            'page_title' => \App\Core\t('notifications.create_notification'),
            'types' => $types,
            'channels' => $channels,
            'target_types' => $targetTypes,
            'users' => $users,
            'notification' => null
        ]);
    }

    public function store(): void
    {
        require_auth();
        require_permission('users.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/notifications');
        }
        
        try {
            $data = [
                'title' => trim((string)($_POST['title'] ?? '')),
                'message' => trim((string)($_POST['message'] ?? '')),
                'type' => $_POST['type'] ?? 'info',
                'channel' => $_POST['channel'] ?? 'in_app',
                'target_type' => $_POST['target_type'] ?? 'all',
                'target_id' => !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null,
                'scheduled_at' => !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null,
                'created_by' => (int)($_SESSION['user']['id'] ?? 0)
            ];
            
            // Validation
            if (empty($data['title'])) {
                throw new \InvalidArgumentException('Title is required.');
            }
            
            if (empty($data['message'])) {
                throw new \InvalidArgumentException('Message is required.');
            }
            
            if ($data['created_by'] <= 0) {
                throw new \InvalidArgumentException('Invalid user session.');
            }
            
            $id = Notification::create($data);
            
            flash_set('success', 'Notification created successfully.');
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to create notification: ' . $e->getMessage());
        }
        
        redirect('/notifications');
    }

    public function edit(): void
    {
        require_auth();
        require_permission('users.manage');
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Invalid notification ID.');
            redirect('/notifications');
        }
        
        $notifications = Notification::all();
        $notification = array_filter($notifications, fn($n) => $n['id'] == $id);
        $notification = reset($notification);
        
        if (!$notification) {
            flash_set('error', 'Notification not found.');
            redirect('/notifications');
        }
        
        $types = Notification::getTypes();
        $channels = Notification::getChannels();
        $targetTypes = Notification::getTargetTypes();
        $users = \App\Core\DB::conn()->query('SELECT id, email FROM users ORDER BY email')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        $this->view('notifications/form', [
            'page_title' => \App\Core\t('notifications.edit_notification'),
            'types' => $types,
            'channels' => $channels,
            'target_types' => $targetTypes,
            'users' => $users,
            'notification' => $notification
        ]);
    }

    public function update(): void
    {
        require_auth();
        require_permission('users.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/notifications');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Invalid notification ID.');
            redirect('/notifications');
        }
        
        try {
            $data = [
                'title' => trim((string)($_POST['title'] ?? '')),
                'message' => trim((string)($_POST['message'] ?? '')),
                'type' => $_POST['type'] ?? 'info',
                'channel' => $_POST['channel'] ?? 'in_app',
                'target_type' => $_POST['target_type'] ?? 'all',
                'target_id' => !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null,
                'scheduled_at' => !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null,
                'is_active' => !empty($_POST['is_active'])
            ];
            
            // Validation
            if (empty($data['title'])) {
                throw new \InvalidArgumentException('Title is required.');
            }
            
            if (empty($data['message'])) {
                throw new \InvalidArgumentException('Message is required.');
            }
            
            if (Notification::update($id, $data)) {
                flash_set('success', 'Notification updated successfully.');
            } else {
                flash_set('error', 'Failed to update notification.');
            }
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to update notification: ' . $e->getMessage());
        }
        
        redirect('/notifications');
    }

    public function delete(): void
    {
        require_auth();
        require_permission('users.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/notifications');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Invalid notification ID.');
            redirect('/notifications');
        }
        
        try {
            if (Notification::delete($id)) {
                flash_set('success', 'Notification deleted successfully.');
            } else {
                flash_set('error', 'Failed to delete notification.');
            }
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to delete notification: ' . $e->getMessage());
        }
        
        redirect('/notifications');
    }

    public function markAsRead(): void
    {
        require_auth();
        require_permission('users.view');
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Invalid notification ID.');
            redirect('/notifications');
        }
        
        try {
            if (Notification::markAsRead($id)) {
                flash_set('success', 'Notification marked as read.');
            } else {
                flash_set('error', 'Failed to mark notification as read.');
            }
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to mark notification as read: ' . $e->getMessage());
        }
        
        redirect('/notifications');
    }

    public function templates(): void
    {
        require_auth();
        require_permission('users.manage');
        
        $templates = NotificationTemplate::all();
        
        $this->view('notifications/templates', [
            'page_title' => \App\Core\t('notifications.notification_templates'),
            'templates' => $templates
        ]);
    }
}

