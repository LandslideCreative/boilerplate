<?php

namespace BMI\Plugin\Actions;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\Interfaces\Action;
use BMI\Plugin\Traits\LoggerTrait;

// require files
require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'action.php';
require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';

abstract class BMI_Action implements Action {

	use LoggerTrait;

	protected $parameters;
	protected $result;


	public function __construct($parameters = [])
	{
		$this->parameters = $parameters;

	}
	
	public function validateError() {
        $this->respondError();
    }

	public function respondError() {
		$this->log('Invalid request');

		echo json_encode([
			'status' => 'msg',
			'why' => 'Invalid request',
			'level' => 'error'
		]);
		exit;
	}

	public function respondSuccess() {
		echo json_encode(['status' => 'success']);
		exit;
	}

	/**
	 * Get the result of the action.
	 * 
	 * @return mixed
	 */
    public function getResult() {
        return $this->result;
    }

	/**
	 * Execute the action.
	 * 
	 * @return bool
	 */
	public function execute(){

		do_action('bmi_action_before_execute', $this);

		$this->result = $this->start();

		do_action('bmi_action_after_execute', $this);

		if ($this->result) {
			return true;
		} else {
			return false;
		}
		
	}

	abstract protected function start();
    abstract public function isValid();

}