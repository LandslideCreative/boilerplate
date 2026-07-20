<?php

// Namespace
namespace BMI\Plugin;

// Uses
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\BMI_Pro_Core;
use BMI\Plugin\BMProAjax as BMProAjax;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\External\BMI_External_PCloud as PCloud;
use BMI\Plugin\External\BMI_External_SFTP as SFTP;
use BMI\Plugin\External\BMI_External_OneDrive as OneDrive;
use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * Ajax Offline (unauthorized) Handler for BMI
 */
class BMI_Ajax_Offline_Premium
{

  public $sftp;
  public $onedrive;
  public $pcloud;

  public $onedriveStatus = false;
  public $pcloudStatus = false;
  public $sftpStatus = false;

  public function __construct()
  {

    // Prevent if there is no offline action required (save resources)
    if (get_option('bmip_last', false) !== '1') return;

    $isOneDriveEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::ONEDRIVE');
    $isPCloudEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::PCLOUD');
    $isSftpEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::SFTP');

    $isEnabledOneDrive = ($isOneDriveEnabled === true || $isOneDriveEnabled === 'true') && $this->getOneDriveConnectionStatus();
    $isEnabledPCloud = ($isPCloudEnabled === true || $isPCloudEnabled === 'true') && $this->getPCloudConnectionStatus();
    $isEnabledSftp = ($isSftpEnabled === true || $isSftpEnabled === 'true') && $this->getSFTPConnectionStatus();

    if ($isEnabledOneDrive) {
      require_once BMI_PRO_INC . 'external/one-drive.php';
      $this->onedrive = new OneDrive();
    }

    // $this->sftp is already created by getSFTPConnectionStatus() — no second instance needed

    if ($isEnabledPCloud) {
      require_once BMI_PRO_INC . 'external/pcloud.php';
      $this->pcloud = new PCloud();
    }

  }

  public function getPCloudConnectionStatus()
  {

    require_once BMI_PRO_INC . 'external/pcloud.php';

    $pcloud = new PCloud();
    $status = $pcloud->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->pcloudStatus = true;
      return true;
    } else {
      return false;
    }
  }

  public function getOneDriveConnectionStatus()
  {
    require_once BMI_PRO_INC . 'external/one-drive.php';

    $onedrive = new OneDrive();

    $status = $onedrive->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->onedriveStatus = true;
      return true;
    } else {
      return false;
    }
  }


  public function getSFTPConnectionStatus() {
    require_once BMI_PRO_INC . 'external/sftp.php';

    // Reuse the single instance stored on $this->sftp
    if (!$this->sftp) {
      $this->sftp = new SFTP();
    }

    $status = $this->sftp->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->sftpStatus = true;
      return true;
    } else {
      return false;
    }
  }
  
  public function checkForBackupsToUpload() {;

    if ($this->onedrive && $this->onedriveStatus) $this->onedrive->checkForBackupsToUpload();
    if ($this->sftp && $this->sftpStatus) $this->sftp->checkForBackupsToUpload();
    if ($this->pcloud && $this->pcloudStatus) $this->pcloud->checkForBackupsToUpload();

  }

  public function checkIfBackupCanBeUploaded($type, $backupSize) {
    
    switch($type) {

      case "sftp": {
        if (!$this->sftp || !$this->sftpStatus) return false;
        $issue = get_transient('bmip_sftp_issue');
        if (in_array($issue, ['disconnected'])) {
          return false;
        }
        break;
      }

      case "onedrive": {
        if (!$this->onedrive || !$this->onedriveStatus) return false;
        if (!$this->getOneDriveConnectionStatus()) {
          return false;
        }

        if (!$this->onedrive->getSecret()) {
          return false;
        }

        if ($this->onedrive->getNotice("upload_issue_space")) {
          $storageInfo = $this->onedrive->getStorageInfo();
          

          if (isset($storageInfo["quota"]))
          {
            $remaining = $storageInfo["quota"]["remaining"];

            // if (BMI_PRO_DEBUG)
            //   $remaining = 0;

            if ($backupSize != -1 && $remaining >= $backupSize)
              return true; //Allow backup to be uploaded if the backup size is within the storage limit
        

            if ($remaining >= get_option("bmip_onedrive_required_space", 0)) {
              $this->onedrive->removeNotice("upload_issue_space");
              delete_option("bmip_onedrive_required_space");
              return true;
            }
          }
          else {
            Logger::error("[BMI PRO] Couldn't fetch quota from OneDrive!");
          }

          return false;
        }

        if ($this->onedrive->getNotice("upload_issue")) {
          return false;
        }

        break;
      }

      case "pcloud": {
        if (!$this->pcloud || !$this->pcloudStatus) return false;
        $issue = get_transient('bmip_pcloud_issue');
        if ($issue === 'auth_error_disconnected') {
          delete_option('bmi_pro_pcloud_client_id');
          delete_option('bmi_pro_pcloud_token');
          delete_transient('bmip_pcloud_issue');
          return false;
        }
        if ($issue == 'internal_error') return false;
        if ($issue == 'insufficient_space') {
          $spaceUsage = $this->pcloud->getSpaceUsage();
          if ($spaceUsage === false) return false;
  
          $availableSpace = $spaceUsage['quota'] - $spaceUsage['usedquota'];
      
          if ($availableSpace >= $backupSize) {
            delete_transient('bmip_pcloud_issue');
            delete_option('bmip_pcloud_required_space');
            return true;
          }    
  
          return false; 
        }
        break;
      }
    }

    return true;
  }

  public function getDeactivatedClouds() {
    $deactivatedClouds = [];
    if (!$this->onedriveStatus) $deactivatedClouds[] = "onedrive";
    if (!$this->sftpStatus) $deactivatedClouds[] = "sftp";
    if (!$this->pcloudStatus) $deactivatedClouds[] = "pcloud";
    return $deactivatedClouds;
  }

  public function processClouds($type, $task, $toBeUploaded, $taskname) {


    // Onedrive
    if ($type == 'onedrive') {

      if (!isset($task['uploadSession'])) {

        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['name'];
        $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['json'];
        $uploadSession = $this->onedrive->initiateUploadSession($backupPath);

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $toBeUploaded['current_upload']['bytesPerRequest'] = $bytesPerRequest;
        $toBeUploaded['current_upload']['uploadSession'] = $uploadSession;
        $toBeUploaded['current_upload']['manifestPath'] = $manifestPath;
        $toBeUploaded['current_upload']['backupPath'] = $backupPath;
        $toBeUploaded['current_upload']['batch'] = 1;

        update_option('bmip_to_be_uploaded', $toBeUploaded);

        if (!file_exists($backupPath)) delete_option('bmip_to_be_uploaded');
        return ['status' => 'success'];
      } else {

        if (!file_exists($task['backupPath'])) {
          delete_option('bmip_to_be_uploaded');
          return ['status' => 'success'];
        }

        $this->onedrive->uploadFile($task['uploadSession'], $task['backupPath'], $task['manifestPath'], $task['md5'], $task['batch'], $task['bytesPerRequest']);
        return ['status' => 'success'];
      }
    }

      // SFTP Process
      if ($type == 'sftp') {
        set_transient('bmip_upload_ongoing', '1', 31);

        $offset = isset($task['offset']) ? $task['offset'] : 0;
        $backupName = isset($task['name']) ? $task['name'] : '';
        $md5 = isset($task['md5']) ? $task['md5'] : '';

        $result = $this->sftp->uploadBackup($backupName, $offset, $md5);
        switch ($result['status']) {
          case 'success':
            $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
            if (!isset($uploadedBackupStatus[$md5])) {
              $uploadedBackupStatus[$md5] = [];
            }
            $uploadedBackupStatus[$md5]['sftp'] = true;
            update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            $toBeUploaded['current_upload'] = [];
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            do_action('bmi_backup_upload_completed', $md5);
            break;
          case 'error':
            Logger::error('[BMI PRO] Could not finish upload for ' . $backupName . ' to SFTP during ' . $result['error']);

            if ($result['error'] == 'internal_file_not_found' || $result['error'] == 'incorrect_offset') {
              delete_option('bmip_to_be_uploaded');
              $this->sftp->restartUploadProcess();
              return ['status' => 'success'];
            }
            
            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            if (isset($toBeUploaded['failed'][$taskname])) $toBeUploaded['failed'][$taskname]++;
            else $toBeUploaded['failed'][$taskname] = 1;
            break;
          case 'continue':
            $offset = isset($result['offset']) ? $result['offset'] : 0;
            if ($offset != 0 ) $toBeUploaded['current_upload']['offset'] = $offset;
            $fileSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
            $toBeUploaded['current_upload']['progress'] = round(($offset / $fileSize) * 100) . '%';

            // remove from failed
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;

          }
          delete_transient('bmip_upload_ongoing');
          update_option('bmip_to_be_uploaded', $toBeUploaded);
          return ['status' => 'success'];
      }

      // pCloud Process
      if ($type == 'pcloud') {
    
        set_transient('bmip_upload_ongoing', '1', 500);
        
        $sessionId = isset($task['sessionId']) ? $task['sessionId'] : '';
        $offset = isset($task['offset']) ? $task['offset'] : 0;
        $backupName = isset($task['name']) ? $task['name'] : '';
        $md5 = isset($task['md5']) ? $task['md5'] : '';

        $pcloud = new PCloud();
        $result = $pcloud->uploadBackup($sessionId, $backupName, $offset, $md5);
        switch ($result['status']) {
          case 'success':
            $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
            if (!isset($uploadedBackupStatus[$md5])) {
              $uploadedBackupStatus[$md5] = [];
            }
            $uploadedBackupStatus[$md5]['pcloud'] = true;
            update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            $toBeUploaded['current_upload'] = [];
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            do_action('bmi_backup_upload_completed', $md5);
            break;
          case 'error':
            Logger::error('[BMI PRO] Could not finish upload for ' . $backupName . ' to pCloud during ' . $result['error']);

            if ($result['error'] == 'internal_file_not_found') {
              delete_option('bmip_to_be_uploaded');
              return ['status' => 'success'];
            }
            
            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            if (isset($toBeUploaded['failed'][$taskname])) $toBeUploaded['failed'][$taskname]++;
            else $toBeUploaded['failed'][$taskname] = 1;
            break;
          case 'continue':
            $offset = isset($result['offset']) ? $result['offset'] : 0;
            $sessionId = isset($result['sessionId']) ? $result['sessionId'] : '';
            if ($offset != 0 ) $toBeUploaded['current_upload']['offset'] = $offset;
            if ($sessionId != '') $toBeUploaded['current_upload']['sessionId'] = $sessionId;
            $fileSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
            $toBeUploaded['current_upload']['progress'] = round(($offset / $fileSize) * 100) . '%';

            // remove from failed
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
        }

        delete_transient('bmip_upload_ongoing');
        update_option('bmip_to_be_uploaded', $toBeUploaded);
        return ['status' => 'success'];
      }

    return ["status"  => "no_tasks"];
  }
}