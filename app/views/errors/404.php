<?php
// Translation helper
require_once __DIR__ . '/../../core/helpers.php';
$t = fn($key) => \App\Core\t($key);
?>
<section>
  <h2><?= $t('errors.404_title') ?></h2>
  <p><?= $t('errors.404_message') ?></p>
</section>
