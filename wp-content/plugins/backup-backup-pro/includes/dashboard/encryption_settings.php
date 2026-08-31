<?php

use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

$encryptionEnabled = filter_var(
  Dashboard\bmi_get_config('ENCRYPTION:ENABLED'),
  FILTER_VALIDATE_BOOLEAN
);
$encryptionPassword = Dashboard\bmi_get_config('ENCRYPTION:PASSWORD') ?? "";
if (is_array($encryptionPassword)) {
  $encryptionPassword = "";
}
?>
<div class="">
  <div class="lh30 mbll">
    <div class="fo-title semibold">
      <span class="cf premium-wrapper">
        <div class="left"><?php esc_html_e("Encryption", 'backup-backup'); ?></div>
        <span class="left premium premium-img"></span>
      </span>
    </div>
    <div class="f20"><?php esc_html_e("Do you want to encrypt and password protect your files?", 'backup-backup'); ?>
    </div>
    <div class="f16">
      <?php esc_html_e("Please make sure you save this password in a secure place. If you lose it, you will not be able to restore your backup.", 'backup-backup') ?>
    </div>
    <div class="f16">
      <?php esc_html_e("Encrypted backups must be restored using plugin version 2.1.7 or higher. Restoring on older versions will cause site failure.", 'backup-backup'); ?>
    </div>
  </div>

  <div class="mm mm-border d-flex" style="align-items: center;">
    <div class="d-flex ia-center" style="margin-right: 25px;">
      <label class="container-radio" style="margin-bottom:0; display: flex; align-items: center;">
        <?php esc_html_e("No", 'backup-backup'); ?>
        <input type="radio" name="encryption" value="false" <?php echo !$encryptionEnabled ? 'checked' : ''; ?>
          class="bmi_will_collapse" data-if-checked="encryption_password_option">
        <span class="checkmark-radio"></span>
      </label>
      <label class="container-radio ml25 premium-function is-pro"
        style="margin-bottom:0; display: flex; align-items: center;">
        <?php esc_html_e("Yes", 'backup-backup'); ?>
        <input type="radio" name="encryption" value="true" <?php echo $encryptionEnabled ? 'checked' : ''; ?>
          class="bmi_will_collapse" data-if-checked="encryption_password_option" id="storage-encryption-true">
        <span class="checkmark-radio"></span>
      </label>
    </div>

    <div id="encryption_password_option" style="display: <?php echo $encryptionEnabled ? 'block' : 'none'; ?>;">
      <div style="display: flex; align-items: center; margin-top: 5px;">
        <div class="f20" style="margin-right: 15px;">
          <?php esc_html_e("Enter your encryption password:", 'backup-backup'); ?>
        </div>
        <div class="d-flex flexcenter" style="margin-right: 15px; position: relative;">
          <input type="password" name="encryption_password" id="bmi_encryption_password"
            value="<?php echo esc_attr($encryptionPassword); ?>" class="bmi-text-input"
            placeholder="<?php esc_attr_e('Password', 'backup-backup'); ?>">
          <a href="#" class="" id="bmi_toggle_password_visibility"
            title="<?php esc_attr_e('Show/Hide Password', 'backup-backup'); ?>"
            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); color: #2b7a71; font-size: 20px; text-decoration: none; display: flex; align-items: center;">
            <span class="dashicons dashicons-visibility" id="bmi_toggle_icon"
              style="font-size:22px; width:22px; height:22px;"></span>
          </a>
        </div>

        <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad left bmi-copper mm30"
          data-copy="bmi_encryption_password">
          <div class="text">
            <img src="<?php echo esc_url($this->get_bmi_free_asset('images', 'copy-icon.png')); ?>" alt="copy-img">
            <div class="f18 semibold"><?php esc_html_e('Copy', 'backup-backup'); ?></div>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>
<?php

