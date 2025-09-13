<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0"><?= $t('notifications.notifications') ?></h1>
                    <p class="text-muted mb-0"><?= $t('notifications.manage_system_notifications') ?></p>
                </div>
                <div>
                    <a href="<?= base_url('/notifications/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?= $t('notifications.create_notification') ?>
                    </a>
                    <a href="<?= base_url('/notifications/templates') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-templates"></i> <?= $t('notifications.templates') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $h($stats['total'] ?? 0) ?></h4>
                            <p class="mb-0"><?= $t('notifications.total_notifications') ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $h($stats['unread'] ?? 0) ?></h4>
                            <p class="mb-0"><?= $t('notifications.unread_notifications') ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $h($stats['active'] ?? 0) ?></h4>
                            <p class="mb-0"><?= $t('notifications.active_notifications') ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $h($stats['scheduled'] ?? 0) ?></h4>
                            <p class="mb-0"><?= $t('notifications.scheduled_notifications') ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> <?= $t('notifications.all_notifications') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted"><?= $t('notifications.no_notifications') ?></h5>
                            <p class="text-muted"><?= $t('notifications.no_notifications_description') ?></p>
                            <a href="<?= base_url('/notifications/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?= $t('notifications.create_first_notification') ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= $t('common.id') ?></th>
                                        <th><?= $t('notifications.title') ?></th>
                                        <th><?= $t('notifications.type') ?></th>
                                        <th><?= $t('notifications.channel') ?></th>
                                        <th><?= $t('notifications.target') ?></th>
                                        <th><?= $t('notifications.status') ?></th>
                                        <th><?= $t('notifications.created_by') ?></th>
                                        <th><?= $t('notifications.created_at') ?></th>
                                        <th><?= $t('common.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr class="<?= $notification['is_read'] ? '' : 'table-warning' ?>">
                                            <td><?= $h($notification['id']) ?></td>
                                            <td>
                                                <strong><?= $h($notification['title']) ?></strong>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-warning ms-2"><?= $t('notifications.unread') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $notification['type'] === 'error' ? 'danger' : ($notification['type'] === 'warning' ? 'warning' : ($notification['type'] === 'success' ? 'success' : 'info')) ?>">
                                                    <?= $t('notifications.type_' . $notification['type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= $t('notifications.channel_' . $notification['channel']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($notification['target_type'] === 'all'): ?>
                                                    <span class="badge bg-primary"><?= $t('notifications.all_users') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><?= $t('notifications.target_type_' . $notification['target_type']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($notification['is_active']): ?>
                                                    <span class="badge bg-success"><?= $t('notifications.active') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $t('notifications.inactive') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $h($notification['created_by_email'] ?? 'System') ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= base_url('/notifications/edit?id=' . $notification['id']) ?>" 
                                                       class="btn btn-outline-primary" title="<?= $t('common.edit') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <form method="POST" action="<?= base_url('/notifications/mark-read') ?>" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                            <input type="hidden" name="id" value="<?= $h($notification['id']) ?>">
                                                            <button type="submit" class="btn btn-outline-success" title="<?= $t('notifications.mark_as_read') ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" action="<?= base_url('/notifications/delete') ?>" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                        <input type="hidden" name="id" value="<?= $h($notification['id']) ?>">
                                                        <button type="submit" class="btn btn-outline-danger" 
                                                                onclick="return confirm('<?= $t('notifications.confirm_delete') ?>')" 
                                                                title="<?= $t('common.delete') ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $t('common.back') ?>
            </a>
        </div>
    </div>
</div>

