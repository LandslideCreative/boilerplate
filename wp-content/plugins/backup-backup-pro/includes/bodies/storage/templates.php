<?php

  // Namespace
  namespace BMI\Plugin;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * Templates Manager
   */
  class BMI_Pro_Templates {

    /**
     * __construct - Initializer (loads active templates)
     */
    function __construct() {

      // Active templates

      require_once BMI_PRO_INC . 'bodies/storage/onedrive.php';
      
      require_once BMI_PRO_INC . 'bodies/storage/sftp.php';

      require_once BMI_PRO_INC . 'bodies/storage/pcloud.php';

    }

    /**
     * get_asset - Assets helper
     */
    private function get_asset($base = '', $asset = '') {

      return BMI_ASSETS . '/' . $base . '/' . $asset;

    }

  }
