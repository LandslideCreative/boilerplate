<?php

// Namespace
namespace BMI\Plugin;

// Uses
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\BMI_Pro_Core;
use BMI\Plugin\BMProAjax as BMProAjax;
use BMI\Plugin\BMI_Ajax as BMI_Ajax;
use BMI\Plugin\Progress\BMI_MigrationProgress as MigrationProgress;
use BMI\Plugin\BMI_File_Explorer as File_Explorer;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Services\BackupService;
use BMI\Plugin\Services\ActionInitiator;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\BMI_Zip_Explorer as ZipExplorer;
use BMI\Plugin\External\BMI_External_OneDrive as OneDrive;
use BMI\Plugin\External\BMI_External_PCloud as PCloud;
use BMI\Plugin\External\BMI_External_SFTP as SFTP;
use BMI\Plugin\Services\BackupLifecycleManager;
// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * Ajax Handler for BMI
 */
class BMI_Ajax_Premium
{
  public $post;

  public function __construct($post)
  {

    // $POST is sanitized by BMI Basic Version
    // Do not call this class anywhere else [!]
    $this->post = $post;

    // Methods
    if ($this->post['f'] == 'download-cloud-backup') {
      BMP::res($this->downloadCloudBackup());
    } elseif ($this->post['f'] == 'explore-files') {
      BMP::res($this->exploreFiles());
    } elseif ($this->post['f'] == 'save-dynamic-paths') {
      BMP::res($this->saveDynamicPaths());
    } elseif ($this->post['f'] == 'set-new-user-secret-key') {
      BMP::res($this->setNewUserSecretKey());
    } elseif ($this->post['f'] == 'before-update') {
      BMP::res($this->beforeUpdate());
    } elseif ($this->post['f'] == 'dismiss-quota-notice') {
      BMP::res($this->dismissQuotaNotice());
    } elseif ($this->post['f'] == 'explore-zip') {
      BMP::res($this->exploreZip());
    } elseif ($this->post['f'] == 'save-restore-parts') {
      BMP::res($this->saveRestoreParts());
    } elseif ($this->post['f'] == 'get-onedrive-wid') {
      BMP::res($this->getOneDriveWid());
    } elseif ($this->post['f'] == 'save-onedrive-connection') {
      BMP::res($this->saveOneDriveToken());
    } elseif ($this->post['f'] == 'verify-onedrive-connection') {
      BMP::res($this->verifyOneDriveConnection());
    } elseif ($this->post['f'] == 'disconnect-onedrive') {
      BMP::res($this->disconnectOneDrive());
    }elseif ($this->post['f'] == 'keep-pcloud-connection') {
      BMP::res($this->keepPCloudToken());
    } elseif ($this->post['f'] == 'get-pcloud-token') {
      BMP::res($this->getPCloudToken());
    }elseif ($this->post['f'] == 'disconnect-pcloud') {
      BMP::res($this->disconnectPCloudToken());
    } elseif ($this->post['f'] == 'verify-pcloud-connection') {
      BMP::res($this->verifyPCloudConnection());
    } elseif ($this->post['f'] == 'save-sftp-config') {
      BMP::res($this->saveSftpConfig());
    } elseif ($this->post['f'] == 'disconnect-sftp') {
      BMP::res($this->disconnectSftp());
    } elseif ($this->post['f'] == 'verify-sftp-connection') {
      BMP::res($this->verifySftpConnection());
    } elseif ($this->post['f'] == 'download-sftp-backup') {
      BMP::res($this->downloadCloudBackupV2());
    } elseif ($this->post['f'] == 'is-storage-strategy-configurable') {
      BMP::res($this->isStorageStrategyConfigurable());
    }
  }

  /**
   * randomString - Generates "random" string
   *
   * @return string "random"
   */
  private function randomString($length = 64)
  {

    $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $str = "";

    for ($i = 0; $i < $length; ++$i) {

      $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $str;
  }

  /**
   * getOneDriveToken - Generates client sided auth token
   *
   * @return string Token
   */
  private function getOneDriveWid()
  {

    $token = get_option('bmi_pro_onedrive_wid', false);
    if ($token)
      return ['token' => $token];

    $bytes = random_bytes(36);
    $token = bin2hex($bytes);

    update_option('bmi_pro_onedrive_wid', $token);
    return ['token' => $token];
  }

  /**
   * saveOneDriveToken - Saves Client Secret for OneDrive API - BMI API communication
   *
   * @return json status
   */
  private function saveOneDriveToken()
  {

    $wid = $this->post['wid'];
    $secret = $this->post['secret'];

    $current_wid = get_option('bmi_pro_onedrive_wid', false);

    if ($current_wid === $wid) {
      set_transient('bmi_pro_onedrive_secret', $secret, 3600);
      return ['status' => 'success'];
    } else {

      return ['status' => 'token_mismatch'];
    }
  }


  /**
   * verifyOneDriveConnection - Checks if the OneDrive is still granted and tokens are not expired
   *
   * @return json rtoken
   */
  private function verifyOneDriveConnection()
  {

    $baseurl = home_url();
    if (substr($baseurl, 0, 4) != 'http') {
      if (is_ssl()) $baseurl = 'https://' . home_url();
      else $baseurl = 'http://' . home_url();
    }

    $wid = get_option('bmi_pro_onedrive_wid', false);

    if ($wid === false) {
      return ['status' => 'success', 'result' => 'disconnected'];
    }
    require_once BMI_PRO_INC . 'external/one-drive.php';
    $onedrive = new OneDrive();
    $secret = $onedrive->getSecret();
    return ['status' => 'success', 'result' => $secret ? "connected" : "disconnected"];
  }

  /**
   * removeOneDriveConnection - Removed OneDrive connection from BMI API
   *
   */
  private function removeOneDriveConnection()
  {

    $baseurl = home_url();
    if (substr($baseurl, 0, 4) != 'http') {
      if (is_ssl()) $baseurl = 'https://' . home_url();
      else $baseurl = 'http://' . home_url();
    }

    $wid = get_option('bmi_pro_onedrive_wid', false);

    if ($wid === false) {
      return ['status' => 'success'];
    }

    $url = 'https://authentication.backupbliss.com/oauth/onedrive/disconnect';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'body' => array(
        'wid' => $wid,
        'site' => $baseurl
      )
    ));

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during OneDrive disconnection:' . $error_message);
      return ['status' => 'error'];
    } else {
      $result = json_decode($response['body']);

      if (isset($result->wid) && $wid === $result->wid) {
        return ['status' => 'success'];
      }
    }
    return ['status' => 'error'];
  }

  /**
   * disconnectOneDrive - Removes connection with OneDrive API
   *
   * @return json status
   */
  private function disconnectOneDrive()
  {

    $ret = $this->removeOneDriveConnection();
    if ($ret['status'] == 'success') {
      Dashboard\bmi_set_config('STORAGE::EXTERNAL::ONEDRIVE', 0);
      delete_option('bmi_pro_onedrive_wid');
      delete_transient('bmi_pro_onedrive_secret');
    }

    return $ret;
  }

  /**
   * downloadCloudBackup - Downloads Cloud Backup to Local Storage
   *
   * @return json status
   */
  private function downloadCloudBackup()
  {

    $secret = false;
    if (isset($this->post['secret'])) $secret = $this->post['secret'];

    $lock = BMI_BACKUPS . '/.migration_lock';
    if (file_exists($lock) && (time() - filemtime($lock)) < 1) {
      $lockContent = file_get_contents($lock);
      if ($lockContent !== $secret) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }
    }

    require_once BMI_INCLUDES . '/progress/migration.php';

    $step = intval($this->post['step']);
    $storage = $this->post['storage'];
    $startRestoreProcess = isset($this->post['startRestoreProcess']) ? $this->post['startRestoreProcess'] : 'true';

    $clearFile = ($step === 0) ? false : true;
    $migration = new MigrationProgress($clearFile);
    $migration->start();

    if ($storage == 'onedrive') {

      require_once BMI_PRO_INC . 'external/one-drive.php';
      $onedrive = new OneDrive();

      $backupDetails = false;
      $fileId = $this->post['fileId'];

      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {

        $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
        $migration->log(__('Creating lock file', 'backup-backup'));
        $secret = $this->randomString();
        file_put_contents($lock, $secret);

        $migration->log('Download intialized', 'INFO');
        $migration->log('Getting backup details from OneDrive...', 'STEP');
        $backupDetails = $onedrive->getFileDetailByName($fileId);

        if (!$backupDetails) {

          $migration->log("Couldn't fetch backup details from cloud.", 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'VERBOSE');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          return ['status' => 'error'];
        }

        $size = intval($backupDetails['size']);
        $originalFilename = $backupDetails['name'];

        $migration->log('Backup details received!', 'SUCCESS');
        $migration->log('Backup original name: ' . $originalFilename, 'INFO');
        $migration->log('Starting download process...', 'STEP');

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

        $fileIterator = 2;
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        if ($extension == 'gz') {
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          $extension = 'tar.gz';
        }

        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
        $finalName = $originalFilename . '.' . $extension;

        while (file_exists($backupDestinationPath)) {
          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
          $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
          $fileIterator++;
        }

        $originalFilename = $finalName;

        $backupDestinationPath .= '.crdownload';

      } else {

        $size = intval($this->post['size']);
        $originalFilename = $this->post['filename'];
        $backupDestinationPath = $this->post['writepath'];
        $bytesPerRequest = intval($this->post['chunksize']);
      }

      $md5 = $this->post['md5'];

      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

      if ($totalBatches <= $step) {

        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

        rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
        $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);
  

        $isDownloadedFileVerified = BMP::verifyFileMd5($backupDestinationPath, $md5);
        if ($isDownloadedFileVerified) {

          $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        } else {

          $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
          $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
          $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'VERBOSE');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);
        if ($startRestoreProcess == 'true') {
          $migration->log('Requesting restoration process...', 'STEP');

          $migration->log('#205', 'END-CODE');
        } else {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('#206', 'END-CODE');
        }
        $migration->progress(100);
        $migration->end();

        return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
      } else {

        $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
        $startRange = ($step * $chunkSize);
        if ($step !== 0) $startRange = $startRange + 1;
        $endRange = (($step + 1) * $chunkSize);
        if ($endRange > $size) $endRange = $size;
        $percentage = intval(($endRange / $size) * 100);

        $data = $onedrive->getFile($fileId, $startRange, $endRange);

        if (!$data["file_detail"] || !$data["file_data"]) {

          $migration->log("Couldn't fetch backup file from cloud.", 'ERROR');
          $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'VERBOSE');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

          $backupFile = fopen($backupDestinationPath, 'ab');
          fwrite($backupFile, $data['file_data']);
          fclose($backupFile);
        } else {

          $migration->log('File is not writable or directory does not exist.', 'ERROR');
          $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
          $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'VERBOSE');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log('Download progress (' . ($step + 1) .  '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
        $migration->progress($percentage);
        $migration->end();

        return [
          'status' => 'success',
          'size' => $size,
          'md5' => $md5,
          'finished' => 'false',
          'originalFilename' => $originalFilename,
          'writepath' => $backupDestinationPath,
          'chunksize' => $bytesPerRequest,
          'secret' => $secret
        ];
      }
    }

    // TODO: move it to downloadCloudBackupV2
    if ($storage == 'pcloud') {

      require_once BMI_PRO_INC . 'external/pcloud.php';
      $pcloud = new PCloud();

      $md5 = isset($this->post['md5']) ? $this->post['md5'] : false;
      $backupDetails = false;
      $fileId = $this->post['fileId'];

      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {
        $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
        $migration->log(__('Creating lock file', 'backup-backup'));
        $secret = $this->randomString();
        file_put_contents($lock, $secret);

        $migration->log('Download intialized', 'INFO');
        $migration->log('Getting backup details from pCloud...', 'STEP');
        $backupDetails = $pcloud->getFileMeta($fileId);

        if ($backupDetails == false) {
          $migration->log('It seem like I was unable to get backup details from cloud.', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          return ['status' => 'error'];
        }

        $size = intval($backupDetails['size']); 
        $originalFilename = $backupDetails['name'];

        $migration->log('Backup details received!', 'SUCCESS');
        $migration->log('Backup original name: ' . $originalFilename, 'INFO');
        $migration->log('Starting download process...', 'STEP');

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

        $fileIterator = 2;
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        if ($extension == 'gz') {
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          $extension = 'tar.gz';
        }
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
        $finalName = $originalFilename . '.' . $extension;


        while (file_exists($backupDestinationPath)) {
          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
          $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
          $fileIterator++;
        }

        $originalFilename = $finalName;
        $backupDestinationPath .= '.crdownload';

      } else {
        $size = intval($this->post['size']);
        $md5 = $this->post['md5'];
        $originalFilename = $this->post['filename'];
        $backupDestinationPath = $this->post['writepath'];
        $bytesPerRequest = intval($this->post['chunksize']);
      }

      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

      if ($totalBatches <= $step) {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

        rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
        $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);

        $isDownloadedFileVerified = BMP::verifyFileMd5($backupDestinationPath, $md5);
        if ($isDownloadedFileVerified) {
          $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        } else {
          $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
          $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
          $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);
        if ($startRestoreProcess == 'true') {
          $migration->log('Requesting restoration process...', 'STEP');
          $migration->log('#205', 'END-CODE');
        } else {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('#206', 'END-CODE');
        }

        $migration->progress(100);
        $migration->end();

        return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];

      } else {
        $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
        $startRange = ($step * $chunkSize);
        if ($step !== 0) $startRange = $startRange + 1;
        $endRange = (($step + 1) * $chunkSize);
        if ($endRange > $size) $endRange = $size;
        $currentRange = $startRange . '-' . $endRange;
        $percentage = intval(($endRange / $size) * 100);

        $contents = $pcloud->getFileContent($fileId, $currentRange);

        if ($contents == false) {
          $migration->log('It seem like I was unable to get backup content from cloud.', 'ERROR');
          $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {
          $backupFile = fopen($backupDestinationPath, 'ab');
          fwrite($backupFile, $contents);
          fclose($backupFile);
        } else {
          $migration->log('File is not writable or directory does not exist.', 'ERROR');
          $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
          $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log('Download progress (' . ($step + 1) . '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
        $migration->progress($percentage);
        $migration->end();

        return [
          'status' => 'success',
          'size' => $size,
          'md5' => $md5,
          'finished' => 'false',
          'originalFilename' => $originalFilename,
          'writepath' => $backupDestinationPath,
          'chunksize' => $bytesPerRequest,
          'secret' => $secret
        ];
      }
    }

    if (file_exists($lock)) @unlink($lock);
    return ['status' => 'error'];
  }

  private function downloadCloudBackupV2()
  {

    require_once BMI_INCLUDES . '/progress/migration.php';

    $secret = isset($this->post['secret']) ? $this->post['secret'] : false;
    $startRestoreProcess = isset($this->post['startRestoreProcess']) ? $this->post['startRestoreProcess'] : 'true';
    $lock = BMI_BACKUPS . '/.migration_lock';

    if (file_exists($lock) && (time() - filemtime($lock)) < 1) {
      $lockContent = file_get_contents($lock);
      if ($lockContent !== $secret) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }
    }

    $externalStorage = null;
    switch($this->post['f']){
      case 'download-sftp-backup':
        require_once BMI_PRO_INC . 'external/sftp.php';
        $externalStorage = new SFTP();
        break;
      default:
        return ['status' => 'error'];
    }
    $fileId = isset($this->post['fileId']) ? $this->post['fileId'] : false; // Required
    $md5 = isset($this->post['md5']) ? $this->post['md5'] : false; // Required
    $step = isset($this->post['step']) ? intval($this->post['step']) : 0; // Required
    $size = isset($this->post['size']) ? intval($this->post['size']) : false;
    $fileName = isset($this->post['filename']) ? $this->post['filename'] : false;
    $writePath = isset($this->post['writepath']) ? $this->post['writepath'] : false;
    $chunkSize = isset($this->post['chunksize'])  && (intval($this->post['chunksize']) != 0) ? intval($this->post['chunksize']) : BMP::getAvailableMemoryInBytes() / 4;
    $migration = new MigrationProgress(($step === 0) ? false : true);


    $migration->start();

    if ($step === 0) {
      $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
      $migration->log(__('Creating lock file', 'backup-backup'));
      $secret = $this->randomString();
      file_put_contents($lock, $secret);

      $migration->log('Download intialized', 'INFO');
      $migration->log('Getting backup details from cloud...', 'STEP');

      $backupDetails = $externalStorage->getFileMeta($fileId);
      if (!isset($backupDetails['name'])) $backupDetails['name'] = $fileId;


      if ($backupDetails == false) {
        $migration->log('It seem like I was unable to get backup details from cloud.', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'VERBOSE');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        return ['status' => 'error'];
      }

      $manifest = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';
      $manifestContent = $externalStorage->getFileContent($md5 . '.json');
      if ($manifestContent == false) {
        $migration->log('It seem like I was unable to get backup manifest from cloud.', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'VERBOSE');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        return ['status' => 'error'];
      }
      file_put_contents($manifest, $manifestContent);


      $size = intval($backupDetails['size']);
      $fileName = $backupDetails['name'];

      $migration->log('Backup details received!', 'SUCCESS');
      $migration->log('Backup original name: ' . $fileName, 'INFO');
      $migration->log('Starting download process...', 'STEP');

      $availableMemory = BMP::getAvailableMemoryInBytes();
      $bytesPerRequest = intval($availableMemory / 4);


      $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

      $fileIterator = 2;
      $extension = pathinfo($fileName, PATHINFO_EXTENSION);
      $fileName = pathinfo($fileName, PATHINFO_FILENAME);
      if ($extension == 'gz') {
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = 'tar.gz';
      }

      $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $fileName . '.' . $extension;
      $finalName = $fileName . '.' . $extension;

      while (file_exists($backupDestinationPath)) {
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $fileName . '-' . $fileIterator . '.' . $extension;
        $fileIterator++;
      }

      $originalFilename = $finalName;

      $backupDestinationPath .= '.crdownload';
    } else {
      $bytesPerRequest = intval($chunkSize);
      $backupDestinationPath = $writePath;
      $originalFilename = $fileName;
    }

    $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

    if ($totalBatches <= $step) {
      $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

      rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
      $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);

      $isDownloadedFileVerified = BMP::verifyFileMd5($backupDestinationPath, $md5);
      if ($isDownloadedFileVerified) {


        $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        $migration->log('File MD5 checksum is correct!', 'SUCCESS');
      } else {

        $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
        $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
        $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
        $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'VERBOSE');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
      if (file_exists($lock)) @unlink($lock);
      if ($startRestoreProcess == 'true') {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Requesting restoration process...', 'STEP');

        $migration->log('#205', 'END-CODE');
      } else {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('#206', 'END-CODE');
      }

      $migration->progress(100);
      $migration->end();

      return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
    } else {

      $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
      $startRange = ($step * $chunkSize);
      if ($step !== 0) $startRange = $startRange + 1;
      $endRange = (($step + 1) * $chunkSize);
      if ($endRange > $size) $endRange = $size;
      $currentRange = $startRange . '-' . $endRange;
      $percentage = intval(($endRange / $size) * 100);

      $contents = $externalStorage->getFileContent($fileId, $currentRange);

      if ($contents == false) {

        $migration->log('It seem like I was unable to get backup content from cloud.', 'ERROR');
        $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'VERBOSE');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

        $backupFile = fopen($backupDestinationPath, 'ab');
        fwrite($backupFile, $contents);
        unset($contents);
        fclose($backupFile);
      } else {

        $migration->log('File is not writable or directory does not exist.', 'ERROR');
        $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
        $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
        $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      $migration->log('Download progress (' . ($step + 1) . '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
      $migration->progress($percentage);
      $migration->end();

      return [
        'status' => 'success',
        'size' => $size,
        'md5' => $md5,
        'finished' => 'false',
        'originalFilename' => $originalFilename,
        'writepath' => $backupDestinationPath,
        'chunksize' => $bytesPerRequest,
        'secret' => $secret
      ];
    }
  }
  private function getPCloudToken() {

    $bytes = random_bytes(36);
    $token = bin2hex($bytes);

    update_option('bmip_pcloud', $token);
    return [ 'token' => $token ];

  }

  private function keepPCloudToken() {

    $receivedToken = $this->post['receivedToken'];
    $receivedAuthCode = $this->post['receivedClientID'];
    $hostname = $this->post['receivedHostname'];
    $accessToken = $this->post['receivedAccessToken'];

    $currentToken = get_option('bmip_pcloud', false);

    if ($currentToken === $receivedToken) {

      if ($hostname !== 'api.pcloud.com' && $hostname !== 'eapi.pcloud.com') {
        $hostname = 'api.pcloud.com';
      }

      update_option('bmip_pcloud_auth_code', $receivedAuthCode);
      update_option('bmip_pcloud_access_token', $accessToken);
      update_option('bmip_pcloud_hostname', $hostname);
      if (get_transient('bmip_pcloud_issue') == 'auth_error_disconnected') delete_transient('bmip_pcloud_issue');

      return [ 'status' => 'success' ];

    } else {

      return [ 'status' => 'token_mismatch' ];

    }

  }

  private function verifyPCloudConnection() {

    require_once BMI_PRO_INC . 'external/pcloud.php';

    $pcloud = new PCloud();
    return $pcloud->verifyConnection();
  }

  private function disconnectPCloudToken() {
    require_once BMI_PRO_INC . 'external/pcloud.php';

    $pcloud = new PCloud();
    $pcloud->disconnect();

    return [ 'status' => 'success' ];
  }


  private function exploreFiles()
  {
    // Load required files
    require_once BMI_INCLUDES . '/file-explorer.php';
    require_once BMI_INCLUDES . '/ajax.php';
    require_once BMI_INCLUDES . '/scanner/files.php';

    // Allowed browsing types
    $allowedBrowsingType = [
      'plugins' => WP_PLUGIN_DIR,
      'themes' => get_theme_root(),
      'uploads' => wp_upload_dir()['basedir'],
      'wp-install' => untrailingslashit(ABSPATH),
      'other-contents' => WP_CONTENT_DIR,
    ];

    // Initialize required variables
    $browsingType = isset($this->post['browsingType']) ? $this->post['browsingType'] : false;
    $enabledRootPath = isset($allowedBrowsingType[$browsingType]) ? BMP::fixSlashes($allowedBrowsingType[$browsingType]) : false;
    $path = isset($this->post['path']) ? BMP::fixSlashes($this->post['path']) : $enabledRootPath;
    $firstLevelOnly = in_array($browsingType, ['plugins', 'themes']) ? true : false;

    // Validate request
    if (!$browsingType || !$enabledRootPath) {
      return ['status' => 'error', 'msg' => __('Something went wrong', 'backup-backup')];
    }
    if (File_Explorer::isSub($path, $enabledRootPath) === -1) {
      return ['status' => 'error', 'msg' => __('Invalid path. Please ensure the path is a subdirectory of the selected browsing type.', 'backup-backup')];
    }
    if ($firstLevelOnly && BMP::fixSlashes($path) != BMP::fixSlashes($enabledRootPath)) {
      return ['status' => 'error', 'msg' => __('This browsing type only allows to explore first level. Please do not navigate into subdirectories.', 'backup-backup')];
    }

    try {
      $ignoredDPaths = BMP::getDefaultDisabledPaths();
      $ignoredPaths = [];

      if ($browsingType == 'wp-install') {
        $ignoredPaths[] = WP_CONTENT_DIR;
      }

      if ($browsingType == 'other-contents') {
        $ignoredPaths = array_merge($ignoredPaths, [WP_PLUGIN_DIR, get_theme_root(), wp_upload_dir()['basedir']]);
      }

      $disabledRelatedPaths = $this->getRelatedPaths($ignoredDPaths, $path);
      $disabledDirsByUser = $this->getRelatedPaths(Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS:IN'), $path);
      $disabledFilesByUser = $this->getRelatedPaths(Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS:IN'), $path);
      array_walk($disabledDirsByUser, function (&$dir) {
        $dir = BMP::fixSlashes(str_replace('***ABSPATH***', ABSPATH, $dir));
      });
      array_walk($disabledFilesByUser, function (&$file) {
        $file = BMP::fixSlashes(str_replace('***ABSPATH***', ABSPATH, $file));
      });

      $ignoredPaths = array_map(function ($path) {
        return BMP::fixSlashes(str_replace('***ABSPATH***', ABSPATH, $path));
      }, array_merge($ignoredPaths, $disabledRelatedPaths, $disabledDirsByUser, $disabledFilesByUser));

      $files = BMI_File_Explorer::scanDir($path, $ignoredPaths);
      $largestFiles = $files->getLargest(100);

      $fValue = isset($_POST['f']) ? $_POST['f'] : '';
      unset($_POST['f']);
      $handler = new BMI_Ajax();
      $emptyVar = ['this_is_empty_array'];
      $enabledFiles = [];
      foreach ($largestFiles as $file) {
        if ($file[1] == 1) $enabledFiles[] = $file[0] . ', ' . $file[2];
      }
      $handler->parseFilesForBackup($enabledFiles, $emptyVar, false, true);
      $_POST['f'] = $fValue;


      $largestFiles = $this->updateLargestFiles($largestFiles, $enabledFiles);
      $largestFiles = array_merge($largestFiles, $this->addDisabledRelatedPaths($disabledRelatedPaths, $disabledDirsByUser, $disabledFilesByUser));

      return [
        'status' => 'success',
        'files' => $largestFiles,
        'enabledRootPath' => $enabledRootPath,
      ];
    } catch (\Exception $e) {
      return ['status' => 'exception', 'msg' => $e->getMessage()];
    }
  }

  private function getRelatedPaths($dirs, $path)
  {
    return array_filter($dirs, function ($dir) use ($path) {
      return File_Explorer::isSub(BMP::fixSlashes(str_replace('***ABSPATH***', ABSPATH, $dir)), $path) === 1;
    });
  }

  private function updateLargestFiles($largestFiles, $enabledFiles)
  {
    return array_map(function ($file) use ($enabledFiles) {
      if ($file[1] == 1 && !in_array($file[0], $enabledFiles)) {
        $file[] = 2; // Disabled
      }
      return $file;
    }, $largestFiles);
  }

  private function addDisabledRelatedPaths($disabledRelatedPaths, $disabledDirsByUser, $disabledFilesByUser)
  {
    $disabledPaths = [];
    foreach ($disabledRelatedPaths as $dir) {
      if (!realpath($dir)) continue;
      if (is_dir($dir)) $disabledPaths[] = [$dir, 0, 0, 2];
      else $disabledPaths[] = [$dir, 1, 0, 2];
    }
    foreach ($disabledDirsByUser as $dir) {
      if (!realpath($dir)) continue;
      $disabledPaths[] = [$dir, 0, 0, 1];
    }
    foreach ($disabledFilesByUser as $file) {
      if (!realpath($dir)) continue;
      $disabledPaths[] = [$file, 1, 0, 1];
    }
    return $disabledPaths;
  }

  private function saveDynamicPaths()
  {

    $fPaths = isset($this->post['fPaths']) ? $this->post['fPaths'] : [];
    $dPaths = isset($this->post['dPaths']) ? $this->post['dPaths'] : [];

    if (!is_array($fPaths)) $fPaths = explode('\n', $fPaths);
    if (!is_array($dPaths)) $dPaths = explode('\n', $dPaths);


    Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS', 'true');
    Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS', 'true');
    $filterIsEnabled = Dashboard\bmi_get_config('BACKUP:FILES::FILTER') === 'true' ? true : false;
    if (!$filterIsEnabled) {
      Dashboard\bmi_set_config('BACKUP:FILES::FILTER', 'true');
      Dashboard\bmi_set_config('BACKUP:FILES::FILTER:SIZE', 'false');
      Dashboard\bmi_set_config('BACKUP:FILES::FILTER:NAME', 'false');
    }
    Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS:IN', $fPaths);
    Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS:IN', $dPaths);

    return ['status' => 'success'];
  }

  private function setNewUserSecretKey()
  {
    $secret = $_POST['ukValue'];
    if (strlen($secret) < 8) return ['status' => 'error', 'msg' => __('Please provide a secret key with at least 8 characters.', 'backup-backup')];
    if (preg_match('/[^A-Za-z0-9]/', $secret)) return ['status' => 'error', 'msg' => __('Secret key can only contain letters and numbers.', 'backup-backup')]; // TODO - Add special characters support

    Dashboard\bmi_set_config('BACKUP:URL:TRIGGER:KEY', $secret);
    return ['status' => 'success'];
  }

  private function beforeUpdate()
  {
    $type = isset($this->post['type']) ? $this->post['type'] : 'core';
    $plugin = isset($this->post['plugin']) ? $this->post['plugin'] : false;
    $theme = isset($this->post['slug']) ? $this->post['slug'] : false;
    $plugins = isset($this->post['plugins']) ? $this->post['plugins'] : false;
    $themes = isset($this->post['themes']) ? $this->post['themes'] : false;
    $core = !($plugin || $theme || $plugins || $themes) ? ABSPATH : false;

    require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-initiator.php';

    $parameters = [
      'type' => $type,
      'item' => $$type
    ];

    $action = new ActionInitiator('BEFORE_UPDATE_BACKUP', $parameters, Dashboard\bmi_get_config('REQUEST:SECRET'));


    $result = $action->execute(ActionInitiator::INITIATOR_DIRECT, []);

    return $result;
  }

  public function dismissQuotaNotice()
  {
    update_option('bmip_dismissed_quota_notice', true);
  }

  /**
   * Responsible for exploring zip files
   *  required post data:
   *    - backupName
   *    - path (OPTIONAL)
   * 
   * @return array $response
   * @return string $response['status'] - success or error or step
   * @return string $response['msg'] - error message (Only if status is error)
   * @return array $response['files'] - files (Only if type is files)
   * @return array $response['dirs'] - directories (Only if type is files)
   * 
   */
  public function exploreZip()
  {
    // Load required files
    require_once BMI_PRO_INC . 'zip-explorer.php';
    require_once BMI_INCLUDES . '/ajax.php';
    require_once BMI_INCLUDES . '/scanner/files.php';
    require_once BMI_INCLUDES . '/scanner/backups.php';

    $backups = new Backups();
    $availableBackups = $backups->getAvailableBackups("local");

    // Initialize required variables
    $backupName = isset($this->post['backupName']) ? $this->post['backupName'] : false; // Backup name
    $path = isset($this->post['path']) ? BMP::fixSlashes($this->post['path']) : null; // Path to explore


    // Validate request
    if (!$backupName) {
      return ['status' => 'error', 'msg' => __('Please provide a backup name.', 'backup-backup')];
    }

    if (!in_array($backupName, array_keys($availableBackups['local']))) {
      return ['status' => 'error', 'msg' => __('Please provide a valid backup name.', 'backup-backup')];
    }

    try {

      $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
      $zipExplorer = new ZipExplorer($backupPath);
      $exploreResult = $zipExplorer->explore($path);
    } catch (\Exception $e) {
      return ['status' => 'error', 'msg' => $e->getMessage()];
    }

    return [
      'status' => 'success',
      'files' => $exploreResult['files'],
      'dirs' => $exploreResult['directories'],
    ];
  }

  /**
   * Save the selected files, directories and tables for restore process
   * required post data:
   * - backupName
   * - files (OPTIONAL)
   * - dirs (OPTIONAL)
   * 
   * but at least one of the files, dirs or tables should be selected
   * save files, dirs and tables to BMI_TMP/restore_parts.json
   * 
   * @return array $response = [ 'status' => 'success' ]
   */
  private function saveRestoreParts()
  {
    $backupName = isset($this->post['backupName']) ? $this->post['backupName'] : false;
    $files = isset($this->post['files']) ? $this->post['files'] : [];
    $dirs = isset($this->post['dirs']) ? $this->post['dirs'] : [];
    $size = isset($this->post['size']) ? $this->post['size'] : 0;

    if ($files === [] && $dirs == []) return ['status' => 'error', 'msg' => __('Please select at least one file or directory.', 'backup-backup')];

    file_put_contents(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_parts.json', json_encode([
      'backupName' => $backupName,
      'files' => $files,
      'dirs' => $dirs,
      'size' => $size,
    ]));

    return ['status' => 'success'];
  }

  private function saveSftpConfig() {
    $backupDir = isset($this->post['bmip-sftp-backup-dir']) ? trim($this->post['bmip-sftp-backup-dir']) : 'BACKUP_MIGRATION_BACKUPS';
    $backupDir = BMP::fixSlashes($backupDir);
    
    $host = isset($this->post['bmip-sftp-host']) ? trim($this->post['bmip-sftp-host']) : '';
    $port = isset($this->post['bmip-sftp-host-port']) ? intval($this->post['bmip-sftp-host-port']) : 22;
    $username = isset($this->post['bmip-sftp-username']) ? trim($this->post['bmip-sftp-username']) : '';
    $authType = isset($this->post['bmip-sftp-authType']) ? trim($this->post['bmip-sftp-authType']) : 'password';
    
    if (empty($host) || empty($username)) {
        return ['status' => 'error', 'msg' => 'Host and username are required.'];
    }
    
    if ($port <= 0 || $port > 65535) {
        return ['status' => 'error', 'msg' => 'Invalid port number.'];
    }
    
    if ($authType == 'password') {
        $password = isset($this->post['bmip-sftp-password']) ? trim($this->post['bmip-sftp-password']) : '';
        if (empty($password)) {
            return ['status' => 'error', 'msg' => 'Password is required for password authentication.'];
        }
    } elseif ($authType == 'key') {
        $password = isset($this->post['bmip-sftp-key']) ? base64_decode(trim($this->post['bmip-sftp-key'])) : '';
        if (empty($password)) {
            return ['status' => 'error', 'msg' => 'SSH key is required for key-based authentication.'];
        }
    } else {
        return ['status' => 'error', 'msg' => 'Invalid authentication type.'];
    }
    
    $passphrase = isset($this->post['bmip-sftp-passphrase']) ? trim($this->post['bmip-sftp-passphrase']) : '';
    $fingerprint = isset($this->post['bmip-sftp-fingerprint']) ? trim($this->post['bmip-sftp-fingerprint']) : '';
    $fingerprints = str_replace(['sha256:', 'SHA256:', 'md5:', 'MD5:'], '', $fingerprint);
    
    require_once BMI_PRO_INC . 'external/sftp.php';
    $sftp = new SFTP();
    $testConnection = $sftp->testConnection($host, $port, $authType, $username, $password, $fingerprint, $passphrase, $backupDir);
    
    if ($testConnection['result'] != 'connected') {
        return ['status' => 'error', 'msg' => $testConnection['result']];
    }
    
    $options = [
        'bmip_sftp_host' => $host,
        'bmip_sftp_port' => $port,
        'bmip_sftp_username' => $username,
        'bmip_sftp_authType' => $authType,
        'bmip_sftp_password' => $password,
        'bmip_sftp_passphrase' => $passphrase,
        'bmip_sftp_fingerprint' => $fingerprint,
        'bmip_sftp_remote_path' => $backupDir
    ];

    Dashboard\bmi_set_config('STORAGE::EXTERNAL::SFTP', 'true');

    foreach ($options as $key => $value) {
        update_option($key, $value);
    }

    if (get_transient('bmip_sftp_issue') == 'disconnected') delete_transient('bmip_sftp_issue');

    return ['status' => 'success'];
  }  

  public function disconnectSftp() {
      require_once BMI_PRO_INC . 'external/sftp.php';
      $sftp = new SFTP();    
      return $sftp->disconnect();
  }

  public function verifySftpConnection() {
      require_once BMI_PRO_INC . 'external/sftp.php';
      $sftp = new SFTP();    
      return $sftp->verifyConnection();
  }
  
  public function isStorageStrategyConfigurable() {
      require_once BMI_PRO_INC . 'services/class-bmi-pro-backup-lifecycle-manager.php';
      $strategy = isset($this->post['strategy']) ? sanitize_text_field($this->post['strategy']) : false;
      $backupLifecycleManager = BackupLifecycleManager::getInstance();
      $result = $backupLifecycleManager->isStorageStrategyConfigurable($strategy);
      if ($result['status'] !== true) {
          // refine the response structure to be consistent across the application
          return ['status' => 'msg', 'why' => $result['msg'], 'level' => 'warning'];
      }

      return ['status' => 'success'];
  }


}
