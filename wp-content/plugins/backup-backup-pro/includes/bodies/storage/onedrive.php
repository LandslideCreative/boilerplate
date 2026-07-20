<?php

  // Namespace
  namespace BMI\Plugin;
  use BMI\Plugin\Dashboard as Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Onedrive template
  add_action('bmi_pro_one_drive_template', function () {
    $wid = get_option('bmi_pro_onedrive_wid', false);
    $shouldBeConnected = is_string($wid);

    $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::ONEDRIVE');
    if ($isEnabled === true || $isEnabled === 'true') {
      $isEnabled = ' checked';
    } else $isEnabled = '';

    ?>
      <!-- External: Onedrive -->
      <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">
        <div class="d-flex ia-center">
          <img src="<?php echo $this->get_asset('images', 'one-drive.svg') ?>" alt="logo" class="tab2-img">
          <span class="ml25 d-flex ia-center">
            <span class="title_whereStored"><?php _e("OneDrive", 'backup-backup'); ?></span>
            <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="logo" class="crown2">
          </span>
        </div>
        <div class="ia-center">
          <div class="b2 bmi-switch"><input type="checkbox" class="checkbox storage-checkbox"<?php echo $isEnabled; ?> data-toggle="storage-onedrive-row" id="bmi-pro-storage-onedrive-toggle">
            <div class="bmi-knobs"><span></span></div>
            <div class="bmi-layer_str"></div>
          </div>
        </div>
      </div>
      <div class="bg_grey storage_target" id="storage-onedrive-row"<?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <?php
        $disabled_functions = explode(',', ini_get('disable_functions'));
        $vA = !in_array('curl_exec', $disabled_functions);
        $vB = !in_array('curl_init', $disabled_functions);

        if (function_exists('curl_version') && function_exists('curl_exec') && function_exists('curl_init') && $vA && $vB) {
          ?>

          <div id="onedrive-unauthenticated-box" class="container-40 lh30 pt30 pb30" <?php echo (($shouldBeConnected) ? 'style="display: none;"' : ''); ?>>
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;"><span id="onedrive-not-authed-content-box">Current status:&nbsp;<b>Inactive</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                            <a href="#" id="onedrive-connect-btn" class="btn"><?php _e("Connect", 'backup-backup'); ?></a>
      					</div>
      				</div>
      			</div>
            <div class="d-flex">
              <blockquote class="bmi-onedrive-info">
                The plugin only asks for the permissions to see or edit the files in the plugin's app specific folder, which should be at "Apps->Backup Migration Plugin" in your OneDrive account.
              </blockquote>
            </div>
          </div>
          <div id="onedrive-authenticated-box" class="container-40 lh30 pt30 pb30" <?php echo ((!$shouldBeConnected) ? 'style="display: none;"' : ''); ?>>

            <div class="d-flex">
      				<div class="w270" style="margin-top: 11px;"><span id="onedrive-authed-content-box">Current status:&nbsp;<b>Active</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                  <a href="#" id="onedrive-disconnect-btn" class="btn"><?php _e("Disconnect", 'backup-backup'); ?></a>
      					</div>
      				</div>
      			</div>

          </div>
          <?php
        } else {
          ?>
          <div class="container-40 lh30 pt30 pb30">
            <div class="center">
              It seem like you don't have cURL extension (PHP module) installed on your server.<br />
              Without this module it's impossible to upload backups to OneDrive.<br />
              If you wish to use this feature, please enable cURL module.
            </div>
          </div>
          <?php
        }
        ?>
      </div>
    <?php
  });
