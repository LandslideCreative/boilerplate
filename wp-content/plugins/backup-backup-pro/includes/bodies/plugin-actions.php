<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Replace text in plugin list
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
