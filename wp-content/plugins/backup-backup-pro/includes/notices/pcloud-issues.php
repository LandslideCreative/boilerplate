<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\External\BMI_External_PCloud as PCloud;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  require_once BMI_PRO_INC . 'external/pcloud.php';
  $pcloud = new PCloud();

  $pcloudIssue = get_transient('bmip_pcloud_issue');
  wp_load_alloptions(true);
  $expireTime = get_option('_transient_timeout_bmip_pcloud_issue', time() + HOUR_IN_SECONDS);
  $timeToRetry = human_time_diff($expireTime, time());
  
  if (!$pcloudIssue) return;

  switch ($pcloudIssue) {
    case 'auth_error_disconnected':
      if ($pcloud->verifyConnection()['result'] == 'connected') {
        delete_transient('bmip_pcloud_issue');
        return;
      }
      $message = sprintf(
        __('There was an error authenticating your pCloud account. Please click %shere%s to re-authenticate, or click %shere%s to disable pCloud as an external storage option.', 'backup-backup'),
        '<a href="javascript:document.querySelector(\'#pcloud-issues #bmi-error-dismiss\').click();document.getElementById(\'pcloud-connect-btn\').click();">',
        '</a>',
        '<a href="javascript:document.querySelector(\'#pcloud-issues #bmi-error-dismiss\').click();document.getElementById(\'bmi-pro-storage-pcloud-toggle\').checked=false;document.querySelector(\'#storage-options .save-btn\').click(); setTimeout(()=>{window.location.reload()}, 500);">',
        '</a>'
      );
      break;
      case 'insufficient_space':
        $spaceUsage = $pcloud->getSpaceUsage();
        if ($spaceUsage === false) return;
        $usagePrecentages = $spaceUsage['usedquota'] / $spaceUsage['quota'] * 100;

        $requiredSpace = get_option('bmip_pcloud_required_space', 0);
        $requiredSpace = intval($requiredSpace);
        $availableSpace = $spaceUsage['quota'] - $spaceUsage['usedquota'];
    
        if ($availableSpace >= $requiredSpace) {
          delete_transient('bmip_pcloud_issue');
          delete_option('bmip_pcloud_required_space');
          return;
        }    

        $message = sprintf(
          __('Your pCloud account doesn’t have enough space to upload the backup. You’ve used %s out of %s (%s). The plugin needs %s of free space to complete the upload. It will automatically retry in %s.', 'backup-backup'),
          BMP::humanSize($spaceUsage['usedquota']),
          BMP::humanSize($spaceUsage['quota']),
          number_format($usagePrecentages) . '%',
          BMP::humanSize($requiredSpace),
          $timeToRetry
        );
        break;
      default:
        return;
  }

  if (!isset($message) || get_option('bmip_pcloud_dismiss_issue', false)) return;
?>


<div class="error-noticer" id="pcloud-issues">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('We have some error regarding most recent backup upload process.', 'backup-backup'); ?>
      </div>
      <div class="right hoverable">
        <span class="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-backup'); ?>" data-collapse="<?php _e('Collapse', 'backup-backup'); ?>">
          <?php _e('Expand', 'backup-backup'); ?>
        </span> |
        <span class="bmi-error-dismiss">
          <?php _e('Dismiss', 'backup-backup'); ?>
        </span>
      </div>
    </div>
  </div>
  <div class="error-body">
    <?php echo $message; ?>
  </div>
</div>