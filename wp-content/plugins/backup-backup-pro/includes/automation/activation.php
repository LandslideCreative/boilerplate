<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Ajax handler for auto-activation of free version
  add_action('wp_ajax_bmi_pro_plugin_install', function () {

    if (check_ajax_referer('backup-migration-pro-ajax', 'nonce', false) === false) {
      return wp_send_json_error();
    }

    if (!current_user_can('install_plugins')) {
      return wp_send_json_error();
    }

    $action = $_POST['type'];
    if ($action === 'activate') {
      activate_plugin('backup-backup/backup-backup.php');
      update_option('_bmi_redirect', true);
      wp_send_json(['status' => 'success']);
    }

    if ($action === 'install') {
      do_action('bmi_pro_install_free_version');
    }

  });
