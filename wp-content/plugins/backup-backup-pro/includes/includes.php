<?php

  // Namespace
  namespace BMI\Plugin;

  use BMI\Plugin\Handlers\RequestHandler;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Includes 
  require_once BMI_PRO_INC . 'handlers' .  DIRECTORY_SEPARATOR . 'class-bmi-request-handler.php';
  new RequestHandler();