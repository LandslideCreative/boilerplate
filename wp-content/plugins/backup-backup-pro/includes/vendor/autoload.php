<?php 


require_once BMI_PRO_INC . 'autoload.php';

$loader = new \BMI\Plugin\Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('phpseclib',  BMI_PRO_INC . 'vendor/phpseclib');
$loader->addNamespace( 'ParagonIE\\ConstantTime',  BMI_PRO_INC . 'vendor/paragonie/constant_time_encoding/src');
