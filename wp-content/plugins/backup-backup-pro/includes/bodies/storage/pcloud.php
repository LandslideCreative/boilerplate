<?php

  // Namespace
  namespace BMI\Plugin;
  use BMI\Plugin\Dashboard as Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // pCloud template
  add_action('bmi_pro_pcloud_template', function () {
    $pcloud_auth_code = get_option('bmip_pcloud_auth_code', false);
    $pcloud_access_token = get_option('bmip_pcloud_access_token', false);
    $shouldBeConnected = false;
    if (is_string($pcloud_access_token) && is_string($pcloud_auth_code)) {
      $shouldBeConnected = true;
    }

    $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::PCLOUD');
    if ($isEnabled === true || $isEnabled === 'true') {
      $isEnabled = ' checked';
    } else $isEnabled = '';

    ?>
      <!-- External: pCloud -->
      <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">
        <div class="d-flex ia-center">
          <img src="<?php echo $this->get_asset('images', 'pcloud.svg') ?>" alt="logo" class="tab2-img">
          <span class="ml25 d-flex ia-center">
            <span class="title_whereStored"><?php _e("pCloud", 'backup-backup'); ?></span>
            <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="logo" class="crown2">
          </span>
        </div>
        <div class="ia-center">
          <div class="b2 bmi-switch"><input type="checkbox" class="checkbox storage-checkbox"<?php echo $isEnabled; ?> data-toggle="storage-pcloud-row" id="bmi-pro-storage-pcloud-toggle">
            <div class="bmi-knobs"><span></span></div>
            <div class="bmi-layer_str"></div>
          </div>
        </div>
      </div>
      <div class="bg_grey storage_target" id="storage-pcloud-row"<?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <?php
        $disabled_functions = explode(',', ini_get('disable_functions'));
        $vA = !in_array('curl_exec', $disabled_functions);
        $vB = !in_array('curl_init', $disabled_functions);

        if (function_exists('curl_version') && function_exists('curl_exec') && function_exists('curl_init') && $vA && $vB) {
          ?>

          <div id="pcloud-unauthenticated-box" class="container-40 lh30 pt30 pb30" <?php echo (($shouldBeConnected) ? 'style="display: none;"' : ''); ?>>
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;"><span id="pcloud-not-authed-content-box">Current status:&nbsp;<b>Inactive</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                            <a href="#" id="pcloud-connect-btn" class="btn external-storage-btn-connection"><?php _e("Connect", 'backup-backup'); ?></a>
      					</div>
      				</div>
      			</div>
            <div class="d-flex">
              <blockquote class="bmi-pcloud-info">
                <?php echo sprintf(
                  __('The plugin only requests the permissions it needs to function and can view or edit only the files it creates within the app folder—nothing else. If you’re concerned, you can set up a new, empty pCloud account %shere%s and provide access to that.', 'backup-backup'),
                  '<a href="https://www.pcloud.com/register" target="_blank">',
                  '</a>'
                ); ?>

              </blockquote>
            </div>
          </div>
          <div id="pcloud-authenticated-box" class="container-40 lh30 pt30 pb30" <?php echo ((!$shouldBeConnected) ? 'style="display: none;"' : ''); ?>>

            <div class="d-flex">
      				<div class="w270" style="margin-top: 11px;"><span id="pcloud-authed-content-box">Current status:&nbsp;<b>Active</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                  <a href="#" id="pcloud-disconnect-btn" class="btn external-storage-btn-connection"><?php _e("Disconnect", 'backup-backup'); ?></a>
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
              Without this module it's impossible to upload backups to pCloud.<br />
              If you wish to use this feature, please enable cURL module.
            </div>
          </div>
          <?php
        }
        ?>
      </div>
    <?php
  });
