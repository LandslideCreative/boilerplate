<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

?>

<div class="bmi-modal" id="bmi-premium-license">

  <div class="bmi-modal-wrapper no-hpad no-vpad" style="max-width: 750px; max-width: min(750px, 80vw);">
    <a href="#" class="bmi-modal-close">×</a>
    <div class="bmi-modal-content">

      <div class="brand-color-bg premium-header">
        Premium License
      </div>
      <p data-slug="backup-migration-pro">
        Welcome to <strong>Backup Migration Premium</strong><b>!</b><br>
        <?php echo $this->data('r'); ?>
      </p>

      <div class="premium-fields">
        <input type="text" id="premium-license" class="bmi-text-input"<?php echo $this->data('l') ?> placeholder="Please enter your license key here!"<?php echo $this->data('d'); ?>>
        <a href="#" class="btn inline btn-pad othersec mm30" id="premium-submit">
          <div class="text">
            <div class="f18 semibold"><?php echo $this->data('b'); ?></div>
          </div>
        </a>
        <div class="mblll f16 mtlll" <?php echo (!empty($this->data('d')) ? 'style="display: none;"' : '') ?>>
          You can purchase the license key here: <a href="https://backupbliss.com" target="_blank">BackupBliss.com</a>
        </div>
      </div>

      <?php
        if ($this->data('z') === 'yeah') {
          ?><script type="text/javascript">setTimeout(function (){jQuery.bmi.modal('bmi-premium-license').open();}, 600)</script><?php
        }
      ?>

    </div>
  </div>

</div>
