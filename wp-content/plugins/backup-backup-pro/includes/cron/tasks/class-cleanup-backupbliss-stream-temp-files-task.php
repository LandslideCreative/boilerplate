<?php

// Namespace
namespace BMI\Plugin\CRON\Tasks;

// Use
use BMI\Plugin\CRON\AbstractTask;

// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * CleanupBackupblissStreamTempFilesTask
 *
 * Runs daily. Detects the presence of temporary files older than a specified threshold.
 * 
 * Deletes trash files that created by streaming process of backupbliss and are not automatically cleaned up due to process interruption or other unforeseen issues.  
 * 
 * 
 *   TaskManager::register(new CleanupBackupblissStreamTempFilesTask());
 *
 * @package backup-backup
 * @since   2.1.5
 */
class CleanupBackupblissStreamTempFilesTask extends AbstractTask {

  // ── Constants ─────────────────────────────────────────────────────────────

  /**
   * WP action hook that fires this task.
   *
   * @var string
   */
  const HOOK = 'bmi_cleanup_backupbliss_stream_temp_files';

  /**
   * Threshold for file age in seconds. Files older than this will be deleted.
   * 
   */
  const FILE_AGE_THRESHOLD = DAY_IN_SECONDS;



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
    $tmpDir = $this->get_tmp_dir();
    if (is_dir($tmpDir)) {
        foreach (glob($tmpDir . 'backupbliss-bb*') as $file) {
            if (filemtime($file) < time() - self::FILE_AGE_THRESHOLD) {
                @unlink($file);
            }
        }
    }
    $streamDir = $this->get_zip_stream_temp_dir();
    if (is_dir($streamDir)){
      foreach (glob($streamDir . '/BMI*') as $file) {
          if (filemtime($file) < time() - self::FILE_AGE_THRESHOLD) {
              @unlink($file);
          }
      }
      @rmdir($streamDir);
    }
    $bufferDir = $this->get_zip_stream_buffer_dir();
    if (is_dir($bufferDir)) {
        foreach (glob($bufferDir . '/*.bmi') as $file) {
            if (filemtime($file) < time() - self::FILE_AGE_THRESHOLD) {
                @unlink($file);
            }
        }
        @rmdir($bufferDir);
    }

  }

  // ── Protected helpers (overridable for unit tests) ─────────────────────────

  /**
   * Get the dir where backupbliss stream temp files are stored.
   * 
   * @return string
   */
  protected function get_zip_stream_temp_dir() {
    return BMI_TMP . '/zip_stream_state';
  }

  /** Get the dir where backupbliss stream buffer files are stored.
   * 
   * @return string
   */
  protected function get_zip_stream_buffer_dir() {
    return BMI_TMP . '/stream_buffer';
  }

  protected function get_tmp_dir() {
    return BMI_TMP;
  }
}
