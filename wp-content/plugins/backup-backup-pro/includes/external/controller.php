<?php

  // Namespace
  namespace BMI\Plugin\External;

  // Use
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\External\BMI_External_OneDrive as OneDrive;
  use BMI\Plugin\External\BMI_External_PCloud as PCloud;
  use BMI\Plugin\Dashboard as Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * BMI_External_Storage
   */
  class BMI_External_Storage_Premium {

    public $onedrive;
    public $pcloud;
    public $sftp;

    public function __construct() {

        require_once BMI_PRO_INC . 'external/one-drive.php';
        $this->onedrive = new OneDrive();


        require_once BMI_PRO_INC . 'external/pcloud.php';
        $this->pcloud = new PCloud();


        require_once BMI_PRO_INC . 'external/sftp.php';
        $this->sftp = new BMI_External_SFTP();

    }

    public function getExternalBackups() {

      $backups = [];

      //OneDrive
      $backups['onedrive'] = $this->getOneDriveBackupsParsedForList();
      
      // pCloud
      $backups['pcloud'] = $this->getPCloudBackupsParsedForList();

      //SFTP
      $backups['sftp'] = $this->getSftpBackupsParsedForList();

      return $backups;

    }

    /**
     * Get pCloud backups list
     *
     * @return array[] An array of backups, where each backup is an array of details keyed by MD5 hash
     *                 Each backup array contains the following elements:
     *                 [
     *                     0 => string   Backup name
     *                     1 => string   Backup date
     *                     2 => int      Number of files in backup
     *                     3 => string   Date of the backup
     *                     4 => int      Backup size in bytes
     *                     5 => string   Backup lock status ("unlocked" or "locked")
     *                     6 => bool     Whether the backup was created by cron
     *                     7 => string   Backup MD5 hash
     *                     8 => string   Backup file ID in pCloud
     *                     9 => string   Backup domain used for tooltip in backups list
     *                 ]
     */
    public function getPCloudBackupsParsedForList()
    {
      $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::PCLOUD');
      if (!($isEnabled === true || $isEnabled === 'true') || $this->pcloud->verifyConnection()['result'] != 'connected') {
        return [];
      }

      $parsedBackups = [];
      $parsedPCloudFiles = $this->pcloud->getParsedFiles();
      $backupsFileName = isset($parsedPCloudFiles['zipFilesName']) ? $parsedPCloudFiles['zipFilesName'] : [];
      $manifestFilesPath = isset($parsedPCloudFiles['jsonFilesPath']) ? $parsedPCloudFiles['jsonFilesPath'] : [];

      foreach ($manifestFilesPath as $manifestPath => $manifestId) {
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath))) {
          $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath));
          $manifest = json_decode($manifest, true);
        } else {
          $manifest = $this->pcloud->getFileContent($manifestId);
          if (!$manifest  || $manifest == 'error') continue;
          file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath), $manifest);
          $manifest = json_decode($manifest, true);
        }
        $md5 = pathinfo($manifestPath, PATHINFO_FILENAME);
        $backupName = $manifest['name'];
        if (!in_array($backupName, array_keys($backupsFileName))) continue; // Skip if the backup is not found
        $parsedBackups[$md5] = [];
        $parsedBackups[$md5][] = $manifest['name'];
        $parsedBackups[$md5][] = $manifest['date'];
        $parsedBackups[$md5][] = $manifest['files'];
        $parsedBackups[$md5][] = $manifest['manifest'];
        $parsedBackups[$md5][] = $backupsFileName[$backupName]['size'];
        $parsedBackups[$md5][] = $manifest['is_locked'];
        $parsedBackups[$md5][] = $manifest['cron'];
        $parsedBackups[$md5][] = $md5;
        $parsedBackups[$md5][] = $backupsFileName[$backupName]['id'];
        $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
      }
      return $parsedBackups;
    }

    public function getSftpBackupsParsedForList()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::SFTP');
        if (!($isEnabled === true || $isEnabled === 'true') || $this->sftp->verifyConnection()['result'] != 'connected') {
            return [];
        }

      
        $backups = $this->sftp->getParsedFiles();

        $backupsFiles = isset($backups['zipFilesName']) ? $backups['zipFilesName'] : [];
        $manifestFiles = isset($backups['jsonFilesPath']) ? $backups['jsonFilesPath'] : [];
        $parsedBackups = [];

        foreach ($manifestFiles as $manifestFile) {
            if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestFile))) {
                $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile);
                $manifest = json_decode($manifest, true);
            } else {
                $manifest = $this->sftp->getManifestContent(pathinfo($manifestFile, PATHINFO_FILENAME));
                file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile, json_encode($manifest));
            }
            $md5 = pathinfo($manifestFile, PATHINFO_FILENAME);

            $backupName = $manifest['name'];
            if (!in_array($backupName, array_keys($backupsFiles))) continue; // Skip if the backup is not found
            $parsedBackups[$md5] = [];
            $parsedBackups[$md5][] = $manifest['name'];
            $parsedBackups[$md5][] = $manifest['date'];
            $parsedBackups[$md5][] = $manifest['files'];
            $parsedBackups[$md5][] = $manifest['manifest'];
            $parsedBackups[$md5][] = $backupsFiles[$backupName]['size'];
            $parsedBackups[$md5][] = $manifest['is_locked'];
            $parsedBackups[$md5][] = $manifest['cron'];
            $parsedBackups[$md5][] = $md5;
            $parsedBackups[$md5][] = $backupName;
            $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
        }

        return $parsedBackups;
    }


    private function getOneDriveBackupsParsedForList() {

      $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::ONEDRIVE');
      if (!($isEnabled === true || $isEnabled === 'true')) {
        return [];
      }

      $files = $this->onedrive->parseFiles($this->onedrive->getAllFiles());

      $parsedBackups = [];

      
      if ($files) {
        foreach ($files['manifests'] as $manifestFileName => $filedetail) {
          
          $localManifest = BMI_BACKUPS . DIRECTORY_SEPARATOR. $manifestFileName;

          if (file_exists($localManifest)) {

            $manifestData = file_get_contents($localManifest);
            $manifest = json_decode($manifestData);

          } else {

            $manifestData = $this->onedrive->getFile($manifestFileName);
            if (is_array($manifestData) && $manifestData["file_data"]) {
              
              file_put_contents($localManifest, $manifestData["file_data"]);
              $manifest = json_decode($manifestData["file_data"]);

            } else continue;

          }

          if (!isset($manifest))
            continue;

          $md5 = pathinfo($manifestFileName, PATHINFO_FILENAME);
          $backupFileName = $manifest->name;

          if (!isset($files["backups"][$backupFileName]))
            continue;

          $parsedBackups[$md5] = [];
          $parsedBackups[$md5][] = $backupFileName;
          $parsedBackups[$md5][] = $manifest->date;
          $parsedBackups[$md5][] = $manifest->files;
          $parsedBackups[$md5][] = $manifest->manifest;
          $parsedBackups[$md5][] = $files["backups"][$backupFileName]["size"];
          $parsedBackups[$md5][] = $manifest->is_locked;
          $parsedBackups[$md5][] = $manifest->cron;
          $parsedBackups[$md5][] = $md5;
          $parsedBackups[$md5][] = $backupFileName;
          $parsedBackups[$md5][] = sanitize_text_field($manifest->domain);
        }
    }

      return $parsedBackups;
    }
  }
