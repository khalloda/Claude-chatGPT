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
                    <h1 class="h3 mb-0"><?= $h($page_title) ?></h1>
                    <p class="text-muted mb-0">
                        <?= $notification ? $t('notifications.edit_notification_description') : $t('notifications.create_notification_description') ?>
                    </p>
                </div>
                <div>
                    <a href="<?= base_url('/notifications') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $t('common.back') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-<?= $notification ? 'edit' : 'plus' ?>"></i> 
                        <?= $notification ? $t('notifications.edit_notification') : $t('notifications.create_notification') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= base_url('/notifications/' . ($notification ? 'update' : 'store')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <?php if ($notification): ?>
                            <input type="hidden" name="id" value="<?= $h($notification['id']) ?>">
                        <?php endif; ?>
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label"><?= $t('notifications.title') ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?= $h($notification['title'] ?? '') ?>" 
                                           placeholder="<?= $t('notifications.title_placeholder') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label"><?= $t('notifications.type') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value=""><?= $t('notifications.select_type') ?></option>
                                        <?php foreach ($types as $key => $label): ?>
                                            <option value="<?= $h($key) ?>" <?= ($notification['type'] ?? '') === $key ? 'selected' : '' ?>>
                                                <?= $h($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="channel" class="form-label"><?= $t('notifications.channel') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="channel" name="channel" required>
                                        <option value=""><?= $t('notifications.select_channel') ?></option>
                                        <?php foreach ($channels as $key => $label): ?>
                                            <option value="<?= $h($key) ?>" <?= ($notification['channel'] ?? '') === $key ? 'selected' : '' ?>>
                                                <?= $h($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_type" class="form-label"><?= $t('notifications.target_type') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="target_type" name="target_type" required>
                                        <option value=""><?= $t('notifications.select_target_type') ?></option>
                                        <?php foreach ($target_types as $key => $label): ?>
                                            <option value="<?= $h($key) ?>" <?= ($notification['target_type'] ?? '') === $key ? 'selected' : '' ?>>
                                                <?= $h($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="target_id_row" style="display: none;">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_id" class="form-label"><?= $t('notifications.target_user') ?></label>
                                    <select class="form-select" id="target_id" name="target_id">
                                        <option value=""><?= $t('notifications.select_user') ?></option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $h($user['id']) ?>" <?= ($notification['target_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                                <?= $h($user['email']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label"><?= $t('notifications.message') ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      placeholder="<?= $t('notifications.message_placeholder') ?>" required><?= $h($notification['message'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_at" class="form-label"><?= $t('notifications.scheduled_at') ?></label>
                                    <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" 
                                           value="<?= $notification['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($notification['scheduled_at'])) : '' ?>">
                                    <div class="form-text"><?= $t('notifications.scheduled_at_help') ?></div>
                                </div>
                            </div>
                            <?php if ($notification): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?= ($notification['is_active'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            <?= $t('notifications.is_active') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= base_url('/notifications') ?>" class="btn btn-outline-secondary">
                                <?= $t('common.cancel') ?>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?= $notification ? 'save' : 'plus' ?>"></i> 
                                <?= $notification ? $t('common.update') : $t('common.create') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> <?= $t('notifications.help') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <h6><?= $t('notifications.notification_types') ?></h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-info me-2">Info</span> <?= $t('notifications.type_info_description') ?></li>
                        <li><span class="badge bg-success me-2">Success</span> <?= $t('notifications.type_success_description') ?></li>
                        <li><span class="badge bg-warning me-2">Warning</span> <?= $t('notifications.type_warning_description') ?></li>
                        <li><span class="badge bg-danger me-2">Error</span> <?= $t('notifications.type_error_description') ?></li>
                    </ul>

                    <h6 class="mt-3"><?= $t('notifications.channels') ?></h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-secondary me-2">In-App</span> <?= $t('notifications.channel_in_app_description') ?></li>
                        <li><span class="badge bg-secondary me-2">Email</span> <?= $t('notifications.channel_email_description') ?></li>
                        <li><span class="badge bg-secondary me-2">SMS</span> <?= $t('notifications.channel_sms_description') ?></li>
                        <li><span class="badge bg-secondary me-2">Webhook</span> <?= $t('notifications.channel_webhook_description') ?></li>
                    </ul>

                    <h6 class="mt-3"><?= $t('notifications.targeting') ?></h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-primary me-2">All Users</span> <?= $t('notifications.target_all_description') ?></li>
                        <li><span class="badge bg-info me-2">Specific User</span> <?= $t('notifications.target_user_description') ?></li>
                        <li><span class="badge bg-info me-2">User Role</span> <?= $t('notifications.target_role_description') ?></li>
                        <li><span class="badge bg-info me-2">User Group</span> <?= $t('notifications.target_group_description') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetTypeSelect = document.getElementById('target_type');
    const targetIdRow = document.getElementById('target_id_row');
    
    function toggleTargetIdField() {
        const targetType = targetTypeSelect.value;
        if (targetType === 'user') {
            targetIdRow.style.display = 'block';
            document.getElementById('target_id').required = true;
        } else {
            targetIdRow.style.display = 'none';
            document.getElementById('target_id').required = false;
        }
    }
    
    targetTypeSelect.addEventListener('change', toggleTargetIdField);
    toggleTargetIdField(); // Initial call
});
</script>
