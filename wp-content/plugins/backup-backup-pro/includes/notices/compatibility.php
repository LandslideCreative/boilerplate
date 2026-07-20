<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Handle notices with native WordPress module
  add_action('admin_notices', function () {

    // When the PHP Version is below 7.0.0
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
      ?>
      <div class="notice notice-error">
        <p>The Backup Migration Pro requires minimum version of PHP 7.0, plugin won't be active unless you upgrade your PHP.</p>
      </div>
      <?php
    }

    // When openssl_decrypt does not exists (premium files cannot be decrypted)
    if (!function_exists('openssl_decrypt')) {
      ?>
      <div class="notice notice-error">
        <p>We couldn't find "openssl_decrypt" function which is required by Backup Migration Pro, please install OpenSSL extension.</p>
        <p>The plugin will not be active unless you install this extension.</p>
      </div>
      <?php
    }

    // When premium version is newer than free (base have to be newer or equal)
    if (defined('BMI_VERSION') && version_compare(BMI_VERSION, BMI_PRO_VERSION, '<')) {
      ?>
      <div class="notice notice-error">
        <p>Your plugin (Backup Migration Pro) is newer than current (Backup Migration).</p>
        <p>Premium version will be disabled unless you update the free plugin.</p>
      </div>
      <?php
    }

    // When free version is disabled (it's required by premium as base in order to work)
    if (!defined('BMI_ROOT_FILE') && file_exists(dirname(BMI_PRO_ROOT_DIR) . '/backup-backup/backup-backup.php')) {
      ?>
      <div class="notice notice-error">
        <p>The plugin "Backup Migration" is required by "Backup Migration Pro" in order to work, but it's disabled.</p>
        <p>You can activate this plugin by <a id="bmi-pro-quick-action" data-action="activate" href="#">clicking here</a>.</p>
      </div>
      <?php

    // When free version is not even installed on the server.
    } else if (!defined('BMI_ROOT_FILE') && !file_exists(dirname(BMI_PRO_ROOT_DIR) . '/backup-backup/backup-backup.php')) {
      ?>
      <div class="notice notice-error" id="bmi-install-notice">
        <p>We couldn't find "Backup Migration" plugin which is required by "Backup Migration Pro" in order to work.</p>
        <p id="bmi-install-p">You can install this plugin automatically by <a href="#" id="bmi-pro-quick-action" data-action="install">clicking here</a>.</p>
        <p id="bmi-ongoing-p" style="display: none;">Installing please wait, we will redirect you once it finish...</p>
      </div>
      <?php
    }
    
  });
