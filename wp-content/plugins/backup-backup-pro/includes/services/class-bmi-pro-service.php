<?php

namespace BMI\Plugin\Services;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\BMI_BackupMethodManager as MethodManager;
use BMI\Plugin\BMI_Ajax;
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\Scanner\BMI_FileScanner as Scanner;
use BMI\Plugin\Traits\LoggerTrait;

// Require necessary files
require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'class-backup-method-mananger.php';
require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'ajax.php';
require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'files.php';
require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';

class BackupService {

    use LoggerTrait;

    public $ajaxHandler;
    public $backupMethodManager;

    /**
     * BackupService constructor.
     */
    public function __construct() {
        $this->initAjaxHandler();
        $this->initBackupMethodManager();
    }

    /**
     * Initialize the BackupMethodManager.
     */
    private function initBackupMethodManager() {
        $this->backupMethodManager = new MethodManager();
    }

    /**
     * Initialize the AjaxHandler.
     * Temporary unset $_POST['f'] to avoid infinite loop when calling it from ajax premium
     */
    private function initAjaxHandler() {
        $fValue = isset($_POST['f']) ? $_POST['f'] : null;
        unset($_POST['f']);
        $this->ajaxHandler = new BMI_Ajax();
        $_POST['f'] = $fValue;
    }

    /**
     * Check if it is a valid time to perform a backup.
     *
     * @return bool
     */
    public function isValidTimeToBackup() {
        return !$this->isBackupRunning() && !$this->isCronRunning();
    }

    /**
     * Check if a backup is currently running.
     *
     * @return bool
     */
    private function isBackupRunning() {
        return $this->isFileRecent(BMI_BACKUPS . '/.running');
    }

    /**
     * Check if a cron job is currently running.
     *
     * @return bool
     */
    private function isCronRunning() {
        return $this->isFileRecent(BMI_BACKUPS . '/.cron');
    }

    /**
     * Check if a file is recent.
     *
     * @param string $filePath
     * @return bool
     */
    private function isFileRecent($filePath) {
        return file_exists($filePath) && (time() - filemtime($filePath)) <= 65;
    }

    /**
     * Perform a backup.
     *
     * @param string $type
     * @param mixed $shouldBackup
     * @return array 
     */
    public function performBackup($type = BMI_BACKUP_FULL, $shouldBackup = null) {
        if (!$this->isValidTimeToBackup()) {
            $this->log(__("Could not make the backup as another backup is already running.", 'backup-backup'));
            return ['status' => 'msg', 'why' => __('there is backup currently in progress. Please wait until it completes.', 'backup-backup'), 'level' => 'error', 'code' => 'CURRENTLY_RUNNING'];
        }
        try {

            /**
             * Before performing the backup.
             * 
             * Can be used to perform any action before the backup is made.
             * 
             * @since 1.4.7
             * 
             * @param BackupService $this The BackupService instance.
             * @param string $type The type of backup.
             * @param mixed $shouldBackup The paths to backup.
             * 
             */
            do_action('bmip_before_backup', $this, $type, $shouldBackup);

            $this->ajaxHandler->resetLatestLogs();
            $this->applyBackupFilters($type, $shouldBackup);
    
            $backup = $this->ajaxHandler->prepareAndMakeBackup();


            /**
             * After performing the backup.
             * 
             * Can be used to perform any action after the backup is made.
             * Note: triggering this action does not mean the backup has been finished.
             * if backup method is curl, browser then triggering this action mean the backup is started => for UrlBackup action
             * if backup method is default method then triggering this action mean the backup is finished => for BeforeUpdateBackup
             * 
             * @since 1.4.7
             * 
             * @param BackupService $this The BackupService instance.
             * @param string $type The type of backup.
             * @param mixed $shouldBackup The paths to backup.
             * @param array $backup The backup result.
             */
            do_action('bmip_after_backup', $this, $type, $shouldBackup, $backup);

            return $backup;
        } catch (\Exception $e) {
            $this->log($e->getMessage());
            return [
                'status' => 'msg',
                'why' => __("Could not make the backup due to internal server error.", 'backup-backup'),
                'level' => 'error',
            ];
        }

    }

    /**
     * Apply backup filters based on the type of backup.
     *
     * @param string $type
     * @param mixed $shouldBackup
     * @return bool
     */
    private function applyBackupFilters($type, $shouldBackup) {
        $backupFilesCallback = function() use($shouldBackup) { return $this->backupCustomFiles($shouldBackup); };
        $backupNameCallback = function($name) use($shouldBackup) { return $this->beforeUpdateBackupName($name, $shouldBackup); };
    
        switch ($type) {
            case BMI_BACKUP_FULL:
                // No filters to apply
                return true;
            case BMI_BACKUP_CORE:
                $this->addBackupFilters($backupFilesCallback, $backupNameCallback, '__return_true');
                return true;
            case BMI_BACKUP_PLUGIN:
            case BMI_BACKUP_THEME:
                $this->addBackupFilters($backupFilesCallback, $backupNameCallback, '__return_false');
                return true;
            default:
                return false;
        }
    }
    
    /**
     * Add backup filters.
     *
     * @param Closure $filesCallback 
     * @param Closure $nameCallback
     * @param string $databaseCallback backup database or not.
     */
    private function addBackupFilters($filesCallback, $nameCallback, $databaseCallback) {
        add_filter('bmip_backup_files', $filesCallback, 10, 0);
        add_filter('bmip_backup_name', $nameCallback);
        add_filter('bmip_database_backup', $databaseCallback);
    }    

    /**
     * Backup custom files.
     *
     * @param mixed $paths
     * @return array All file paths to backup.
     */
    public function backupCustomFiles($paths) {
        $files = [];
        $toBeIgnored = BMP::getDefaultDisabledPaths();
        if ($paths === ABSPATH) {
            $toBeIgnored[] = BMP::fixSlashes(WP_CONTENT_DIR);
            $paths = rtrim($paths, '/');
        }
        if (is_array($paths)) {
            foreach ($paths as $path) {
                $files = array_merge($files, Scanner::scanFilesGetNamesWithIgnore($path, $toBeIgnored));
            }
        } else {
            $files = Scanner::scanFilesGetNamesWithIgnore($paths, $toBeIgnored);
        }
        return $files;
    }

    /**
     * Get the backup name before an update.
     *
     * @param string $name
     * @param mixed $path
     * @return string The backup name.
     */
    public function beforeUpdateBackupName($name, $path) {
        $ext = '';
        if (preg_match('/\.tar\.gz$/', $name)) {
            $ext = 'tar.gz';
            $name = preg_replace('/\.tar\.gz$/', '', $name);
        } else {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $name = pathinfo($name, PATHINFO_FILENAME);
        }
        
        if ($path === ABSPATH) {
            return "{$name}_before_core_update.{$ext}";
        }
        $extra = is_array($path) ? implode('_', array_map('basename', $path)) : basename($path);
        return "{$name}_before_{$extra}_update.{$ext}";
    }

    /**
     * Activate the external ping server to start 10s polling for the active backup.
     */
    public static function activatePingServer() {
        if (\BMI\Plugin\Dashboard\bmi_get_config('CRON:REMOTE_PING') === true) {
            $domain = network_home_url();
            $sk = get_option('bmi_sk_keepalive');
            
            wp_remote_post('https://authentication.backupbliss.com/v2/crons/active-process', [
                'blocking' => false,
                'timeout' => 5,
                'body' => [
                    'site' => $domain,
                    'sk' => $sk
                ]
            ]);
        }
    }

    /**
     * Resumes the backup batch safely when triggered by KeepAlive Ping Server.
     */
    public function continueBackupBatch() {
        // Try early connection closing to prevent ping server timeouts (30s limits)
        if (ob_get_level()) @ob_end_clean();
        header('Content-Type: application/json');
        header('Connection: close');
        @ignore_user_abort(true);
        ob_start();
        echo json_encode(['status' => 'success']);
        $size = ob_get_length();
        header("Content-Length: $size");
        @ob_end_flush();
        @flush();
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        $GLOBALS['BMI::RESPONSE::SENT'] = true;

        // Load the bypasser with 'false' identity to load from currentBackupConfig.php
        require_once BMI_INCLUDES . '/backup-process.php';
        $request = new \BMI\Plugin\Heart\BMI_Backup_Heart(false, BMI_CONFIG_DIR, trailingslashit(WP_CONTENT_DIR), BMI_BACKUPS, trailingslashit(ABSPATH), plugin_dir_path(BMI_ROOT_FILE));
        
        if (sizeof($request->remote_settings) > 0) {
            $request->handle_batch();
        }

        // We already outputted the response and terminated the connection if possible.
        // If not, we just exit since we already echoed json earlier.
        exit;
    }
  


    /**
     * Get the debug code.
     *
     * @return string
     */
    public function getDebugCode($triggeredBy = 'backup') {
        $debugCode = null;
        $response = $this->ajaxHandler->sendTroubleshootingDetails('manual', $triggeredBy);
        if ($response['status'] == 'success'){
            $debugCode = $response['code'];
        }
        return $debugCode;    
    }
}
