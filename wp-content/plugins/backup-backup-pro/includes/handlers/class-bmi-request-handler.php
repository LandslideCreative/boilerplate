<?php

namespace BMI\Plugin\Handlers;

use BMI\Plugin\Traits\LoggerTrait;
use BMI\Plugin\Actions\BMI_Action;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Services\ActionFactory;
use Exception;



class RequestHandler {


    protected $action;
    protected $instance;
    protected $bmiParam;
    protected $parameters;
    protected $secretKey;

    const ACTION_PARAM = 'backup-migration';

    public static function init() {
        if ( self::$instance === null ) 
            self::$instance = new self;

        return self::$instance;

    }

    public function __construct() {
        if (get_option('bmip_last') != '1' ||  !defined('BMI_BACKUP_PRO') || !BMI_BACKUP_PRO) return;

        $this->bmiParam = isset($_REQUEST[self::ACTION_PARAM]) ? sanitize_text_field($_REQUEST[self::ACTION_PARAM]) : null;
        // No need to continue if bmiParam is not set
        if ($this->bmiParam === null) {
            return;
        }
        add_action('wp_loaded', [$this, 'handleRequest']);
    }

    public function handleRequest() {
        if ($this->bmiParam === null) {
            return;
        }

        $this->secretKey = Dashboard\bmi_get_config('REQUEST:SECRET');
        $this->action = $this->instantiateAction($this->bmiParam);

        $this->respond();
    }

    /**
     * Instantiate an action based on the action name.
     * 
     * @param string $actionName Name of the action to instantiate.
     * @return BMI_Action|null The instantiated action object or null if not allowed.
     */
    public function instantiateAction($actionName) {
        $this->parameters = $this->sanitizeParameters($_REQUEST);
        require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-factory.php';
        return ActionFactory::create($actionName, $this->parameters);
    }

    /**
     * Sanitize the request parameters.
     *
     * @param array $request
     * @return array
     */
    private function sanitizeParameters($request) {
        $sanitized = [];
        foreach ($request as $key => $value) {
            $sanitized[sanitize_text_field($key)] = is_array($value) 
                ? $this->sanitizeParameters($value) 
                : sanitize_text_field($value);
        }
        return $sanitized;
    }

    /**
     * Respond to the request based on the action's execution result.
     * 
     * @return void so if the action is not valid, let wordpress handle the request and don't respond.
     * 
     */
    public function respond() {
        if ($this->action === null) {
            return;
        }

        if (!$this->isValid()) {
            $this->respondError('Invalid request');
            return;
        }

        try {
            $this->processAction();
        } catch (Exception $e) {
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Process the action execution and respond accordingly.
     */
    private function processAction() {
        if ($this->action->isValid() && $this->action->execute()) {
            $this->action->respondSuccess();
        } else {
            $this->action->respondError();
        }
    }

    /**
     * Send a success response.
     */
    private function respondSuccess() {
        echo json_encode(['status' => 'success']);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     */
    private function respondError($message) {
        echo json_encode([
            'status' => 'msg',
            'why' => $message,
            'level' => 'error'
        ]);
        exit;
    }

    /**
     * Convert the handler to an array.
     *
     * @return array
     */
    public function toArray() {
        return $this->parameters;
    }

    /**
     * Get a parameter by name.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getParameter($name) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Check if the request is valid.
     *
     * @return bool
     */
    public function isValid() {
        $secretKey = $this->getParameter('sk');
        return $this->action !== null && $secretKey !== null && $this->secretKey === $secretKey;
    }
}
