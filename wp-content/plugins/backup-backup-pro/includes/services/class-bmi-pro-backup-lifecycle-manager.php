<?php

namespace BMI\Plugin\Services;

use BMI\Plugin\External\BMI_External_Storage_Manager as StorageManager;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Services\ActionInitiator;

if (!defined('ABSPATH'))
    exit;


class BackupLifecycleManager
{

    private $configuredOption;
    private static $instance = null;

    const STORAGE_STRATEGY_CLOUD_ONLY = 'cloud_only';
    const STORAGE_STRATEGY_HYBRID = 'hybrid';
    const STORAGE_STRATEGY_LOCAL_AND_CLOUD = 'local_and_cloud';

    private $cache = [];

    /**
     * @var StorageManager
     */
    private $storageManager;

    private $backupDir;

    /**
     * BackupLifecycleManager constructor.
     *
     * @param StorageManager $storageManager
     * @param string|null $backupDir
     */
    public function __construct($storageManager = null, $backupDir = null)
    {
        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';

        $this->storageManager = is_null($storageManager) ? StorageManager::getInstance() : $storageManager;
        $this->backupDir = is_null($backupDir) ? BMI_BACKUPS : $backupDir;
    }

    public static function getInstance($storageManager = null, $backupDir = null)
    {
        if (self::$instance === null) {
            if (!class_exists('BMI\Plugin\External\BMI_External_Storage_Manager')) {
                require_once BMI_INCLUDES . '/external/external-storage-manager.php';
            }

            self::$instance = new self($storageManager, $backupDir);
        }
        return self::$instance;
    }


    public function getConfiguredOption()
    {
        if (isset($this->cache['configured_option'])) {
            return $this->cache['configured_option'];
        }

        $this->configuredOption = $this->getConfig('STORAGE:STRATEGY');

        $this->cache['configured_option'] = $this->configuredOption;
        return $this->cache['configured_option'];
    }

    public function updateConfiguredOption($option)
    {
        $updated = $this->setConfig('STORAGE:STRATEGY', $option);
        if ($updated) {
            $this->cache['configured_option'] = $option;
        }
        return $updated;
    }

    public function saveStorageStrategyOption($existingResponse, $strategy)
    {
        if (!in_array($strategy, [self::STORAGE_STRATEGY_CLOUD_ONLY, self::STORAGE_STRATEGY_HYBRID])) {
            return [
                'status' => 'msg',
                'why' => __('Invalid premium storage strategy option', 'backup-backup'),
                'level' => 'error'
            ];
        }

        $isStorageStrategyConfigurable = $this->isStorageStrategyConfigurable($strategy);
        if ($isStorageStrategyConfigurable['status'] !== true) {
            return [
                'status' => 'msg',
                'why' => $isStorageStrategyConfigurable['msg'],
                'level' => 'error'
            ];
        }

        if ($this->getConfiguredOption() == $strategy) {
            return $existingResponse;
        }

        if (!$this->updateConfiguredOption($strategy)) {
            return [
                'status' => 'msg',
                'why' => __('Failed to save backup storage type setting', 'backup-backup'),
                'level' => 'error'
            ];
        }

        $this->initiateApplyStorageStrategyToExistingBackups();

        return $existingResponse;
    }

    /**
     * Check if the storage strategy option can be configured based on whether any storage is configured.
     *
     * @param string $strategy The storage strategy to check.
     * @return array 
     */
    public function isStorageStrategyConfigurable($strategy)
    {
        if (!in_array($strategy, [self::STORAGE_STRATEGY_CLOUD_ONLY, self::STORAGE_STRATEGY_HYBRID])) {
            return [
                'status' => false,
                'msg' => __('Invalid premium storage strategy option', 'backup-backup'),
                'level' => 'error'
            ];
        }

        if (!$this->storageManager->isAnyStorageConfigured()) {
            $message = $strategy == self::STORAGE_STRATEGY_CLOUD_ONLY ?
                __('Cloud storage must be configured before you can store backups only in the cloud. Please connect cloud storage before enabling this option', 'backup-backup') :
                __('Cloud storage must be configured before enabling cloud storage for scheduled backups. Please connect cloud storage before enabling this option', 'backup-backup');
            return [
                'status' => false,
                'msg' => $message,
                'level' => 'error'
            ];
        }
        return [
            'status' => true,
            'msg' => '',
            'level' => 'success'
        ];
    }

    /**
     * Initiate the process of applying the current storage strategy to existing backups synchronously via non-blocking action.
     * 
     */
    public function initiateApplyStorageStrategyToExistingBackups()
    {
        require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-initiator.php';

        $action = new ActionInitiator('APPLY_STORAGE_STRATEGY', [], Dashboard\bmi_get_config('REQUEST:SECRET'));
        $action->execute(ActionInitiator::INITIATOR_URL, [
            'method' => ActionInitiator::HTTP_METHOD_GET,
            'async' => false,
            'timeout' => 0.01,
        ]);

    }



    /**
     * Handle the completion of a backup upload to a storage provider.
     *
     * @param string $backupMd5 The MD5 identifier of the backup.
     * @return bool
     */
    public function handleUploadComplete($backupMd5)
    {
        $manifestPath = $this->backupDir . DIRECTORY_SEPARATOR . $backupMd5 . '.json';

        if (!preg_match('/^[a-f0-9]{32}$/', $backupMd5) || !$this->fileExists($manifestPath)) {
            return false;
        }

        $content = $this->fileGetContents($manifestPath);
        if ($content === false) return false;

        $manifestContent = json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Invalid JSON in manifest: $manifestPath");
            return false;
        }

        $isCron = isset($manifestContent->cron) ? (bool) $manifestContent->cron : false;
        $zipName = isset($manifestContent->name) ? $manifestContent->name : '';

        if (empty($zipName)) {
            return false;
        }


        if ($this->shouldDeleteLocalAfterUpload($isCron, $zipName, $backupMd5)) {
            $this->tryDeleteLocalBackup($zipName, $backupMd5);
        }
        return true;
    }

    /**
     * Attempt to delete the local backup if it is safely stored in all configured clouds.
     *
     * @param string $zipName
     * @param string $backupMd5
     */
    public function tryDeleteLocalBackup($zipName, $backupMd5)
    {
        // We need to ensure it is uploaded to ALL configured storages.
        $configuredStorages = $this->storageManager->getConfiguredStorages();

        // Get the upload status for this backup
        $uploadedBackupsStatus = $this->getOption('bmi_uploaded_backups_status', []);
        $thisBackupStatus = isset($uploadedBackupsStatus[$backupMd5]) ? $uploadedBackupsStatus[$backupMd5] : [];

        if (empty($configuredStorages)) {
            $this->log("No storages configured, skipping local deletion for backup {$backupMd5}");
            return;
        }

        $uploadedToAll = true;
        foreach ($configuredStorages as $serviceName => $config) {
            // Check if this service has successfully received the backup
            // The status array usually has keys like 'backupbliss' => true/false
            if (empty($thisBackupStatus[$serviceName])) {
                $uploadedToAll = false;
                break;
            }
        }

        if ($uploadedToAll) {
            $this->deleteLocalFiles($zipName, $backupMd5);
        }
    }

    /**
     * Delete the local backup file and its manifest.
     *
     * @param string $zipName
     * @param string $backupMd5

     */
    private function deleteLocalFiles($zipName, $backupMd5)
    {
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . basename($zipName);
        $manifestPath = $this->backupDir . DIRECTORY_SEPARATOR . $backupMd5 . '.json';

        if ($this->fileExists($zipPath)) {
            $this->unlinkFile($zipPath);
            $this->log("Deleted local backup file {$zipName} as per storage strategy.");
        }
        if ($this->fileExists($manifestPath) && preg_match('/^[a-f0-9]{32}\.json$/', basename($manifestPath))) {
            $this->unlinkFile($manifestPath);
            $this->log("Deleted local backup manifest {$backupMd5}.json as per storage strategy.");
        }
    }

    /**
     * Determine if local backup files should be deleted after upload based on the storage strategy.
     *
     * @param bool $isCron Indicates if the backup was created via a scheduled task.
     * @param string $zipName The name of the backup zip file.
     * @param string $backupMd5 The MD5 identifier of the backup.
     * @return bool True if local files should be deleted, false otherwise.
     */
    public function shouldDeleteLocalAfterUpload($isCron, $zipName, $backupMd5)
    {
        return $this->getConfiguredOption() == self::STORAGE_STRATEGY_CLOUD_ONLY ||
            ($this->getConfiguredOption() == self::STORAGE_STRATEGY_HYBRID && ($isCron || $this->isBeforeUpdateBackup($zipName, $backupMd5)));
    }

    /**
     * Check if the backup is a before-update backup.
     * 
     * @param mixed $zipName
     * @param mixed $backupMd5
     * @return bool
     */
    public function isBeforeUpdateBackup($zipName, $backupMd5)
    {
        return preg_match('/before_.*?_update/i', $zipName);
    }

    protected function log($message)
    {
        Logger::log($message);
    }

    // wrapper function for easier testing
    protected function getConfig($key)
    {
        return Dashboard\bmi_get_config($key);
    }
    protected function setConfig($key, $value)
    {
        return Dashboard\bmi_set_config($key, $value);
    }
    protected function getOption($key, $default = false)
    {
        return get_option($key, $default);
    }

    protected function fileExists($path)
    {
        return file_exists($path);
    }

    protected function fileGetContents($path)
    {
        return file_get_contents($path);
    }

    protected function unlinkFile($path)
    {
        return @unlink($path);
    }


}
