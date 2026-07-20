<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Backup_Migration_Plugin as BMP;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action.php';

class UrlBackup extends BMI_Action {

    private $backupService;
    private $secretKey;


    public function __construct($parameters, $backupService) {
        parent::__construct($parameters);
        $this->backupService = $backupService;
        $this->secretKey = Dashboard\bmi_get_config('BACKUP:URL:TRIGGER:KEY');
    }


    protected function start() {
        add_action('bmip_before_backup', [$this, 'doRecommendedSettings'], 10, 3);
        add_action('bmip_after_backup', [$this, 'afterExecute'], 10, 4);
        define('BACKUP_TRIGGERED_BY_URL', true);
        return $this->backupService->performBackup(BMI_BACKUP_FULL);
    }

    public function doRecommendedSettings($backupService, $type, $shouldBackup) {
        BMP::extend_execution_time();
        if (session_id()) {
            if(false == session_write_close())
                $this->log('Failed to write session data and close the session');
        }
        if ($backupService->backupMethodManager->currentMethod() != BMI_METHOD_CURL){
            $backupService->backupMethodManager->changeBackupMethod(BMI_METHOD_CURL);
        }
        $backupService->backupMethodManager->disableCLI();
    }

    public function afterExecute($backupService, $type, $shouldBackup, $backupResult) {
        switch ($backupResult['status']) {
            case 'background':
            $this->log(__('Scheduled backup is running in background: ', 'backup-backup') . $backupResult['filename']);
            break;
            case 'msg':
            $this->log($backupResult['msg']);
            break;
        }
    }

    public function isValid() {
		$userSecretKey = isset($this->parameters['uk']) ? $this->parameters['uk'] : null;
		$isRunning = file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65;
		$isCron = file_exists(BMI_BACKUPS . '/.cron') && (time() - filemtime(BMI_BACKUPS . '/.cron')) <= 65;
		return $userSecretKey == $this->secretKey && !$isRunning && !$isCron;
    }
}
