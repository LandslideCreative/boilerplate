<?php

  // Namespace
  namespace BMI\Plugin\Uninstaller\Premium;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  $configFile = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backup-migration-config.php';

  if (!file_exists($configFile)) {
    return;
  }

  $config = file_get_contents($configFile);
  $config = json_decode(substr($config, 8), true);

  $deleteConfigs = $config['OTHER:UNINSTALL:CONFIGS'];

  if ($deleteConfigs === 'true' || $deleteConfigs === true) {

    global $wpdb;

    $pro_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%bmi_pro_%' OR option_name LIKE '%bmip_%'" );

    foreach( $pro_options as $option ) {
        delete_option( $option->option_name );
    }
  }