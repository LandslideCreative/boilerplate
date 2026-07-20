<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH'))
    exit;

use BMI\Plugin\Services\BackupLifecycleManager;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action.php';

class ApplyStorageStrategy extends BMI_Action
{

    private $backupLifecycleManager;

    private $backupsScanner;

    const MANIFEST_INDEX_IS_CRON = 6;
    const MANIFEST_INDEX_MD5 = 7;



    public function __construct($parameters, $backupLifecycleManager = null, $backupsScanner = null)
    {
        parent::__construct($parameters);
        $this->backupLifecycleManager = $backupLifecycleManager === null ? BackupLifecycleManager::getInstance() : $backupLifecycleManager;
        $this->backupsScanner = $backupsScanner === null ? new Backups() : $backupsScanner;

    }

    protected function start()
    {
        // Get backups
        $manifests = $this->backupsScanner->getAvailableBackups('local');

        foreach ($manifests['local'] as $filename => $manifest) {
            $md5 = $manifest[self::MANIFEST_INDEX_MD5];
            $isCron = $manifest[self::MANIFEST_INDEX_IS_CRON];

            if (empty($md5)) {
                continue;
            }

            if ($this->backupLifecycleManager->shouldDeleteLocalAfterUpload($isCron, $filename, $md5)) {
                $this->backupLifecycleManager->tryDeleteLocalBackup($filename, $md5);
            }
        }

        return true;
    }

    public function isValid()
    {
        return true;
    }

}
