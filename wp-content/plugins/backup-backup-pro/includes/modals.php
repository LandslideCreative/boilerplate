<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }
  
  add_action('bmi_load_premium_modals', function () {
    require_once BMI_PRO_INC . 'activation-modal.php';
  });
