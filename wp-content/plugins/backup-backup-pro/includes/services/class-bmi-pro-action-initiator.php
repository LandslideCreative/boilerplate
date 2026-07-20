<?php

namespace BMI\Plugin\Services;

if (!defined('ABSPATH')) exit;
use BMI\Plugin\Actions\BMI_Action;
use BMI\Plugin\Services\ActionFactory;
use BMI\Plugin\Traits\LoggerTrait;


require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';


/**
 * Class ActionInitiator
 * 
 * Handles the initiation and execution of various backup actions.
 */
class ActionInitiator {

    use LoggerTrait;

    private $actionName;
    private $actionParameters;
    private $siteUrl;
    private $secretKey;
    private $initiatorType;

    const INITIATOR_URL = 'INITIATOR_URL';
    const INITIATOR_DIRECT = 'INITIATOR_DIRECT';

    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_GET = 'GET';

    /**
     * ActionInitiator constructor.
     *
     * @param string $actionName The name of the action to be initiated.
     * @param array $actionParameters Parameters for the action.
     * @param string $secretKey Secret key for authentication.
     */
    public function __construct($actionName, $actionParameters, $secretKey) {
        $this->actionName = $actionName;
        $this->actionParameters = $actionParameters;
        $this->siteUrl = get_site_url();
        $this->secretKey = $secretKey;
    }

    /**
     * Execute the action based on the initiator type.
     * 
     * @param string $initiatorType Type of initiator (URL or DIRECT).
     * @param array $executionParams Additional parameters for execution.
     * @return mixed Result of the action execution.
     */
    public function execute($initiatorType, $executionParams = []) {
        $this->initiatorType = $initiatorType;

        if ($initiatorType === self::INITIATOR_URL) {
            return $this->executeUrlInitiator($executionParams);
        }

        if ($initiatorType === self::INITIATOR_DIRECT) {
            return $this->executeDirectInitiator();
        }

        return [
            'status' => 'msg',
            'why' => __('Invalid initiator type.', 'backup-migration'),
            'level' => 'error'
        ];
    }

    /**
     * Handle URL-based action initiation.
     * 
     * @param array $executionParams Parameters for URL-based execution.
     * @return array|\WP_Error Result of the HTTP request.
     */
    private function executeUrlInitiator($executionParams) {
        $httpMethod = isset($executionParams['method']) ? $executionParams['method'] : self::HTTP_METHOD_GET;
        $isAsync    = isset($executionParams['async']) ? $executionParams['async'] : true;
        $timeout    = isset($executionParams['timeout']) ? $executionParams['timeout'] : 5;

        $response = $this->sendHttpRequest($httpMethod, $isAsync, $timeout);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body;
    }

    /**
     * Handle direct action initiation.
     * 
     * @return array Result of the direct action execution.
     */
    private function executeDirectInitiator() {
        $action = $this->instantiateAction($this->actionName);

        if (!defined('BMI_BACKUP_PRO') || BMI_BACKUP_PRO !== 1) {
            $this->log("Couldn't execute action: {$this->actionName} - Backup Migration Pro is not active.");
            return [
                'status' => 'msg',
                'why' => __('Please upgrade to Backup Migration Pro to use this feature.', 'backup-migration'),
                'level' => 'error'
            ];
        }
        
        if ($action === null) {
            return [
                'status' => 'msg',
                'why' => __('The action is not allowed.', 'backup-migration'),
                'level' => 'error'
            ];
        }

        if ($action->isValid()) {
            return $this->performAction($action);
        } else {
            return [
                'status' => 'msg',
                'why' => __('Another backup process is currently running. Please try again later.', 'backup-migration'),
                'level' => 'error'
            ];
        }
    }

    /**
     * Perform the instantiated action.
     * 
     * @param BMI_Action $action The action object to be executed.
     * @return mixed Result of the action execution.
     */
    private function performAction($action) {
        $action->execute();
        return $action->getResult();
    }

    /**
     * Instantiate an action based on the action name.
     * 
     * @param string $actionName Name of the action to instantiate.
     * @return BMI_Action|null The instantiated action object or null if not allowed.
     */
    public function instantiateAction($actionName) {
        $sanitizedParameters = $this->sanitizeParameters(
            $this->initiatorType === self::INITIATOR_URL ? $_REQUEST : $this->actionParameters
        );

        require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-factory.php';
        return ActionFactory::create($actionName, $sanitizedParameters);
    }

    /**
     * Send an HTTP request to initiate the action.
     * 
     * @param string $httpMethod HTTP method to use (GET or POST).
     * @param bool $isAsync Whether the request should be asynchronous.
     * @param int $timeout Request timeout in seconds.
     * @return array|\WP_Error Result of the HTTP request.
     */
    public function sendHttpRequest($httpMethod = self::HTTP_METHOD_POST, $isAsync = true, $timeout = 5) {
        $requestParameters = http_build_query(array_merge(
            [
                'backup-migration' => $this->actionName,
                'sk' => $this->secretKey,
            ],
            $this->actionParameters
        ));

        $url = $this->siteUrl . '?' . $requestParameters;

        $requestOptions = [
            'timeout' => $timeout,
            'blocking' => $isAsync,
            'sslverify' => false,
        ];

        return $httpMethod === self::HTTP_METHOD_GET
            ? wp_remote_get($url, $requestOptions)
            : wp_remote_post($url, $requestOptions);
    }

    /**
     * Sanitize the request parameters.
     * 
     * @param array $parameters Parameters to sanitize.
     * @return array Sanitized parameters.
     */
    private function sanitizeParameters($parameters) {
        $sanitized = [];
        foreach ($parameters as $key => $value) {
            $sanitized[sanitize_text_field($key)] = is_array($value) 
                ? $this->sanitizeParameters($value) 
                : sanitize_text_field($value);
        }
        return $sanitized;
    }
}

