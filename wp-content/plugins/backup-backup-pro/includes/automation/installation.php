<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Ajax for free version auto-installation
  add_action('bmi_pro_install_free_version', function () {

    function is_plugin_installed($slug) {
      $all_plugins = get_plugins();

      if (!empty($all_plugins[$slug])) return true;
      else return false;
    }

    function install_plugin($plugin_zip) {
      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      wp_cache_flush();

      $upgrader = new Plugin_Upgrader();
      $installed = $upgrader->install($plugin_zip);

      return $installed;
    }

    function upgrade_plugin($plugin_slug) {
      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      wp_cache_flush();

      $upgrader = new Plugin_Upgrader();
      $upgraded = $upgrader->upgrade($plugin_slug);

      return $upgraded;
    }

    $plugin_slug = 'backup-backup/backup-backup.php';
    $plugin_zip = 'https://downloads.wordpress.org/plugin/backup-backup.latest-stable.zip';

    if (is_plugin_installed($plugin_slug)) {
      upgrade_plugin($plugin_slug);
      $installed = true;
    } else {
      $installed = install_plugin($plugin_zip);
    }

    if (!is_wp_error($installed) && $installed) {
      $activate = activate_plugin($plugin_slug);

      if (is_null($activate)) {
        update_option('_bmi_cool_installation', true);
        update_option('_bmi_redirect', true);
        wp_send_json(['status' => 'success']);
      }

    } else {
      wp_send_json(['status' => 'fail', 'url' => admin_url('/plugin-install.php?s=Migrate&tab=search&type=author')]);
    }

  });
