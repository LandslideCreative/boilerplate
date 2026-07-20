<?php

declare(strict_types=1);

namespace BMI\Plugin\Pro\Zip;

use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\Heart\BMI_Backup_Heart;

/**
 * BMIZipBatchProcessor — Backup batch orchestration for streaming ZIP.
 *
 * Handles the full lifecycle of a single streaming backup batch:
 * database files (batch 1), regular file batches, and the final batch
 * (which includes archive finalization and manifest streaming).
 *
 * Design: This class owns all batch-specific orchestration logic,
 * delegating ZIP operations exclusively to BMIZipStream.
 *
 * @package BMI\Plugin\Pro\Zip
 */
class BMIZipBatchProcessor
{
    /** @var string State sub-directory name under BMI_TMP */
    private const STATE_DIR_NAME = 'zip_stream_state';

    /** @var BMI_Backup_Heart */
    private BMI_Backup_Heart $heartbeat;

    /** @var null|BMIZipBatchProcessor Singleton instance for global access */
    private static ?BMIZipBatchProcessor $instance = null;

    /**
     * Get the singleton instance of BMIZipBatchProcessor.
     *
     * @return BMIZipBatchProcessor
     */
    public static function getInstance(BMI_Backup_Heart $heartbeat): BMIZipBatchProcessor
    {
        if (self::$instance === null) {
            self::$instance = new self($heartbeat);
        }
        return self::$instance;
    }

    public function __construct(BMI_Backup_Heart $heartbeat)
    {
        $this->heartbeat = $heartbeat;
        add_action('bmi_zip_batch_processor_cooldown_hit', [$this, 'handleUploadCooldown']);
    }

     /**
      * Check if the current batch can be processed.
      *
      * Verifies that the backup process is in a valid state for ZIP streaming,
      * and that the necessary conditions are met to proceed with batch processing.
      *
      * @return bool True if processing can proceed; false otherwise.
      */
    private static function ableToProcess(): bool
    {
        if (get_transient('bmi_zip_upload_cooldown')) {
            do_action('bmi_zip_batch_processor_cooldown_hit');
            return false;
        }
        return true;
    }
    /**
     * Process a single streaming backup batch.
     *
     * Entry point for the bmip_streaming_backup_process_batch action. Handles state
     * directory creation, ZipStream initialisation and session resumption,
     * then delegates to the appropriate batch handler.
     *
     */
    public function process(): void
    {
        $heartbeat = self::$instance->heartbeat;
        if (!self::ableToProcess()) {
            return;
        }
        $stateDirectory = BMI_TMP . DIRECTORY_SEPARATOR . self::STATE_DIR_NAME;
        if (!file_exists($stateDirectory)) {
            mkdir($stateDirectory, 0755, true);
        }

        try {
            $zipStream = new BMIZipStream($stateDirectory, $heartbeat->backupname);
        } catch (\Throwable $e) {
            $heartbeat->send_error('ZipStream initialization failed: ' . $e->getMessage());
            return;
        }

        try {
            $zipStream->startSession($heartbeat->identy);
        } catch (\Throwable $e) {
            $heartbeat->send_error('ZipStream session failed: ' . $e->getMessage());
            return;
        }

        if ($heartbeat->it === 1 && self::processDatabaseBatch($zipStream, $heartbeat)) {
            return;
        }

        if (!self::processFileBatch($zipStream, $heartbeat)) {
            return;
        }

        if ($heartbeat->final_batch === true) {
            self::processFinalBatch($zipStream, $heartbeat);
            return;
        }

        try {
            $zipStream->saveProgress();
        } catch (\Throwable $e) {
            $zipStream->abort('Progress save failed', false);
            $heartbeat->send_error('ZipStream progress save failed: ' . $e->getMessage());
            return;
        }

        $zipStream->close();
    }

    /**
     * Process database files in the first batch.
     *
     * Resolves either a single DB file or a directory of DB files (v2 engine),
     * adds them to the archive, saves progress, and closes the session.
     *
     * @param BMIZipStream $zipStream Active zip stream.
     * @param object       $heartbeat Backup process controller.
     * @return bool True if this method handled the batch (caller should return);
     *              false if no DB files were found (caller should continue).
     */
    private static function processDatabaseBatch(BMIZipStream $zipStream, object $heartbeat): bool
    {
        $_dbfile = $heartbeat->dbfile;
        $_db_dir = $heartbeat->db_dir_v2;
        if (strpos($_dbfile, 'file://') !== false) $_dbfile = substr($_dbfile, 7);
        if (strpos($_db_dir, 'file://') !== false) $_db_dir = substr($_db_dir, 7);

        $dbFiles = [];
        if (file_exists($_dbfile)) {
            $dbFiles[] = $_dbfile;
        } elseif (file_exists($_db_dir) && is_dir($_db_dir)) {
            $heartbeat->db_v2_engine = true;
            foreach (scandir($_db_dir) as $name) {
                if ($name !== '.' && $name !== '..') {
                    $dbFiles[] = $_db_dir . DIRECTORY_SEPARATOR . $name;
                }
            }
        }

        if (empty($dbFiles)) {
            $heartbeat->output->log('Performing site files backup...', 'STEP');
            return false;
        }

        try {
            foreach ($dbFiles as $dbFile) {
                $zipStream->addPath($heartbeat->cutDir($dbFile), $dbFile);
            }
        } catch (\Throwable $e) {
            $zipStream->abort('DB file streaming failed', false);
            $heartbeat->send_error('ZipStream DB batch failed: ' . $e->getMessage());
            return true;
        }

        $heartbeat->output->log('Database added to the backup stream.', 'SUCCESS');
        $heartbeat->output->log('Performing site files backup...', 'STEP');

        try {
            $zipStream->saveProgress();
        } catch (\Throwable $e) {
            $zipStream->abort('Progress save failed', false);
            $heartbeat->send_error('ZipStream progress save failed: ' . $e->getMessage());
            return true;
        }

        $zipStream->close();
        return true;
    }

    /**
     * Load, parse, and stream a regular file batch.
     *
     * @param BMIZipStream $zipStream Active zip stream.
     * @param object       $heartbeat Backup process controller.
     * @return bool True to continue to the next stage; false to stop.
     */
    private static function processFileBatch(BMIZipStream $zipStream, object $heartbeat): bool
    {
        $listFile = $heartbeat->load_batch();
        if ($listFile === false) {
            $zipStream->close();
            return false;
        }

        $absWo    = ABSPATH;
        $wpcDirWo = WP_CONTENT_DIR;
        if (strpos($absWo,    'file://') !== false) $absWo    = substr(ABSPATH,        7);
        if (strpos($wpcDirWo, 'file://') !== false) $wpcDirWo = substr(WP_CONTENT_DIR, 7);

        $needManipulation = strpos($wpcDirWo, $absWo) === false;

        [$parsedFiles, $totalSize] = self::parseFileList($listFile, $absWo, $wpcDirWo, $heartbeat);

        if (count($parsedFiles) === 1) {
            $heartbeat->output->log('Adding: 1 file... [Size: ' . $heartbeat->humanSize($totalSize) . ']', 'INFO');
            $heartbeat->output->log('Alone-file mode for: ' . $parsedFiles[0] . ' file...', 'INFO');
        } else {
            $heartbeat->output->log('Adding: ' . count($parsedFiles) . ' files... [Size: ' . $heartbeat->humanSize($totalSize) . ']', 'INFO');
        }

        if ((60 * (1024 * 1024)) < $totalSize) {
            $heartbeat->output->log('Current batch is quite large, it may take some time...', 'WARN');
        }

        try {
            foreach ($parsedFiles as $file) {
                if ($needManipulation && strpos($file, $wpcDirWo) !== false) {
                    $archivePath = 'wordpress' . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . substr($file, strlen($wpcDirWo));
                } else {
                    $archivePath = 'wordpress' . DIRECTORY_SEPARATOR . substr($file, strlen($absWo));
                }

                $zipStream->addPath(BMP::fixSlashes($archivePath, '/'), $file);
            }
        } catch (\Throwable $e) {
            $zipStream->abort('File batch streaming failed', false);
            $heartbeat->send_error('ZipStream file batch failed: ' . $e->getMessage());
            return false;
        }

        if ($listFile && file_exists($listFile)) {
            $heartbeat->unlinksafe($listFile);
        }

        $heartbeat->filessofar += count($parsedFiles);
        $heartbeat->output->progress($heartbeat->filessofar . '/' . $heartbeat->total_files);
        $heartbeat->output->log('Milestone: ' . $heartbeat->filessofar . '/' . $heartbeat->total_files . ' [' . $heartbeat->batches_left . ' batches left]', 'SUCCESS');
        $heartbeat->output->log('Time since last batch: ' . (time() - $heartbeat->startOfBatch) . ' seconds.', 'VERBOSE');
        $heartbeat->startOfBatch = time();

        return true;
    }

    /**
     * Add extra files, finalize the archive, and stream the manifest.
     *
     * @param BMIZipStream $zipStream Active zip stream.
     * @param object       $heartbeat Backup process controller.
     */
    private static function processFinalBatch(BMIZipStream $zipStream, object $heartbeat): void
    {
        $heartbeat->output->log('Adding final files to this batch...', 'STEP');
        $heartbeat->output->log('Adding manifest as addition...', 'INFO');

        try {
            foreach ($heartbeat->get_final_batch() as $file) {
                $zipStream->addPath($heartbeat->cutDir($file), $file);
            }
            $heartbeat->final_made = true;
        } catch (\Throwable $e) {
            $zipStream->abort('Final batch streaming failed', false);
            $heartbeat->send_error('ZipStream final batch failed: ' . $e->getMessage());
            return;
        }

        try {
            $zipStream->finalize();
            $heartbeat->output->log('Backup stream finalized successfully.', 'SUCCESS');

            $heartbeat->output->log('Streaming the manifest to the destination...', 'STEP');
            $zipStream->streamManifest(file_get_contents($heartbeat->manifest));
            $heartbeat->output->log('Manifest streamed successfully.', 'SUCCESS');
        } catch (\Throwable $e) {
            $heartbeat->send_error('ZipStream finalization failed: ' . $e->getMessage());
            return;
        }

        $heartbeat->log_final_batch();
    }

    /**
     * Parse a batch file list into validated file paths and total size.
     *
     * File entries support three positional prefixes:
     *   @1@ → path relative to WP_CONTENT_DIR
     *   @2@ → path relative to ABSPATH
     *   (none) → absolute path
     *
     * @param string $listFile  Path to the batch file list.
     * @param string $absWo     Normalised ABSPATH (no file:// prefix).
     * @param string $wpcDirWo  Normalised WP_CONTENT_DIR (no file:// prefix).
     * @param object $heartbeat Heartbeat for logging; total_files is decremented
     *                          for skipped/missing entries.
     * @return array{0: string[], 1: int} Tuple of [$parsedFiles, $totalSize].
     */
    private static function parseFileList(string $listFile, string $absWo, string $wpcDirWo, object $heartbeat): array
    {
        $rawFiles    = explode("\r\n", file_get_contents($listFile));
        $totalSize   = 0;
        $parsedFiles = [];

        foreach ($rawFiles as $raw) {
            if (strlen(trim($raw)) <= 1) {
                $heartbeat->total_files--;
                continue;
            }

            $parts = explode(',', $raw);
            $last  = count($parts) - 1;
            $size  = intval($parts[$last]);
            unset($parts[$last]);
            $entry = implode(',', $parts);

            if ($entry[0] . $entry[1] . $entry[2] === '@1@') {
                $file = $wpcDirWo . DIRECTORY_SEPARATOR . substr($entry, 3);
            } elseif ($entry[0] . $entry[1] . $entry[2] === '@2@') {
                $file = $absWo . DIRECTORY_SEPARATOR . substr($entry, 3);
            } else {
                $file = $entry;
            }

            if (!file_exists($file)) {
                $heartbeat->output->log('Removing this file from backup (it does not exist anymore): ' . $file, 'WARN');
                $heartbeat->total_files--;
                continue;
            }

            $parsedFiles[] = $file;
            $totalSize    += $size;
        }

        return [$parsedFiles, $totalSize];
    }

    /**
     * Handle the upload cooldown event.
      *
      * This method is triggered when the 'bmi_zip_batch_processor_cooldown_hit' action is fired, indicating that the upload cooldown is active.
     */
    public function handleUploadCooldown(): void
    {
        $this->heartbeat->output->log('Upload cooldown is active. Waiting before resuming ZIP batch processing.', 'WARN');
    }
}
