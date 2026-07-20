<?php

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

// Notices
add_action('admin_notices', function () {
  if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    ?>
    <div class="notice notice-error">
      <p>The Backup Migration Pro requires minimum version of PHP 7.0, plugin won't be active unless you upgrade your PHP.</p>
    </div>
    <?php
  }

  if (!function_exists('openssl_decrypt')) {
    ?>
    <div class="notice notice-error">
      <p>We couldn't find "openssl_decrypt" function which is required by Backup Migration Pro, please install OpenSSL extension.</p>
      <p>The plugin will not be active unless you install this extension.</p>
    </div>
    <?php
  }

  if (defined('BMI_VERSION') && version_compare(BMI_VERSION, BMI_PRO_VERSION, '<')) {
    ?>
    <div class="notice notice-error">
      <p>Your plugin (Backup Migration Pro) is newer than current (Backup Migration).</p>
      <p>Premium version will be disabled unless you update the free plugin.</p>
    </div>
    <?php
  }

  if (!defined('BMI_ROOT_FILE') && file_exists(dirname(BMI_PRO_ROOT_DIR) . '/backup-backup/backup-backup.php')) {
    ?>
    <div class="notice notice-error">
      <p>The plugin "Backup Migration" is required by "Backup Migration Pro" in order to work, but it's disabled.</p>
      <p>You can activate this plugin by <a id="bmi-pro-quick-action" data-action="activate" href="#">clicking here</a>.</p>
    </div>
    <?php
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

add_action('in_admin_footer', function () {
  ?>
  <script type='text/javascript'>
    jQuery(document).ready(function($) {
      $('#bmi-pro-quick-action').on('click', function () {

        $('#bmi-install-p').hide(300);
        $('#bmi-ongoing-p').show(300);

        let o = $(this).attr('data-action');
        $.post(ajaxurl, { action: 'bmi_pro_plugin_install', nonce: '<?php echo wp_create_nonce('backup-migration-pro-ajax'); ?>', type: o }).done((res) => {

          if (typeof res.status === 'undefined') {
            res = JSON.parse(res.slice(res.indexOf('{')));
          }

          $('#bmi-ongoing-p').hide(300);
          if (res.status === 'success') window.location.reload();
          else {
            $('#bmi-ongoing-p').text('Plugin failed to install, please try again or do it manually.');
            setTimeout(function () { window.location = res.url; }, 2000);
          }
          $('#bmi-ongoing-p').show(300);

        }).fail(() => {
          $('#bmi-ongoing-p').hide(300);
          setTimeout(function () {
            $('#bmi-ongoing-p').text('We could not install the plugin, please check your internet connection and server status.');
          }, 310);
        });

      });
    });
  </script>
  <?php
});

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

add_action('admin_enqueue_scripts', function () {
  // Enqueue before update script
  $screenId = get_current_screen()->id;
  if (in_array($screenId, ['update-core', 'plugins', 'plugin-install', 'themes','customize', 'plugins-network', 'plugin-install-network', 'themes-network'])){
    wp_enqueue_script('bmi-pro-before-update', (BMI_PRO_ASSETS . 'js/before-update-interceptor.min.js'), ['jquery'], BMI_PRO_VERSION, true);
  }

  if ($screenId != 'toplevel_page_backup-migration') return;
  wp_enqueue_script('backup-migration-pro-script', (BMI_PRO_ASSETS . 'js/backup-migration-pro.min.js'), ['jquery'], BMI_PRO_VERSION, true);
  wp_enqueue_style('backup-migration-pro-style', (BMI_PRO_ASSETS . 'css/bmi-premium.min.css'), [], BMI_PRO_VERSION);
}, 2000);

add_filter('plugin_action_links_' . plugin_basename(BMI_PRO_ROOT_FILE), function ($links) {
  if (is_plugin_active('backup-backup/backup-backup.php')) {
    $text = __('Manage', 'backup-backup');
    $links['bmi-settings-link'] = '<a href="' . admin_url('/admin.php?page=backup-migration') . '">' . $text . '</a>';
  } else {
    $text = __('Free version is missing', 'backup-backup');
    $links['bmi-settings-link'] = '<span style="color: #666;">' . $text . '</span>';
  }

  return $links;
});
