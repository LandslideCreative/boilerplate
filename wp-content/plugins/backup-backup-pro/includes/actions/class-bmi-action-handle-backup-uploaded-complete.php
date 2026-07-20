<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH'))
    exit;

use BMI\Plugin\Services\BackupLifecycleManager;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action.php';

class HandleBackupUploadedComplete extends BMI_Action
{

    private $backupLifecycleManager;


    public function __construct($parameters, $backupLifecycleManager = null)
    {
        parent::__construct($parameters);
        $this->backupLifecycleManager = $backupLifecycleManager === null ? BackupLifecycleManager::getInstance() : $backupLifecycleManager;

    }

    protected function start()
    {
        return $this->backupLifecycleManager->handleUploadComplete($this->parameters['md5']);
    }

    public function isValid()
    {
        return isset($this->parameters['md5']) && preg_match('/^[a-f0-9]{32}$/', $this->parameters['md5']);
    }

}
