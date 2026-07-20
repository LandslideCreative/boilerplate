<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }
  
  add_action('bmi_premium_status', function () {
    ?>
    <div class="f16 right-align">
      <span class="light"><?php _e('Premium license status:', 'backup-backup') ?></span>
      <span class="semibold"><?php echo $this->data('s'); ?></span>
      (<a href="#" id="bmi-manage-licenses" class="hoverable secondary"><?php _e('manage', 'backup-backup') ?></a>)
    </div>
    <?php
  });
