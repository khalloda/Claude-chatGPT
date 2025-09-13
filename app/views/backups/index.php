<?php
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$u = fn($path) => \App\Core\base_url($path);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?= $h($page_title) ?></h1>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                        <i class="fas fa-plus me-1"></i>
                        <?= $t('backups.create_backup') ?>
                    </button>
                </div>
            </div>

            <!-- Backup Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title"><?= $t('backups.total_backups') ?></h6>
                                    <h3 class="mb-0"><?= $h($backup_stats['total_count']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-database fa-2x"></i>
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
                                    <h6 class="card-title"><?= $t('backups.total_size') ?></h6>
                                    <h3 class="mb-0"><?= $h($backup_stats['total_size_formatted']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-hdd fa-2x"></i>
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
                                    <h6 class="card-title"><?= $t('backups.latest_backup') ?></h6>
                                    <h6 class="mb-0"><?= $backup_stats['newest_backup'] ? date('M j, Y', $backup_stats['newest_backup']) : $t('backups.none') ?></h6>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
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
                                    <h6 class="card-title"><?= $t('backups.oldest_backup') ?></h6>
                                    <h6 class="mb-0"><?= $backup_stats['oldest_backup'] ? date('M j, Y', $backup_stats['oldest_backup']) : $t('backups.none') ?></h6>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-history fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= $t('backups.quick_actions') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                                        <i class="fas fa-plus me-2"></i>
                                        <?= $t('backups.create_backup') ?>
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                                        <i class="fas fa-broom me-2"></i>
                                        <?= $t('backups.cleanup_old') ?>
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <a href="<?= $u('/backups') ?>" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-sync me-2"></i>
                                        <?= $t('backups.refresh') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= $t('backups.backup_list') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-database fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted"><?= $t('backups.no_backups') ?></h5>
                            <p class="text-muted"><?= $t('backups.no_backups_description') ?></p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                                <i class="fas fa-plus me-1"></i>
                                <?= $t('backups.create_first_backup') ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= $t('backups.filename') ?></th>
                                        <th><?= $t('backups.type') ?></th>
                                        <th><?= $t('backups.size') ?></th>
                                        <th><?= $t('backups.created_at') ?></th>
                                        <th><?= $t('backups.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td>
                                                <code><?= $h($backup['filename']) ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $h($backup['type_color']) ?>">
                                                    <?= $t('backups.type_' . $backup['type']) ?>
                                                </span>
                                            </td>
                                            <td><?= $h($backup['size_formatted']) ?></td>
                                            <td><?= $h(date('Y-m-d H:i:s', $backup['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= $u('/backups/download?file=' . urlencode($backup['filename'])) ?>" 
                                                       class="btn btn-outline-primary" title="<?= $t('backups.download') ?>">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="restoreBackup('<?= $h($backup['filename']) ?>')" 
                                                            title="<?= $t('backups.restore') ?>">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteBackup('<?= $h($backup['filename']) ?>')" 
                                                            title="<?= $t('backups.delete') ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $u('/backups/create') ?>">
                <?= \App\Core\csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $t('backups.create_backup') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="backup_type" class="form-label"><?= $t('backups.backup_type') ?></label>
                        <select class="form-select" id="backup_type" name="backup_type" required>
                            <option value="full"><?= $t('backups.type_full') ?></option>
                            <option value="structure"><?= $t('backups.type_structure') ?></option>
                            <option value="data"><?= $t('backups.type_data') ?></option>
                        </select>
                        <div class="form-text"><?= $t('backups.backup_type_help') ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_data" name="include_data" value="1" checked>
                            <label class="form-check-label" for="include_data">
                                <?= $t('backups.include_data') ?>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="compress" name="compress" value="1" checked>
                            <label class="form-check-label" for="compress">
                                <?= $t('backups.compress_backup') ?>
                            </label>
                        </div>
                        <div class="form-text"><?= $t('backups.compress_help') ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('common.cancel') ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        <?= $t('backups.create_backup') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $u('/backups/cleanup') ?>">
                <?= \App\Core\csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $t('backups.cleanup_old') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $t('backups.cleanup_warning') ?>
                    </div>
                    <div class="mb-3">
                        <label for="days" class="form-label"><?= $t('backups.delete_older_than') ?></label>
                        <select class="form-select" id="days" name="days" required>
                            <option value="7">7 <?= $t('backups.days') ?></option>
                            <option value="15">15 <?= $t('backups.days') ?></option>
                            <option value="30" selected>30 <?= $t('backups.days') ?></option>
                            <option value="60">60 <?= $t('backups.days') ?></option>
                            <option value="90">90 <?= $t('backups.days') ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('common.cancel') ?></button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-broom me-1"></i>
                        <?= $t('backups.cleanup_old') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $u('/backups/restore') ?>" id="restoreForm">
                <?= \App\Core\csrf_field() ?>
                <input type="hidden" name="backup_file" id="restore_filename">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $t('backups.restore_backup') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $t('backups.restore_warning') ?>
                    </div>
                    <p><?= $t('backups.restore_confirm') ?></p>
                    <p><strong><?= $t('backups.backup_file') ?>:</strong> <code id="restore_filename_display"></code></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('common.cancel') ?></button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo me-1"></i>
                        <?= $t('backups.restore_backup') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $u('/backups/delete') ?>" id="deleteForm">
                <?= \App\Core\csrf_field() ?>
                <input type="hidden" name="backup_file" id="delete_filename">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $t('backups.delete_backup') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $t('backups.delete_warning') ?>
                    </div>
                    <p><?= $t('backups.delete_confirm') ?></p>
                    <p><strong><?= $t('backups.backup_file') ?>:</strong> <code id="delete_filename_display"></code></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('common.cancel') ?></button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        <?= $t('backups.delete_backup') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function restoreBackup(filename) {
    document.getElementById('restore_filename').value = filename;
    document.getElementById('restore_filename_display').textContent = filename;
    new bootstrap.Modal(document.getElementById('restoreModal')).show();
}

function deleteBackup(filename) {
    document.getElementById('delete_filename').value = filename;
    document.getElementById('delete_filename_display').textContent = filename;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
