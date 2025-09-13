<?php 
use function App\Core\base_url;

// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<section>
  <h2><?= $t('integrations.integrations') ?></h2>
  <p><?= $t('integrations.integrations_description') ?></p>
  <p class="no-print" style="margin-top:8px;"><a href="<?= base_url('/') ?>"><?= $t('common.back') ?></a></p>
</section>

