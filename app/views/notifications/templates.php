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
                    <h1 class="h3 mb-0"><?= $t('notifications.notification_templates') ?></h1>
                    <p class="text-muted mb-0"><?= $t('notifications.manage_notification_templates') ?></p>
                </div>
                <div>
                    <a href="<?= base_url('/notifications') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $t('common.back') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="row">
        <?php if (empty($templates)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-templates fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted"><?= $t('notifications.no_templates') ?></h5>
                    <p class="text-muted"><?= $t('notifications.no_templates_description') ?></p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><?= $h($template['name']) ?></h6>
                            <div>
                                <span class="badge bg-<?= $template['type'] === 'error' ? 'danger' : ($template['type'] === 'warning' ? 'warning' : ($template['type'] === 'success' ? 'success' : 'info')) ?>">
                                    <?= $t('notifications.type_' . $template['type']) ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <?= $t('notifications.channel_' . $template['channel']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="text-muted"><?= $t('notifications.title_template') ?>:</h6>
                            <p class="small mb-3"><?= $h($template['title_template']) ?></p>
                            
                            <h6 class="text-muted"><?= $t('notifications.message_template') ?>:</h6>
                            <p class="small mb-3"><?= $h($template['message_template']) ?></p>
                            
                            <?php if ($template['variables']): ?>
                                <h6 class="text-muted"><?= $t('notifications.available_variables') ?>:</h6>
                                <div class="mb-3">
                                    <?php 
                                    $variables = json_decode($template['variables'], true);
                                    if ($variables): 
                                        foreach ($variables as $variable): 
                                    ?>
                                        <span class="badge bg-light text-dark me-1 mb-1">{<?= $h($variable) ?>}</span>
                                    <?php 
                                        endforeach; 
                                    endif; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?= $t('notifications.created') ?>: <?= date('Y-m-d', strtotime($template['created_at'])) ?>
                                </small>
                                <div>
                                    <?php if ($template['is_active']): ?>
                                        <span class="badge bg-success"><?= $t('notifications.active') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $t('notifications.inactive') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="useTemplate('<?= $h($template['slug']) ?>')">
                                    <i class="fas fa-plus"></i> <?= $t('notifications.use_template') ?>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        onclick="previewTemplate('<?= $h($template['slug']) ?>')">
                                    <i class="fas fa-eye"></i> <?= $t('notifications.preview') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="<?= base_url('/notifications') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $t('common.back') ?>
            </a>
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $t('notifications.template_preview') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="templatePreviewContent">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('common.close') ?></button>
                <button type="button" class="btn btn-primary" id="useTemplateBtn"><?= $t('notifications.use_this_template') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTemplateSlug = null;

function useTemplate(slug) {
    // Redirect to create notification form with template pre-filled
    window.location.href = '<?= base_url('/notifications/create') ?>?template=' + slug;
}

function previewTemplate(slug) {
    currentTemplateSlug = slug;
    
    // For now, show a simple preview
    // In a real implementation, you might want to fetch template details via AJAX
    const previewContent = `
        <div class="alert alert-info">
            <h6><?= $t('notifications.template_preview_note') ?></h6>
            <p><?= $t('notifications.template_preview_description') ?></p>
        </div>
        <div class="card">
            <div class="card-body">
                <h6><?= $t('notifications.preview_title') ?>:</h6>
                <p class="mb-3">[Template title with variables]</p>
                <h6><?= $t('notifications.preview_message') ?>:</h6>
                <p>[Template message with variables]</p>
            </div>
        </div>
    `;
    
    document.getElementById('templatePreviewContent').innerHTML = previewContent;
    
    const modal = new bootstrap.Modal(document.getElementById('templatePreviewModal'));
    modal.show();
}

document.getElementById('useTemplateBtn').addEventListener('click', function() {
    if (currentTemplateSlug) {
        useTemplate(currentTemplateSlug);
    }
});
</script>
