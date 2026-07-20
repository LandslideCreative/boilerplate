<?php

// Namespace
namespace BMI\Plugin\CRON\Tasks;

// Use
use BMI\Plugin\CRON\AbstractTask;

// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * DeleteLocalBackupsAfterUploadTask - in case there was an issue with deletion of local backup files after successful upload, this task will attempt to delete any leftover local backup files.
 * 
 * Runs daily. 
 *
 * @package backup-backup
 * @since   2.1.5
 */
class DeleteLocalBackupsAfterUploadTask extends AbstractTask {

  // ── Constants ─────────────────────────────────────────────────────────────

  /**
   * WP action hook that fires this task.
   *
   * @var string
   */
  const HOOK = 'bmi_delete_local_backups_after_upload';

  /**
   * Manifest index for the "is_cron" flag.
   */
  const MANIFEST_INDEX_IS_CRON = 6;

  /**
   * Manifest index for the "md5" flag.
   */
  const MANIFEST_INDEX_MD5 = 7;

  /**
   * Backup lifecycle manager instance.
   *
   * @var \BMI\Plugin\Services\BackupLifecycleManager
   */
  protected $backupLifecycleManager;

  /**
   * Backups scanner instance.
   *
   * @var \BMI\Plugin\Scanner\BMI_BackupsScanner
   */
  protected $backupsScanner;

  /**
   * External storage manager instance.
   *
   * @var \BMI\Plugin\External\BMI_External_Storage_Manager
   */
  protected $externalStorageManager;

  /**
   * Backups list
   * 
   * @var array
   */
  protected $backupsList = [];

  // ── AbstractTask contract ──────────────────────────────────────────────────

  /**
   * @inheritDoc
   */
  public function get_hook() {
    return self::HOOK;
  }

  /**
   * @inheritDoc
   *
   */
  public function get_interval() {
    return 'daily';
  }

  // ── Task logic ─────────────────────────────────────────────────────────────

  /**
   * Validate the storage strategy and fix it when cloud storage is absent.
   *
   * @return void
   */
  public function run() {
    $this->load_dependencies();
    $this->refresh_bmi_uploaded_backups_status();
    // Get backups
    $manifests = $this->backupsList['local'] ?? [];

    foreach ($manifests as $filename => $manifest) {
        $md5 = $manifest[self::MANIFEST_INDEX_MD5];
        $isCron = $manifest[self::MANIFEST_INDEX_IS_CRON];

        if (empty($md5)) {
            continue;
        }

        if ($this->backupLifecycleManager->shouldDeleteLocalAfterUpload($isCron, $filename, $md5)) {
            $this->backupLifecycleManager->tryDeleteLocalBackup($filename, $md5);
        }
    }


  }

  public function load_dependencies() {
    require_once BMI_PRO_INC . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-backup-lifecycle-manager.php';
    require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
    require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'external' . DIRECTORY_SEPARATOR . 'external-storage-manager.php';
    $this->backupLifecycleManager = \BMI\Plugin\Services\BackupLifecycleManager::getInstance();
    $this->backupsScanner = new \BMI\Plugin\Scanner\BMI_BackupsScanner();
    $this->externalStorageManager = \BMI\Plugin\External\BMI_External_Storage_Manager::getInstance();
    $this->backupsList = $this->backupsScanner->getAvailableBackups();
  }

  /**
   * Refreshes the 'bmi_uploaded_backups_status' option by cross-referencing local backups with configured external storages, ensuring accurate tracking of backup upload statuses.
   *
   * @return void
   */
  public function refresh_bmi_uploaded_backups_status() {
    $configuredStoragesArray = $this->externalStorageManager->getConfiguredStorages();
    $configuredStorages = array_keys($configuredStoragesArray);
    if (empty($configuredStorages)) {
      return;
    }

    $option = get_option('bmi_uploaded_backups_status', []);
    foreach ($this->backupsList['local'] as $filename => $manifest) {
      $md5 = $manifest[self::MANIFEST_INDEX_MD5];
      if (!isset($option[$md5])) $option[$md5] = [];
      foreach ($configuredStorages as $storage) {
        $md5s = array_keys($this->backupsList['external'][$storage]);
        if (in_array($md5, $md5s)) {
          $option[$md5][$storage] = true;
        }
      }
    }
    update_option('bmi_uploaded_backups_status', $option);
  }

}
