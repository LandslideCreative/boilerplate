<?php

namespace BMI\Plugin;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\Dashboard;
use BMI\Plugin\Services\ActionInitiator;
use BMI\Plugin\Traits\LoggerTrait;


require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';

class Before_Update_Controller {
    use LoggerTrait;

    /**
     * Completed backups array.
     * indicates if the item has been backed up or not to avoid multiple backups when run postponded auto update event.
     * TODO: 
     *   - should be stored in json file to avoid delete / overwrite on site restore.
     *   - should verify the backup file exists to avoid false positives.
     * 
     * @var array
     */
    private $completedBackups = [];

    /**
     * Lock name for the postponed auto update event.
     * 
     * @var string
     */
    private $lockName = 'auto_updater.lock';

    public function __construct() {
        add_action('admin_notices', [$this, 'registerAutoBackupConfirm']);
        add_action('admin_footer', [$this, 'registerRequiredBackupModals']);
        add_action('pre_auto_update', [$this, 'handlePreAutoUpdate'], 10, 2);
        add_action('bmi_perform_auto_update', [$this, 'executePostponedUpdate']);
    }

    /**
     * Display a confirmation message to the user before updating.
     */
    public function registerAutoBackupConfirm() {
        $allowed_pages = [
            'update-core',
            'plugins',
            'themes',
            'themes-network',
            'plugins-network',
        ];
    
        if (!function_exists('get_current_screen')) {
            return;
        }

        if (!$this->isAutoBackupEnabled()) {
            return;
        }

        $current_page = get_current_screen()->id;
    
        if (in_array($current_page, $allowed_pages)) {
            require_once BMI_INCLUDES . '/dashboard/translations.php';
        }
    }

    /**
     * Register Backup Progress Modal In Core Update Page.
     * 
     */
    public function registerRequiredBackupModals() {

        if (!function_exists('get_current_screen')) {
            return;
        }

        $current_page = get_current_screen()->id;
        $allowed_pages = [
            'update-core',
            'plugins',
            'themes',
            'themes-network',
            'plugins-network',
        ];


        if (!in_array($current_page, $allowed_pages)) {
            return;
        }

        ?>
        <!-- Required for styling -->
        <div id="bmi"> 
        <?php
            require_once BMI_INCLUDES . '/dashboard/modals/backup-progress-modal.php';
            require_once BMI_INCLUDES . '/dashboard/modals/error-modal.php';
            require_once BMI_INCLUDES . '/dashboard/modals/before-update-confirm-modal.php';
            require_once BMI_INCLUDES . '/dashboard/modals/after-logs-sent.php';
            require_once BMI_INCLUDES . '/dashboard/modals/logs-modal.php';
            require_once BMI_INCLUDES . '/dashboard/modals/freeze-loading.php';
            require_once BMI_INCLUDES . '/dashboard/translations.php'; // Required for js functions e.g. #BMI_BLOG_URL
        ?>
        </div>
        <?php
    }

    /**
     * Handle the pre-auto-update event.
     * Supported types: plugin, theme, core.
     * else log the unsupported type (e.g. translation).
     * @param string $type
     * @param object $item
     * @return void
     */
    public function handlePreAutoUpdate($type, $item) {
        if (in_array($type, ['plugin', 'theme', 'core'])) {
            $updateInfo = [
                'type' => $type,
                'item' => $type === 'core' ? ABSPATH : $item->{$type},
                'full' => $item,
            ];
            $this->performPreUpdateBackup($updateInfo);
        } else {
            $this->log('Pre-auto-update triggered for unsupported type: ' . $type);
        }
    }

    /**
     * Perform the pre-update backup.
     * 
     * @param array $updateInfo
     * @param string $updateInfo['type'] Type of the update (plugin, theme, core).
     * @param string $updateInfo['item'] Item to be updated. (plugin name, theme name, ABSPATH for core).
     * @param object $updateInfo['full'] Full update info. 
     * @return bool
     */
    public function performPreUpdateBackup($updateInfo) {
        if ($this->isBackupRequired($updateInfo)) {

            $this->postponeUpdate(180); // Postpone update by 3 minutes

            $backupResult = $this->initiateBackup($updateInfo);

            if (isset($backupResult['level']) && $backupResult['level'] === 'error' && isset($backupResult['code']) && $backupResult['code'] === 'CURRENTLY_RUNNING') {
                $this->postponeUpdate(60); // Postpone update by 1 minute if backup is currently running 
                return false; // Do not mark as backed up
            }

            if (isset($backupResult['status']) && $backupResult['status'] === 'success') {
                if (isset($backupResult['filename'])){
                    $updateInfo['backup'] = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupResult['filename'];
                } else {
                    $updateInfo['backup'] = null;
                }
                $this->markAsBackedUp($updateInfo);
            }
        }

        // Allow the update to proceed
        return true;
    }

    /**
     * Check if a backup is required before updating.
     * 
     * @param array $updateInfo
     * @return bool
     */
    private function isBackupRequired($updateInfo) {
        if ($this->isAutoBackupEnabled() === false) {
            return false;
        }

        $type = $updateInfo['type'];
        $item = $updateInfo['full'];

        switch ($type) {
            case 'plugin':
                $identifier = $item->plugin;
                $version = $item->new_version;
                break;
            case 'theme':
                $identifier = $item->theme;
                $version = $item->new_version;
                break;
            case 'core':
                $identifier = 'core';
                $version = $item->version;
                break;
            default:
                return false; // Don't backup unknown types
        }

        $this->completedBackups = get_option('bmi_completed_backups', []);
        if (isset($this->completedBackups[$type][$identifier])) {
            $backedUpVersion = $this->completedBackups[$type][$identifier]['version'];
            if ($backedUpVersion === $version) {
                $backupExists = file_exists($this->completedBackups[$type][$identifier]['backup']);
                if ($backupExists) {
                    return false;
                } else {
                    unset($this->completedBackups[$type][$identifier]);
                }
            }
        }

        return true;
    }

    private function postponeUpdate($seconds) {
        wp_unschedule_hook('bmi_perform_auto_update');
        if ($seconds > 0) {
            $this->log("Rescheduling update for {$seconds} seconds ahead");
            $lockValue = $this->getLockValue();
            wp_schedule_single_event(time() + $seconds, 'bmi_perform_auto_update', [$lockValue]);
        }
    }

    private function initiateBackup($updateInfo) {
        $this->log("Initiating auto backup for {$updateInfo['item']}");
        return $this->fireBeforeUpdateAction($updateInfo);
    }

    /**
     * Fire the before update action. 
     * Sends an HTTP request to the action initiator to start the backup.
     * @param mixed $parameters
     * @return mixed
     */
    public function fireBeforeUpdateAction($parameters) {
        require_once BMI_PRO_INC . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'class-bmi-pro-action-initiator.php';
        unset($parameters['full']);
        $action = new ActionInitiator('BEFORE_UPDATE_BACKUP', $parameters, Dashboard\bmi_get_config('REQUEST:SECRET'));
                
        $response = $action->execute(ActionInitiator::INITIATOR_URL, [
            'method' => ActionInitiator::HTTP_METHOD_GET,
            'async' => true,
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        if (isset($response['level']) && $response['level'] === 'error') {
            return false;
        }

        return $response;
    }

    private function markAsBackedUp($updateInfo) {
        $type = $updateInfo['type'];
        $item = $updateInfo['full'];

        switch ($type) {
            case 'plugin':
                $identifier = $item->plugin;
                $version = $item->new_version;
                break;
            case 'theme':
                $identifier = $item->theme;
                $version = $item->new_version;
                break;
            case 'core':
                $identifier = 'core';
                $version = $item->version;
                break;
            default:
                return; // Don't mark unknown types
        }

        if (!isset($this->completedBackups[$type])) {
            $this->completedBackups[$type] = [];
        }
        $this->completedBackups[$type][$identifier] = [
            'version' => $version,
            'backup' => $updateInfo['backup'],
        ];
        update_option('bmi_completed_backups', $this->completedBackups);
    }

    /**
     * Execute the postponed auto update.
     * @param mixed $lockValue
     * @return void
     */
    public function executePostponedUpdate($lockValue) {
        if ($this->verifyLock($lockValue)) {
            $this->clearLock();
            wp_maybe_auto_update();
        }
    }

    private function isAutoBackupEnabled() {
        return Dashboard\bmi_get_config('OTHER:TRIGGER:BEFORE:UPDATES') === 'true' || Dashboard\bmi_get_config('OTHER:TRIGGER:BEFORE:UPDATES') === true;
    }

    private function getLockValue() {
        $lockValue = wp_generate_password(20, false);
        update_option($this->lockName, $lockValue);
        return $lockValue;
    }

    private function verifyLock($lockValue) {
        return get_option($this->lockName) === $lockValue;
    }

    private function clearLock() {
        delete_option($this->lockName);
    }

    /**
     * Function get_asset which resolves dependency injection when requiring the backup-progress-model. (TEMP FIX)
     * 
     * @param string $base
     * @param string $asset
     */
    private function get_asset($base = '', $asset = '') {
        return BMI_ASSETS . '/' . $base . '/' . $asset;
    }

}

new Before_Update_Controller();
