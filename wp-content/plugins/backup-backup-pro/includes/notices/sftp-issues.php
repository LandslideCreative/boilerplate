<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\External\BMI_External_SFTP as SFTP;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  require_once BMI_PRO_INC . 'external/sftp.php';

  $sftpIssue = get_transient('bmip_sftp_issue');
  
  if (!$sftpIssue) return;

  // Only create the SFTP instance (and trigger connection) when there's an issue to verify
  $sftp = new SFTP();

  switch ($sftpIssue) {
    case 'disconnected':
        if (bmi_get_config('STORAGE::EXTERNAL::SFTP') != 'true') return;
        if ($sftp->verifyConnection()['result'] == 'connected') {
            delete_transient('bmip_sftp_issue');
            return;
        }
        $message = sprintf(
        __('There was an error authenticating your SFTP server. Please re-authenticate, or click %shere%s to disable SFTP as an external storage option.', 'backup-backup'),
        '<a href="javascript:document.getElementById(\'bmi-error-dismiss\').click();document.getElementById(\'bmi-pro-storage-sftp-toggle\').checked=false;document.querySelector(\'#storage-options .save-btn\').click(); setTimeout(()=>{window.location.reload()}, 500);">',
        '</a>'
        );
    
        break;
    default:
        return;
  }

  if (!isset($message) || get_option('bmip_sftp_dismiss_issue', false)) return;
?>


<div class="error-noticer" id="sftp-issues">
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