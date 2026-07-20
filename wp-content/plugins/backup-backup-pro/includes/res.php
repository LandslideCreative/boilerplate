<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Response codes
  if (!function_exists('bmi_code')) {
    function bmi_code($code) {

      // All codes used by core file
      $byCode = [
        '1' => 'Unknown verification method [#001].',
        '2' => 'There was an error, while getting data. Please check your server internet connection [#002].',
        '3' => 'You have to enter license to do this action [#003]',
        '4' => 'There was an error with product id, please report this [#004].',
        '5' => 'There was an error with product id, please report this [#005].',
        '6' => 'Unknown error occurred, please refresh the page and try again [#006].',
        '7' => 'Unknown response, please refresh the page and try again [#007].',
        '8' => 'Provided license key reached max activation count, if you want to transfer the license you have to de-activate it on the site you currently have it activated [#008].',
        '9' => 'Failed to recognize method [#009].',
        '10' => 'Failed to recognize method [#010].',
        '11' => 'Provided license key expired or the seller disabled it [#011].',
        '12' => 'License key is not valid is it for this product? [#012].',
        '13' => 'There was an error with product id, please report this [#013].',
        '14' => 'There was an error with product id, please report this [#014].',
        '15' => 'Unknown error occurred, please refresh the page and try again [#015].',
        '16' => 'Unknown response, please refresh the page and try again [#016].',
        '17' => 'Provided license key reached max activation count, if you want to transfer the license you have to de-activate it on the site you currently have it activated [#017].',
        '18' => 'Unknown response, please refresh the page and try again [#018].',
        '19' => 'Provided license key has been disabled by seller [#019].',
        '20' => 'System was not able to read server response, please try again later or contact support [#020].',
        '21' => 'Provided license key is not valid, please double check [#021].',
        '22' => 'The license might be valid but please update the premium plugin using WordPress upgrade section or download the newest version directly from SellCodes! [#022]',
        '23' => 'Unknown response, please refresh the page and try again [#023].',
        '24' => 'Your license key is not active, ask seller for activation [#024].',
        's1' => 'License has been activated, we will reload the page soon for you!',
        's2' => 'License has been successfully deactivated, we will reload the page soon for you!',
        's3' => 'License was not activated, we will reload the page for you!',
        'r1' => 'The License key for given Product is inactive',
        'r2' => 'Invalid or missing License Key',
        'r3' => 'Invalid or missing Product Id,',
        'r4' => 'Invalid or missing Product Id, Invalid or missing License Key',
        'd1' => 'Coming soon!',
        'd2' => ('<b>' . __('stay tuned!', 'backup-backup') . '</b>'),
        'm1' => 'Your license is valid and active, you can deactivate it if you wish!',
        'm2' => 'The license status is suspended, author could turn it off or it expired.',
        'm3' => 'Before we turn on those awesome features, please activate the plugin!'
      ];

      // Unknown response (probably crack-try)
      if (!isset($byCode[$code])) return 'Unknown response code - please report this issue [#099]';
      else return $byCode[$code];

    }
  }
