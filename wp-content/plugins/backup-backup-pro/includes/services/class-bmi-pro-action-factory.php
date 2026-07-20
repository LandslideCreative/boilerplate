<?php

namespace BMI\Plugin\Services;

use BMI\Plugin\Actions\UrlBackup;
use BMI\Plugin\Actions\URLBackupRequest;
use BMI\Plugin\Actions\BeforeUpdateBackup;
use BMI\Plugin\Actions\ApplyStorageStrategy;
use BMI\Plugin\Actions\HandleBackupUploadedComplete;
use BMI\Plugin\Services\BackupService;
use BMI\Plugin\BMI_BackupMethodManager as MethodManager;

if (!defined('ABSPATH'))
    exit;

class ActionFactory
{

    private static $registry = [];
    private static $initialized = false;

    /**
     * Exhaustive list of action names that may be instantiated from external input.
     * Any name absent from this list is rejected by create() before the registry
     * is even consulted, providing defence-in-depth against registry injection.
     */
    private const ALLOWED_ACTIONS = [
        'BACKUP_BY_URL',
        'BACKUP_BY_URL_REQUEST',
        'BEFORE_UPDATE_BACKUP',
        'HANDLE_BACKUP_UPLOADED_COMPLETE',
        'APPLY_STORAGE_STRATEGY',
    ];

    /**
     * Initialize default actions.
     */
    private static function initialize()
    {
        if (self::$initialized)
            return;

        self::register('BACKUP_BY_URL', function ($parameters) {
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action-url-backup.php';
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-service.php';
            return new UrlBackup($parameters, new BackupService());
        });

        self::register('BACKUP_BY_URL_REQUEST', function ($parameters) {
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action-url-backup-request.php';
            require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'class-backup-method-mananger.php';
            return new URLBackupRequest($parameters, new MethodManager());
        });

        self::register('BEFORE_UPDATE_BACKUP', function ($parameters) {
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action-before-update-backup.php';
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-service.php';
            return new BeforeUpdateBackup($parameters, new BackupService());
        });

        self::register('HANDLE_BACKUP_UPLOADED_COMPLETE', function ($parameters) {
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action-handle-backup-uploaded-complete.php';
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-backup-lifecycle-manager.php';
            return new HandleBackupUploadedComplete($parameters, \BMI\Plugin\Services\BackupLifecycleManager::getInstance());
        });

        self::register('APPLY_STORAGE_STRATEGY', function ($parameters) {
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . 'class-bmi-action-apply-storage-strategy.php';
            require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-backup-lifecycle-manager.php';
            require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
            return new ApplyStorageStrategy($parameters, \BMI\Plugin\Services\BackupLifecycleManager::getInstance(), new \BMI\Plugin\Scanner\BMI_BackupsScanner());
        });

        self::$initialized = true;
    }

    /**
     * Register a new action creator.
     *
     * @param string $actionName
     * @param callable $creatorCallback Function that accepts $parameters and returns an Action instance.
     */
    private static function register($actionName, $creatorCallback)
    {
        self::$registry[$actionName] = $creatorCallback;
    }

    /**
     * Create an action instance.
     *
     * @param string $actionName
     * @param array $parameters
     * @return mixed|null
     */
    public static function create($actionName, $parameters)
    {
        if (!in_array($actionName, self::ALLOWED_ACTIONS, true)) {
            return null;
        }

        self::initialize();

        if (isset(self::$registry[$actionName])) {
            return call_user_func(self::$registry[$actionName], $parameters);
        }

        return null;
    }

    /**
     * Check if an action is registered.
     *
     * @param string $actionName
     * @return bool
     */
    public static function isRegistered($actionName)
    {
        self::initialize();
        return isset(self::$registry[$actionName]);
    }
}
