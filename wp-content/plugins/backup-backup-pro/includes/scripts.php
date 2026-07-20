<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Enqueue scripts & styles only on BMI settings (for better performance)
  add_action('admin_enqueue_scripts', function () {
    if (get_current_screen()->id != 'toplevel_page_backup-migration') return;
    wp_enqueue_script('backup-migration-pro-script', (BMI_PRO_ASSETS . 'js/backup-migration-pro.min.js'), ['jquery'], BMI_PRO_VERSION, true);
    wp_enqueue_style('backup-migration-pro-style', (BMI_PRO_ASSETS . 'css/bmi-premium.min.css'), [], BMI_PRO_VERSION);
  }, 2000);
