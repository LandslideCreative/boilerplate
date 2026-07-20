<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Services\BackupService;
use BMI\Plugin\Backup_Migration_Plugin as BMP;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action.php';

class BeforeUpdateBackup extends BMI_Action {
    private $backupService;

    public $backupTypeMap = [
        'plugins' => BMI_BACKUP_PLUGIN,
        'plugin' => BMI_BACKUP_PLUGIN,
        'themes' => BMI_BACKUP_THEME,
        'theme' => BMI_BACKUP_THEME,
        'core' => BMI_BACKUP_CORE
    ];

    public $isAuto = false;

    

    public function __construct($parameters, $backupService) {
        parent::__construct($parameters);
        $this->backupService = $backupService;
        if (doing_action('pre_auto_update')) {
            $this->isAuto = true;
        }
    }

    protected function start()
    {
        add_action('bmip_before_backup', [$this, 'doRecommendedSettings'], 10, 3);
        add_action('bmip_after_backup', [$this, 'afterExecute'], 10, 4);
        $backupParams = $this->extractParams($this->parameters);
        return $this->backupService->performBackup($backupParams['type'], $backupParams['shouldBeBackup']);
    }

    public function respondSuccess() {
        if (isset($this->result)) {
            echo json_encode($this->result);
            exit;
        } else {
            parent::respondSuccess();
        }
    }

    public function doRecommendedSettings($backupService, $type, $shouldBackup) {
        BMP::extend_execution_time();
        if (session_id()) {
            if(false == session_write_close())
                $this->log('Failed to write session data and close the session');
        }
        if ($backupService->backupMethodManager->currentMethod() != BMI_METHOD_DEFAULT){
            $backupService->backupMethodManager->changeBackupMethod(BMI_METHOD_DEFAULT);
        }
        $backupService->backupMethodManager->disableCLI();
    }

    public function afterExecute($backupService, $type, $shouldBackup, $backupResult) {
        if ($backupService->backupMethodManager->oldMethod() != null) {
            $this->log('Reverting backup method to old method.');
            $backupService->backupMethodManager->changeBackupMethod($backupService->backupMethodManager->oldMethod());
        }

        if ((isset($backupResult['level']) && $backupResult['level'] === 'error') || $backupResult == null || (isset($backupResult['status']) && $backupResult['status'] === 'error')) {
            $debugCode = $backupService->getDebugCode('before-update');
            $this->log('Failed to create backup before ' . $this->isAuto? 'auto' : 'manual' .' update: ' . json_encode($this->parameters));
            $message = __('An error occurred while creating a backup before ' . $this->isAuto? 'auto' : 'manual' .' update %s. For more information, please contact support and provide the following debug code: %s you can click %shere%s to copy the debug code to your clipboard', 'backup-backup');
            $message = sprintf($message, 
                $this->parameters["item"],
                "<span id='debug-code'>{$debugCode}</span>",
                "<a class='bmi-copper' href='#' data-copy='debug-code'>",
                "</a>"
            );
            update_option('bmi_display_before_update_backup_issues', $message);
        } else if (isset($backupResult['filename'])) {
            $this->log('Backup created before update successfully: ' . $backupResult['filename']);
        }
    }


    
    /**
     * Extracts the parameters from the request.
     * 
     * @param array $parameters The parameters should have type and item keys.
     * @return array
     */
    public function extractParams($parameters)
    {
        $data = [
            'type' => $this->backupTypeMap[$parameters['type']],
            'shouldBeBackup' => null
        ];

        switch ($this->backupTypeMap[$parameters['type']]) {
            case BMI_BACKUP_CORE:
                {
                    $data['shouldBeBackup'] = $parameters['item'];
                    break;
                }
            case BMI_BACKUP_PLUGIN:
                {
                    $data['shouldBeBackup'] = $this->getItemPath($parameters['item'], 'plugin', $parameters['type'] === 'plugins');
                    break;
                }
            case BMI_BACKUP_THEME:
                {
                    $data['shouldBeBackup'] = $this->getItemPath($parameters['item'], 'theme', $parameters['type'] === 'themes');
                    break;
                }
            default:
                {
                    break;
                }
        }
        return $data;
    }


    public function getItemPath($item, $type, $isMultiple = false) {
        $path = '';
        if ($isMultiple) {
            $path = [];
            foreach ($item as $i) {
                $path[] = $this->getSingleItemPath($i, $type);
            }
        } else {
            $path = $this->getSingleItemPath($item, $type);
        }
        return $path;
    }

    public function getSingleItemPath($item, $type) {
        $path = '';
        switch ($type) {
            case 'plugin':
                $path = BMP::fixSlashes(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . (dirname($item) === '.' ? $item : dirname($item)));
                break;
            case 'theme':
                $path = BMP::fixSlashes(get_theme_root() . DIRECTORY_SEPARATOR . $item);
                break;
        }
        return $path;
    }

    public function isValid() {
		$isRunning = file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65;
		$isCron = file_exists(BMI_BACKUPS . '/.cron') && (time() - filemtime(BMI_BACKUPS . '/.cron')) <= 65;
		return !$isRunning && !$isCron;
    }

}
