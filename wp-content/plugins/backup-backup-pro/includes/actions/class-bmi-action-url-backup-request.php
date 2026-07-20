<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\Services\ActionInitiator;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\BMI_BackupMethodManager as MethodManager;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action.php';
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-initiator.php';




class URLBackupRequest extends BMI_Action {
    private $bmiMethodManager;
    private $secretKey;



    public function __construct($parameters, $bmiMethodManager) {
		parent::__construct($parameters);
        $this->bmiMethodManager = $bmiMethodManager;
        $this->secretKey = Dashboard\bmi_get_config('BACKUP:URL:TRIGGER:KEY');
    }


	protected function start() {
		$this->checkBackupMethod();
	
		$this->log('Backup by URL request started');
		$actionParameters = $this->prepareActionParameters();
		$response = $this->initiateBackupAction($actionParameters);
	
		return $this->handleResponse($response);
	}
	
	private function checkBackupMethod() {
		if ($this->bmiMethodManager->currentMethod() != BMI_METHOD_CURL){
			if ($this->bmiMethodManager->isMethodChangable(BMI_METHOD_CURL)) {
				define('CHANGED_TO_CURL_METHOD', true);
			} else {
				define('CURL_METHOD_NOT_WORKING', true);
			}
		}
	}
	
	private function prepareActionParameters() {
		$parameters = $this->parameters;
		unset($parameters['backup-migration']);
		return $parameters;
	}
	
	private function initiateBackupAction($actionParameters) {
		$actionInitiator = new ActionInitiator(
			'BACKUP_BY_URL',
			$actionParameters,
			Dashboard\bmi_get_config('REQUEST:SECRET')
		);
		return $actionInitiator->execute(
			ActionInitiator::INITIATOR_URL,
			[
				'method' => ActionInitiator::HTTP_METHOD_GET,
				'async' => false,
			]
		);
	}
	
	private function handleResponse($response) {
		if (is_wp_error($response)) {
			$this->log('Failed to send request to the server');
			define('REQUEST_FAILED', true);
			return false;
		}
		return true;
	}

    public function isValid() {
		$userSecretKey = isset($this->parameters['uk']) ? $this->parameters['uk'] : null;
		$isRunning = file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65;
		$isCron = file_exists(BMI_BACKUPS . '/.cron') && (time() - filemtime(BMI_BACKUPS . '/.cron')) <= 65;
		if (false === defined('ALREADY_RUNNING_MESSAGE') && ($isRunning || $isCron)) define('ALREADY_RUNNING_MESSAGE', true);
		if (false === defined('INVALID_REQUEST') && $userSecretKey != $this->secretKey) define('INVALID_REQUEST', true);
		return $userSecretKey == $this->secretKey && !$isRunning && !$isCron;
    }

	public function respondSuccess() {
		require_once BMI_PRO_INC  . 'pages/backup-by-url.php';
		exit;
	}

	public function respondError() {
		require_once BMI_PRO_INC  . 'pages/backup-by-url.php';
		exit;
	}

	public function validateError()
	{
		require_once BMI_PRO_INC  . 'pages/backup-by-url.php';
		exit;
	}

}
