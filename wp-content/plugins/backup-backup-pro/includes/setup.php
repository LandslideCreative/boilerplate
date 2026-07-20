<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Replace plugin actions for even better notice
  require_once BMI_PRO_INC . 'bodies/plugin-actions.php';

  // Notices
  require_once BMI_PRO_INC . 'notices/compatibility.php';

  // Automation (utilities for notices)
  require_once BMI_PRO_INC . 'automation/activation.php';
  require_once BMI_PRO_INC . 'automation/dynamic-script.php';
  require_once BMI_PRO_INC . 'automation/installation.php';
