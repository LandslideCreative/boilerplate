<?php

namespace BMI\Plugin\Services;

use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Checker\BMI_Checker as Checker;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

if (!defined('ABSPATH') && !defined('BMI_USING_CLI_FUNCTIONALITY')) {
    exit;
}

/**
 * Class BackupEncryptionManager
 *
 * @package backup-backup-pro
 */
class BackupEncryptionManager
{
    /**
     * @var BackupEncryptionManager|null Singleton instance.
     */
    private static $instance = null;

    /**
     * @var Backups|object|null Backups scanner dependency.
     */
    protected $backupsScanner;

    /**
     * @var Checker|object|null Disk space checker dependency.
     */
    protected $checker;

    /**
     * @var callable|null Factory to instantiate ZipArchive (useful for mocking during tests).
     */
    protected $zipFactory;

    /**
     * @var string|null Base directory where backups are stored.
     */
    protected $backupDir;

    /**
     * @var string|null Base directory for temporary extractions.
     */
    protected $tmpDir;

    /**
     * BackupEncryptionManager constructor.
     *
     * @param object|null   $backupsScanner Optional Backups scanner instance.
     * @param object|null   $checker        Optional BMI_Checker instance.
     * @param callable|null $zipFactory     Optional callback returning a ZipArchive instance.
     * @param string|null   $backupDir      Optional override for backup directory path.
     * @param string|null   $tmpDir         Optional override for temporary directory path.
     */
    public function __construct($backupsScanner = null, $checker = null, $zipFactory = null, $backupDir = null, $tmpDir = null)
    {
        $this->backupsScanner = $backupsScanner;
        $this->checker = $checker;
        $this->zipFactory = $zipFactory;
        $this->backupDir = $backupDir !== null ? $backupDir : (defined('BMI_BACKUPS') ? BMI_BACKUPS : '');
        $this->tmpDir = $tmpDir !== null ? $tmpDir : (defined('BMI_TMP') ? BMI_TMP : sys_get_temp_dir());
    }

    /**
     * Get singleton instance of BackupEncryptionManager.
     *
     * @param object|null   $backupsScanner Optional Backups scanner instance.
     * @param object|null   $checker        Optional BMI_Checker instance.
     * @param callable|null $zipFactory     Optional callback returning a ZipArchive instance.
     * @param string|null   $backupDir      Optional override for backup directory path.
     * @param string|null   $tmpDir         Optional override for temporary directory path.
     * @return BackupEncryptionManager
     */
    public static function getInstance($backupsScanner = null, $checker = null, $zipFactory = null, $backupDir = null, $tmpDir = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($backupsScanner, $checker, $zipFactory, $backupDir, $tmpDir);
        }
        return self::$instance;
    }

    /**
     * Helper to obtain the Backups scanner instance lazily.
     *
     * @return object
     */
    protected function getBackupsScanner()
    {
        if ($this->backupsScanner === null) {
            if (!class_exists('BMI\Plugin\Scanner\BMI_BackupsScanner')) {
                require_once BMI_INCLUDES . '/scanner/backups.php';
            }
            $this->backupsScanner = Backups::getInstance();
        }
        return $this->backupsScanner;
    }

    /**
     * Helper to obtain the space checker instance lazily.
     *
     * @return object
     */
    protected function getChecker()
    {
        if ($this->checker === null) {
            if (!class_exists('BMI\Plugin\Checker\BMI_Checker')) {
                require_once BMI_INCLUDES . '/check/checker.php';
            }
            $this->checker = new Checker();
        }
        return $this->checker;
    }

    /**
     * Helper to create a new ZipArchive object using the factory or constructor.
     *
     * @return ZipArchive|object
     */
    protected function createZipArchive()
    {
        if (is_callable($this->zipFactory)) {
            return ($this->zipFactory)();
        }
        return new ZipArchive();
    }

    /**
     * Verify that there is sufficient free disk space based on a multiplier of target file size.
     *
     * @param string $filePath   Path to target file.
     * @param float  $multiplier Size multiplier required (e.g. 1.0 for encrypt, 1.5 for extract).
     * @return bool True if enough space or size determination fails, false if insufficient.
     */
    protected function verifyFreeDiskSpace($filePath, $multiplier)
    {
        $size = @filesize($filePath);
        if ($size !== false) {
            $checker = $this->getChecker();
            return (bool) $checker->check_free_space($size * $multiplier, true);
        }
        return true;
    }

    /**
     * Get the total number of files in the open ZipArchive.
     *
     * @param ZipArchive|object $zip Open ZipArchive object.
     * @return int Total files count.
     */
    protected function getZipFilesCount($zip)
    {
        return $zip->numFiles;
    }

    /**
     * Compute maximum execution limit in seconds for a batch slice before yielding.
     *
     * @return float Maximum execution limit in seconds.
     */
    protected function getMaxExecutionLimit()
    {
        $maxExecutionTime = function_exists('ini_get') ? intval(ini_get('max_execution_time')) : 30;
        return ($maxExecutionTime > 0) ? $maxExecutionTime * 0.7 : 10.0;
    }

    /**
     * Determine whether the current batch processing loop should yield based on processed count and execution time.
     *
     * @param float $startTime  Time when batch loop started.
     * @param int   $processed  Number of items processed so far.
     * @param int   $batchLimit Maximum items to process per batch.
     * @return bool True if batch should yield, false to continue.
     */
    protected function shouldYieldBatch($startTime, $processed, $batchLimit = 10000)
    {
        return ($processed >= $batchLimit || (microtime(true) - $startTime) > $this->getMaxExecutionLimit());
    }

    /**
     * Compute the encrypted backup filename by appending '-enc' before the extension.
     *
     * @param string $backupName Original backup filename.
     * @return string Encrypted backup filename.
     */
    protected function getEncryptedBackupName($backupName)
    {
        $firstDot = strpos($backupName, '.');
        if ($firstDot !== false) {
            $base = substr($backupName, 0, $firstDot);
            $ext = substr($backupName, $firstDot);
            return $base . '-enc' . $ext;
        }
        return $backupName . '-enc';
    }

    /**
     * Compute the decrypted backup filename by removing '-enc' suffix before the extension if present.
     *
     * @param string $backupName Encrypted backup filename.
     * @return string Decrypted backup filename.
     */
    protected function getDecryptedBackupName($backupName)
    {
        $firstDot = strpos($backupName, '.');
        if ($firstDot !== false) {
            $base = substr($backupName, 0, $firstDot);
            $ext = substr($backupName, $firstDot);
            if (substr($base, -4) === '-enc') {
                $base = substr($base, 0, -4);
            }
            return $base . $ext;
        }
        if (substr($backupName, -4) === '-enc') {
            return substr($backupName, 0, -4);
        }
        return $backupName;
    }

    /**
     * Decode, update `is_encrypted` and `name` properties on a manifest JSON string, and return the encoded JSON.
     *
     * @param string|false $manifestJson Raw manifest JSON string.
     * @param bool         $isEncrypted  Target encrypted boolean value.
     * @param string|null  $newName      Optional target backup name value for manifest.
     * @return string|false Updated manifest JSON string or false on failure.
     */
    protected function updateManifestContent($manifestJson, $isEncrypted, $newName = null)
    {
        if ($manifestJson === false || $manifestJson === null || $manifestJson === '') {
            return false;
        }
        $manifestObj = json_decode($manifestJson);
        if ($manifestObj) {
            $manifestObj->is_encrypted = (bool) $isEncrypted;
            if ($newName !== null) {
                $manifestObj->name = $newName;
            }
            return json_encode($manifestObj);
        }
        return false;
    }

    /**
     * Read and update the `is_encrypted` flag and optional `name` in bmi_backup_manifest.json inside an open ZipArchive.
     *
     * @param ZipArchive|object $zip         Open ZipArchive object.
     * @param bool              $isEncrypted Target encrypted boolean value.
     * @param string|null       $newName     Optional target backup name value for manifest.
     * @return void
     */
    protected function updateManifestEncryptionInZip($zip, $isEncrypted, $newName = null)
    {
        $manifestJson = $zip->getFromName('bmi_backup_manifest.json');
        $updatedJson = $this->updateManifestContent($manifestJson, $isEncrypted, $newName);
        if ($updatedJson !== false) {
            $zip->addFromString('bmi_backup_manifest.json', $updatedJson);
        }
    }

    /**
     * Validate common parameters and prepare data for encryption or decryption operations.
     *
     * @param array  $post       Request parameters (`backup`, `password`, `offset`, etc.).
     * @param string $actionName Name of action (`encryption` or `decryption`) for error messaging.
     * @return array Prepared data array on success or error status array on failure.
     */
    protected function prepareBackupOperation(array $post, $actionName)
    {
        if (!class_exists('ZipArchive') && !is_callable($this->zipFactory)) {
            return ['status' => 'error', 'msg' => 'ZipArchive class is required for ' . $actionName . '.'];
        }

        $backupName = sanitize_file_name(isset($post['backup']) ? $post['backup'] : '');
        $password = isset($post['password']) ? $post['password'] : '';
        $offset = isset($post['offset']) ? intval($post['offset']) : 0;

        if (empty($backupName) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Backup name and password are required.'];
        }

        $backupPath = rtrim($this->backupDir, '/\\') . DIRECTORY_SEPARATOR . $backupName;
        if (!file_exists($backupPath)) {
            return ['status' => 'error', 'msg' => 'Backup file does not exist locally.'];
        }

        $backups = $this->getBackupsScanner();
        $oldManifest = $backups->getManifest($backupPath);
        $oldHash = is_array($oldManifest) && isset($oldManifest[7]) ? $oldManifest[7] : $backups->getBackupHash($backupPath, null);

        return [
            'backupName' => $backupName,
            'password'   => $password,
            'offset'     => $offset,
            'backupPath' => $backupPath,
            'backups'    => $backups,
            'oldHash'    => $oldHash
        ];
    }

    /**
     * Handle backup encryption across batches or in one step.
     *
     * @param array $post Request parameters (`backup`, `password`, `offset`).
     * @return array Response status (`error`, `step`, or `success`).
     */
    public function encryptBackup(array $post)
    {
        $prep = $this->prepareBackupOperation($post, 'encryption');
        if (isset($prep['status']) && $prep['status'] === 'error') {
            return $prep;
        }

        $backupName = $prep['backupName'];
        $password   = $prep['password'];
        $offset     = $prep['offset'];
        $backupPath = $prep['backupPath'];
        $backups    = $prep['backups'];
        $oldHash    = $prep['oldHash'];

        if ($offset === 0) {
            if (!$this->verifyFreeDiskSpace($backupPath, 1.0)) {
                return ['status' => 'error', 'msg' => __('Not enough free disk space to encrypt this backup.', 'backup-backup')];
            }
        }

        $startTime = microtime(true);
        $zip = $this->createZipArchive();
        if ($zip->open($backupPath) !== true) {
            return ['status' => 'error', 'msg' => 'Could not open backup archive.'];
        }
        $zip->setPassword($password);

        $numFiles = $this->getZipFilesCount($zip);
        $processed = 0;

        for ($i = $offset; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== 'bmi_backup_manifest.json' && $name !== 'bmi_logs_this_backup.log' && $name !== '.backup_name') {
                if (method_exists($zip, 'setEncryptionIndex')) {
                    $zip->setEncryptionIndex($i, ZipArchive::EM_AES_256, $password);
                }
            }
            $processed++;
            if ($this->shouldYieldBatch($startTime, $processed)) {
                $nextOffset = $i + 1;
                if ($nextOffset < $numFiles) {
                    if ($zip->close() !== true) {
                        return ['status' => 'error', 'msg' => __('Failed to save archive during encryption step.', 'backup-backup')];
                    }
                    return [
                        'status' => 'step',
                        'offset' => $nextOffset,
                        'total' => $numFiles,
                        'msg' => sprintf(__('Encrypting backup files (%d/%d)...', 'backup-backup'), $nextOffset, $numFiles)
                    ];
                }
                break;
            }
        }

        $newBackupName = $this->getEncryptedBackupName($backupName);
        $newBackupPath = rtrim($this->backupDir, '/\\') . DIRECTORY_SEPARATOR . $newBackupName;

        $this->updateManifestEncryptionInZip($zip, true, $newBackupName);

        $zip->addFromString('.backup_name', $newBackupName);
        if (method_exists($zip, 'setEncryptionName')) {
            $zip->setEncryptionName('.backup_name', ZipArchive::EM_AES_256, $password);
        }

        if ($zip->close() !== true) {
            return ['status' => 'error', 'msg' => __('Failed to close and save encrypted archive.', 'backup-backup')];
        }

        if ($newBackupPath !== $backupPath) {
            if (!rename($backupPath, $newBackupPath)) {
                return ['status' => 'error', 'msg' => __('Failed to rename encrypted archive with -enc suffix.', 'backup-backup')];
            }
        }

        Logger::log("Deleting the old backup with the key  - " . $backupName . " after encryption process completed");
        $backups->deleteBackup($backupName, ['hash' => $oldHash, 'isCloud' => true], true);

        return ['status' => 'success'];
    }

    /**
     * Handle backup decryption across extraction and archive phases.
     *
     * @param array $post Request parameters (`backup`, `password`, `phase`, `offset`).
     * @return array Response status (`error`, `step`, or `success`).
     */
    public function decryptBackup(array $post)
    {
        $prep = $this->prepareBackupOperation($post, 'decryption');
        if (isset($prep['status']) && $prep['status'] === 'error') {
            return $prep;
        }

        $backupName = $prep['backupName'];
        $password   = $prep['password'];
        $offset     = $prep['offset'];
        $backupPath = $prep['backupPath'];
        $backups    = $prep['backups'];
        $oldHash    = $prep['oldHash'];
        $phase      = isset($post['phase']) ? sanitize_text_field($post['phase']) : 'extract';

        $extractDir = rtrim($this->tmpDir, '/\\') . DIRECTORY_SEPARATOR . pathinfo($backupName, PATHINFO_FILENAME) . '_unenc';

        if ($phase === 'extract') {
            return $this->handleDecryptionExtractPhase($backupPath, $password, $offset, $extractDir);
        } elseif ($phase === 'archive') {
            return $this->handleDecryptionArchivePhase($backupPath, $backupName, $oldHash, $offset, $extractDir, $backups);
        }

        return ['status' => 'error', 'msg' => 'Invalid batch phase specified.'];
    }

    /**
     * Execute the extraction phase of decrypting a backup archive.
     *
     * @param string $backupPath Absolute path to local backup zip file.
     * @param string $password   Password to unlock archive.
     * @param int    $offset     File index offset for batch processing.
     * @param string $extractDir Target temporary directory for extracted files.
     * @return array Status array.
     */
    protected function handleDecryptionExtractPhase($backupPath, $password, $offset, $extractDir)
    {
        if ($offset === 0) {
            if (!$this->verifyFreeDiskSpace($backupPath, 1.5)) {
                return ['status' => 'error', 'msg' => __('Not enough free disk space to extract and decrypt backup.', 'backup-backup')];
            }
            if (file_exists($extractDir)) {
                $this->deleteDirectoryRecursively($extractDir);
            }
            if (!mkdir($extractDir, 0755, true)) {
                return ['status' => 'error', 'msg' => __('Could not create temporary extraction directory.', 'backup-backup')];
            }
        }

        $startTime = microtime(true);
        $zip = $this->createZipArchive();
        if ($zip->open($backupPath) !== true) {
            return ['status' => 'error', 'msg' => 'Could not open encrypted backup archive.'];
        }
        $zip->setPassword($password);

        $numFiles = $this->getZipFilesCount($zip);
        $processed = 0;

        for ($i = $offset; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $zip->extractTo($extractDir, $name);
            $processed++;

            if ($this->shouldYieldBatch($startTime, $processed)) {
                $nextOffset = $i + 1;
                if ($nextOffset < $numFiles) {
                    if ($zip->close() !== true) {
                        return ['status' => 'error', 'msg' => __('Failed to close archive during extraction step.', 'backup-backup')];
                    }
                    return [
                        'status' => 'step',
                        'phase' => 'extract',
                        'offset' => $nextOffset,
                        'total' => $numFiles,
                        'msg' => sprintf(__('Extracting backup files (%d/%d)...', 'backup-backup'), $nextOffset, $numFiles)
                    ];
                }
                break;
            }
        }

        if ($zip->close() !== true) {
            return ['status' => 'error', 'msg' => __('Failed to close archive after extraction.', 'backup-backup')];
        }
        return [
            'status' => 'step',
            'phase' => 'archive',
            'offset' => 0,
            'msg' => __('Starting reconstruction of unencrypted backup...', 'backup-backup')
        ];
    }

    /**
     * Execute the archive/reconstruction phase of decrypting a backup archive.
     *
     * @param string $backupPath Absolute path to original local backup file.
     * @param string $backupName Sanitize file name of original backup.
     * @param string $oldHash    MD5 hash or manifest hash of old backup.
     * @param int    $offset     File index offset for batch processing.
     * @param string $extractDir Directory containing unencrypted extracted files.
     * @param object $backups    Backups scanner instance.
     * @return array Status array.
     */
    protected function handleDecryptionArchivePhase($backupPath, $backupName, $oldHash, $offset, $extractDir, $backups)
    {
        if (!file_exists($extractDir)) {
            return ['status' => 'error', 'msg' => 'Extraction directory missing during reconstruction.'];
        }

        $files = $this->collectExtractedFiles($extractDir);
        $totalFiles = count($files);
        $tmpZipPath = $backupPath . '.dec.tmp';

        $startTime = microtime(true);
        $zip = $this->createZipArchive();
        $openMode = ($offset === 0) ? (ZipArchive::CREATE | ZipArchive::OVERWRITE) : ZipArchive::CREATE;
        if ($zip->open($tmpZipPath, $openMode) !== true) {
            return ['status' => 'error', 'msg' => 'Could not open temporary unencrypted archive for writing.'];
        }

        $newBackupName = $this->getDecryptedBackupName($backupName);
        $processed = 0;

        for ($i = $offset; $i < $totalFiles; $i++) {
            $item = $files[$i];
            if ($item['rel'] === 'bmi_backup_manifest.json') {
                $content = @file_get_contents($item['real']);
                if ($content !== false) {
                    $updatedManifest = $this->updateManifestContent($content, false, $newBackupName);
                    if ($updatedManifest !== false) {
                        $content = $updatedManifest;
                    }
                    $zip->addFromString('bmi_backup_manifest.json', $content);
                }
            } elseif ($item['rel'] === '.backup_name') {
                continue;
            } else {
                $zip->addFile($item['real'], $item['rel']);
            }

            $processed++;
            if ($this->shouldYieldBatch($startTime, $processed)) {
                $nextOffset = $i + 1;
                if ($nextOffset < $totalFiles) {
                    if ($zip->close() !== true) {
                        return ['status' => 'error', 'msg' => __('Failed to save unencrypted archive during reconstruction step.', 'backup-backup')];
                    }
                    return [
                        'status' => 'step',
                        'phase' => 'archive',
                        'offset' => $nextOffset,
                        'total' => $totalFiles,
                        'msg' => sprintf(__('Reconstructing unencrypted backup (%d/%d)...', 'backup-backup'), $nextOffset, $totalFiles)
                    ];
                }
                break;
            }
        }

        $newBackupPath = rtrim($this->backupDir, '/\\') . DIRECTORY_SEPARATOR . $newBackupName;

        $zip->addFromString('.backup_name', $newBackupName);
        if ($zip->close() !== true) {
            @unlink($tmpZipPath);
            return ['status' => 'error', 'msg' => __('Failed to close and finalize unencrypted archive.', 'backup-backup')];
        }

        if (!rename($tmpZipPath, $newBackupPath)) {
            @unlink($tmpZipPath);
            return ['status' => 'error', 'msg' => 'Failed to replace original backup file with unencrypted archive.'];
        }

        $this->deleteDirectoryRecursively($extractDir);

        Logger::log("Deleting the old backup with the key  - " . $backupName . " after decyption process completed");
        $backups->deleteBackup($backupName, ['hash' => $oldHash, 'isCloud' => true], true);

        return ['status' => 'success'];
    }

    /**
     * Collect all file paths within an extraction directory relative to the directory root.
     *
     * @param string $extractDir Absolute directory path.
     * @return array List of associative arrays with 'real' and 'rel' keys.
     */
    protected function collectExtractedFiles($extractDir)
    {
        $realExtractDir = realpath($extractDir);
        if ($realExtractDir === false) {
            $realExtractDir = $extractDir;
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(rtrim($realExtractDir, '/\\')) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $files[] = ['real' => $filePath, 'rel' => $relativePath];
            }
        }
        return $files;
    }

    /**
     * Recursively delete a directory and all of its contents.
     *
     * @param string $dir Target directory absolute path.
     * @return void
     */
    public function deleteDirectoryRecursively($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectoryRecursively($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
