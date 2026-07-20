<?php

  /**
   * Plugin Name: Backup Migration Pro
   * Description: Premium version of the “Backup Migration” plugin (please keep the free one activated).
   *      Author: Inisev
   *  Author URI: https://inisev.com
   *  Plugin URI: https://backupbliss.com
   * Text Domain: backup-backup
   *     Version: 2.1.6
   * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

  // Default namespace
  namespace BMI\Plugin;
  use BMI\Plugin\BMI_Pro_Core;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Not dynamic constants
  if (!defined('BMI_PRO_VERSION')) {
    define('BMI_PRO_VERSION', '2.1.6');
  }
  if (!defined('BMI_PRO_DEBUG')) {
    define('BMI_PRO_DEBUG', false);
  }
  if (!defined('BMI_PRO_ROOT_DIR')) {
    define('BMI_PRO_ROOT_DIR', __DIR__);
  }
  if (!defined('BMI_PRO_ROOT_FILE')) {
    define('BMI_PRO_ROOT_FILE', __FILE__);
  }
  if (!defined('BMI_PRO_INC')) {
    define('BMI_PRO_INC', trailingslashit(BMI_PRO_ROOT_DIR) . 'includes/');
  }
  if (!defined('BMI_PRO_ASSETS')) {
    define('BMI_PRO_ASSETS', plugin_dir_url(BMI_PRO_ROOT_FILE) . 'admin/');
  }
  if (!defined('BMI_PRO_CORE_FILE')) {
    define('BMI_PRO_CORE_FILE', BMI_PRO_ROOT_DIR . '/classes/core' . ((BMI_PRO_DEBUG) ? '.to-enc.php' : '.php'));
  }
  if (!defined('BMI_PRO_UPDATER_FILE')) {
    define('BMI_PRO_UPDATER_FILE', BMI_PRO_ROOT_DIR . '/classes/updates' . ((BMI_PRO_DEBUG) ? '.to-enc.php' : '.php'));
  }

  // Activation hook
  register_activation_hook(BMI_PRO_ROOT_FILE, function () {
    update_option('_bmi_redirect', true);
  });

  register_deactivation_hook(BMI_PRO_ROOT_FILE, function () {
    require_once BMI_PRO_INC . 'cron/bootstrap.php';
    try{
      \BMI\Plugin\CRON\TaskManager::boot();
      \BMI\Plugin\CRON\TaskManager::on_deactivation();
    } catch (\Exception $e) {
      // silent
    } catch (\Throwable $t) {
      // silent
    }
  });

  function bmip_uninstall_handler() {
    require_once BMI_PRO_ROOT_DIR . '/uninstall.php';
  }

  register_uninstall_hook(BMI_PRO_ROOT_DIR, 'bmip_uninstall_handler');

  // Load plugin after all
  add_action('init', function () {

    $scheduled_cron = false;
    $crons = get_option('cron', false);
    if ($crons != false) {
      foreach ($crons as $timestamp => $cronhooks) {
        if (isset($cronhooks['bmi_do_backup_right_now'])) {
          if (microtime(true) >= $timestamp) $scheduled_cron = true;
        }
      }
    }

    $isCLI = (defined('STDIN') || (defined('PHP_SAPI') && PHP_SAPI == 'cli') || php_sapi_name() === 'cli') ? true : false;
    $isCurlBackup = strpos($_SERVER['REQUEST_URI'], '?backup-migration=CURL_BACKUP' ) !== false;
    $isBackupByURL = strpos($_SERVER['REQUEST_URI'], '?backup-migration=BACKUP_BY_URL') !== false;
    if ($isCurlBackup) define('BMI_CURL_REQUEST', true);

    // Check requirements
    $newerThanFree = false;
    if (defined('BMI_VERSION')) $newerThanFree = version_compare(BMI_VERSION, BMI_PRO_VERSION, '<');
    if (version_compare(PHP_VERSION, '7.0.0', '<') || !function_exists('openssl_decrypt') || !defined('BMI_ROOT_FILE') || $newerThanFree) {
      if (is_admin() || $isCLI) require_once BMI_PRO_INC . 'misc.php';
      return;
    }

    // Initialize backup-migration-pro
    if (!class_exists('BMI_Pro_Core') && file_exists(BMI_PRO_CORE_FILE) && file_exists(BMI_PRO_UPDATER_FILE)) {
      if (is_admin() || $isCLI || strpos($_SERVER['REQUEST_URI'], 'doing_wp_cron') !== false || $scheduled_cron || strpos($_SERVER['REQUEST_URI'], '?backup-migration=') !== false) {
        require_once BMI_PRO_INC . 'misc.php';
        require_once BMI_PRO_INC . 'class-before-update-controller.php';

        // Disabled POST functions
        $disabled = ['is-running-backup', 'scan-directory', 'calculate-cron', 'get-dynamic-names'];

        // Verify (Don't use this plugin outside BMI Settings)
        $method = '';
        if (isset($_SERVER['REQUEST_METHOD'])) $method = $_SERVER['REQUEST_METHOD'];
        if (
          $scheduled_cron 
          || defined('BMI_CURL_REQUEST') 
          || $method === 'POST' 
          || strpos($_SERVER['REQUEST_URI'], 'backup-migration') !== false 
          || $isCLI 
          || defined('DOING_CRON') 
          || defined('BMI_DOING_SCHEDULED_BACKUP')
        ) {

          if (
            $scheduled_cron
            || defined('BMI_CURL_REQUEST') 
            || (
              isset($_POST['token']) 
              && isset($_POST['f']) 
              && !in_array($_POST['f'], $disabled) 
              && $_POST['token'] == 'bmi'
            )
            || (
              $method === 'GET' 
              || $isCLI 
              || defined('BMI_DOING_SCHEDULED_BACKUP') 
            )
            || defined('DOING_CRON')
          ) {
            require_once BMI_PRO_CORE_FILE;
            $bmi_pro_instance = new BMI_Pro_Core($scheduled_cron);
            $bmi_pro_instance->initialize();
          }
        }

        // Handle updates
        if (!class_exists('BMI_Pro_Updater')) {
          require_once BMI_PRO_UPDATER_FILE;
          $updater = new BMI_Pro_Updater(BMI_PRO_ROOT_FILE, ['version' => BMI_PRO_VERSION]);
        }

      }
    } else if (!file_exists(BMI_PRO_CORE_FILE) || !file_exists(BMI_PRO_UPDATER_FILE)) {
      add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error">
          <p>
            <b>Backup & Migration Premium Plugin</b>: Something removed our core and auto-updater file, it could be done by some security plugin or your hosting provider security solutions.
            In order to resolve the issue, you need to reinstall our plugin, you can download latest version from <a href="https://sellcodes.com" target="_blank">SellCodes.com</a> anytime.
          </p>
        </div>
        <?php
      });
    }

  }, 11);
  