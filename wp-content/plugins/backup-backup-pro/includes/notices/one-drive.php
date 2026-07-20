<?php
// Exit on direct access
if (!defined('ABSPATH')) exit;

use BMI\Plugin\External\BMI_External_OneDrive as OneDrive;
use BMI\Plugin\Dashboard as Dashboard;




if (!defined("BMI_PRO_INC"))
  return;

$isOneDriveEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::ONEDRIVE');

if (!($isOneDriveEnabled === true || $isOneDriveEnabled === 'true'))
  return;

if (file_exists(BMI_PRO_INC . 'external/one-drive.php')) {
  require_once BMI_PRO_INC . 'external/one-drive.php';
  $onedrive = new OneDrive();
  if (count($onedrive->getNotices()) == 0) 
    return;
?>

  <div class="error-noticer" id="onedrive-issues">
    <div class="error-header">
      <div class="cf">
        <div class="left">
          <?php _e('We have some error(s) regarding OneDrive.', 'backup-backup'); ?>
        </div>
        <div class="right hoverable">
          <span class="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-backup'); ?>" data-collapse="<?php _e('Collapse', 'backup-backup'); ?>">
            <?php _e('Expand', 'backup-backup'); ?>
          </span> |
          <span class="bmi-error-dismiss" issue-type="onedrive" onclick="document.getElementById('onedrive-issues').remove()">
            <?php _e('Dismiss', 'backup-backup'); ?>
          </span>
        </div>
      </div>
    </div>
    <div class="error-body">
      <?php
        echo implode("<br /><br />", $onedrive->getNotices());
      ?>
    </div>
  </div>
  <?php } ?>