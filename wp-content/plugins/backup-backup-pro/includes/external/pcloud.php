<?php

namespace BMI\Plugin\External;


// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'pcloud-sdk.php';

use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;

/**
 * BMI_External_PCloud
 * 
 * This class is responsible for handling all pCloud related operations.
 * Delegates API calls to BMI_PCloud_SDK.
 */

class BMI_External_PCloud
{
    public $id = 'bmip_pcloud';
    public $authCodeOption = 'bmip_pcloud_auth_code';
    public $accessToken = 'bmip_pcloud_access_token';
    public $dirName;

    /** @var BMI_PCloud_SDK|null */
    private $sdk = null;

    public function __construct()
    {
        add_action('bmi_premium_remove_backup_file', [&$this, 'deleteBackup']);
        add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteBackupJson']);

        add_action('set_transient_bmip_pcloud_issue', [&$this, 'setPCloudIssue']);
        add_action('delete_transient_bmip_pcloud_issue', [&$this, 'deletePCloudIssue']);


    }

    public function deletePCloudIssue()
    {
        delete_option('bmip_pcloud_correct_offset');
        delete_option('bmip_pcloud_required_space');
        delete_option('bmip_pcloud_dismiss_issue');
    }

    public function setPCloudIssue()
    {
        update_option('bmip_pcloud_dismiss_issue', false);
    }

    /**
     * Get or initialize the pCloud SDK instance
     * 
     * @return BMI_PCloud_SDK|false SDK instance or false if no access token
     */
    private function getSDK()
    {
        if ($this->sdk !== null) {
            return $this->sdk;
        }

        $accessToken = get_option($this->accessToken);

        if (!$accessToken) {
            $accessToken = $this->configureAccessToken();
        }

        if (!$accessToken) {
            return false;
        }

        $apiUrl = get_option('bmip_pcloud_hostname', 'https://api.pcloud.com');
        $this->sdk = new BMI_PCloud_SDK($accessToken, $apiUrl);

        return $this->sdk;
    }

    /**
     * Reset the SDK instance (e.g. after token refresh)
     * 
     * @return void
     */
    private function resetSDK()
    {
        $this->sdk = null;
    }

    /**
     * getFileMeta get the file id of a file in pCloud
     * @param string $fileName file id of the file in pCloud
     * @return false|array false if error, array of metadata if success
     */
    public function getFileMeta($fileName)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->getFileMeta($fileName);
    }

    /**
     * deleteFile delete a file in pCloud 
     * @param string $fileName name of file or file id of the file in pCloud
     * @return bool true if success, false if error
     */
    public function deleteFile($fileName)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        $result = $sdk->deleteFile($fileName);

        if (!$result) {
            $this->handleApiError('deletefile');
        }

        return $result;
    }

    /**
     * getFileLink get the download link of a file in pCloud
     * @param string $fileName file id of the file in pCloud
     * @return false|string false if error, download URL if success
     */
    public function getFileLink($fileName)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->getFileLink($fileName);
    }

    /**
     * getFileContent get the content of a file in pCloud
     * @param string $fileName file id of the file in pCloud
     * @param string $range range of the file to download (optional) e.g. '0-100' for first 100 bytes
     * @return false|string false if error, string response if success (content of the file)
     */
    public function getFileContent($fileName, $range = '')
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->getFileContent($fileName, $range);
    }

    /**
     * listFiles list all files in root folder in pCloud
     * @return false|array[] false if error, array of entries if success
     */
    public function listFiles()
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->listFiles();
    }

    /**
     * startUploadSession start an upload session in pCloud
     * 
     * @return false|int false if error, upload id if success
     */
    public function startUploadSession()
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->startUploadSession();
    }

    /**
     * uploadChunk upload a chunk of a file to pCloud using upload session 
     * 
     * @param int $sessionId valid upload id
     * @param string $filePath full path of the file
     * @param int $offset offset of the file
     * @param int $maxRetries maximum number of retries
     * @return false|int false if error, new offset if success
     */
    public function uploadChunk($sessionId, $filePath, $offset, $maxRetries = 3)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        $result = $sdk->uploadChunk($sessionId, $filePath, $offset, $maxRetries);

        if ($result === false) {
            $issue = get_transient('bmip_pcloud_issue');
            if ($issue == 'incorrect_offset') {
                $correctOffset = get_option('bmip_pcloud_correct_offset', false);
                if ($correctOffset) {
                    delete_option('bmip_pcloud_correct_offset');
                    return $sdk->uploadChunk($sessionId, $filePath, $correctOffset, $maxRetries);
                }
            }
        }

        return $result;
    }

    /**
     * finishUpload finish the upload session of a file in pCloud
     * 
     * @param int $sessionId valid upload id
     * @param string $filePathOnPCloud name/path of the file
     * @param int $folderId id of the folder in pCloud
     * @return false|int false if error, file id if success
     */
    public function finishUpload($sessionId, $filePathOnPCloud, $folderId = 0)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->finishUpload($sessionId, $filePathOnPCloud, $folderId);
    }

    /**
     * uploadFile upload a file to pCloud
     * 
     * @param string $filePath full path of the file
     * @return false|int false if error, file id if success
     */
    public function uploadFile($filePath)
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->uploadFile($filePath);
    }

    /**
     * Handle pCloud API errors and set appropriate transients
     * 
     * @param string $context The API call context for logging
     * @return void
     */
    private function handleApiError($context = '')
    {
        Logger::log('[BMI PRO] pCloud API Error during: ' . $context);
        update_option('bmip_pcloud_dismiss_issue', false);
    }

    /**
     * getParsedFiles get the list of files in pCloud folder and their metadata (JSON files)
     * 
     * @return array[]|bool compact array of zip files and json files if success, false if error
     *                  format: ['zipFilesName' => ['filename.zip' => ['id' => 'file_id', 'size' => 'file_size']], 'jsonFilesPath' => ['path_lower']]
     */ 
    public function getParsedFiles()
    {
        $files = $this->listFiles();
        if ($files === false) return false;
        $zipFilesName = [];
        $jsonFilesPath = [];
        foreach ($files as $file) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (in_array($ext, ['zip', 'tar', 'gz'])) {
                $zipFilesName[$file['name']] = [
                    'id' => $file['fileid'],
                    'size' => $file['size']
                ];
            } else if (strpos($file['name'], '.json') !== false) {
                $jsonFilesPath[$file['path']] = $file['fileid'];
            }

        }
        return compact('zipFilesName', 'jsonFilesPath');
    }

    /**
     * getSpaceUsage get the space usage of pCloud account
     * 
     * @return false|array false if error, array of space usage if success
     */
    public function getSpaceUsage()
    {
        $sdk = $this->getSDK();
        if (!$sdk) return false;

        return $sdk->getSpaceUsage();
    }


    /************************************************************************************************************* */
    /*********************  DELETE PCLOUD BACKUP  **************************************************************** */
    /************************************************************************************************************* */
    public function deleteBackup($md5){

        $manifestFile = $md5;
        if (strpos($md5, '.json') === false) {
            $manifestFile = $md5 . '.json';
        }

        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile)) {
            $manifestContent = json_decode(file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile), true);
        } else {
            $manifestContent = json_decode($this->getFileContent('/' . $manifestFile), true);
        }
        $backupName = $manifestContent['name'];
        $deleteManifest = $this->deleteFile($manifestFile);
        $deleteZip = $this->deleteFile($backupName);
        if ($deleteManifest && $deleteZip) {
            return true;
        }
        return false;    
    }

    public function deleteBackupJson($manifestFile){
        $deleteManifest = $this->deleteFile('/' . $manifestFile);
        if ($deleteManifest) {
            return true;
        }
        return false;
    }



    /************************************************************************************************************* */
    /*********************  pCloud Plugin Functions  ************************************************************ */
    /************************************************************************************************************* */



    /**
     * uploadBackup - Uploads a backup to pCloud
     * @param string $sessionId - session id of the upload
     * @param string $backupName - name of the backup to upload
     * @param int $offset - offset of the file to upload
     * @param string $md5 - md5 hash of the backup to get the manifest file
     * @return array explain the status of the upload process in format
     *    [
     *      'status' => 'finished' | 'error' | 'continue',
     *      (status == 'continue') ? 'offset' => int : null,
     *      (status == 'error') ? 'step' => 'start' | 'chunk' | 'finish' | 'manifest' | 'start_upload_in_one_go' : null,
     *      (status == 'error') ? 'error' => 'internal_file_not_found'
     *    ]
     */
    public function uploadBackup($sessionId, $backupName, $offset, $md5)
    {
        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
        $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';
        $spaceUsage = $this->getSpaceUsage();
        $availableSpace = $spaceUsage['quota'] - $spaceUsage['usedquota'];

        if (!file_exists($backupPath) || !file_exists($manifestPath)) {
            Logger::error('[BMI PRO] Backup file or manifest file not found for backup: ' . $backupName);
            return [
              'status' => 'error',
              'error' => 'internal_file_not_found'
            ];
        }

        if ($availableSpace < filesize($backupPath)) {
            Logger::error('[BMI PRO] Not enough space to upload file: ' . $backupName);
            update_option('bmip_pcloud_required_space', filesize($backupPath));
            set_transient('bmip_pcloud_issue', 'insufficient_space', HOUR_IN_SECONDS);
            return [
              'status' => 'error',
              'error' => 'insufficient_space'
            ];
        }

        if ($sessionId == '') {
            $sessionId = $this->startUploadSession();
            if (!$sessionId) {
                return [
                  'status' => 'error',
                  'error' => 'start'
                ];
            }
            return [
              'status' => 'continue',
              'offset' => 0,
              'sessionId' => $sessionId
            ];
        } else {
            $availableMemory = BMP::getAvailableMemoryInBytes();
            if ($availableMemory / 4 > filesize($backupPath)) {
                $uploadResult = $this->uploadFile($backupPath);
                if ($uploadResult) {
                    $manifestUploadResult = $this->uploadFile($manifestPath);
                    if ($manifestUploadResult) return ['status' => 'success'];
                    else return ['status' => 'error', 'error' => 'manifest'];
                } else {
                    return ['status' => 'error', 'error' => 'start_upload_in_one_go'];
                }
            }
            if ($offset < filesize($backupPath)){
              $newOffset = $this->uploadChunk($sessionId, $backupPath, $offset);
              if ($newOffset === false) {
                  return [
                    'status' => 'error',
                    'error' => 'chunk'
                  ];
              }
              return [
                'status' => 'continue',
                'offset' => $newOffset
              ];
            } else {
              $fileId = $this->finishUpload($sessionId, $backupPath);
              if ($fileId) {
                  $manifestUploadResult = $this->uploadFile($manifestPath);
                  if ($manifestUploadResult) return ['status' => 'success'];
                  else return ['status' => 'error', 'error' => 'manifest'];
              } else {
                  return ['status' => 'error', 'error' => 'finish'];
              }
            }

        }

    }

    /**
     * checkForBackupsToUpload - Checks for backups to upload to pCloud
     * update bmip_to_be_uploaded option with the list of backups to upload
     * 
     * @return array explain the status of the upload process in format
     *    [
     *      'status' => 'success'
     *    ]
     */
    public function checkForBackupsToUpload() {

        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::PCLOUD');
        if (!($isEnabled === true || $isEnabled === 'true')) {
          update_option('bmip_to_be_uploaded', [ 'current_upload' => [], 'queue' => [] ]);
          return ['status' => 'not_enabled'];
        }
    
        $requiresUpload = get_option('bmip_to_be_uploaded', [
          'current_upload' => [],
          'queue' => [],
          'failed' => []
        ]);
    
        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
        $backups = new Backups();
        $backupsAvailable = $backups->getAvailableBackups();
        $localBackups = $backupsAvailable['local'];
        $parsedPCloudFiles = $this->getParsedFiles();
        if($parsedPCloudFiles === false) return ['status' => 'error'];
        $backupsFileName = isset($parsedPCloudFiles['zipFilesName']) ? $parsedPCloudFiles['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedPCloudFiles['jsonFilesPath']) ? $parsedPCloudFiles['jsonFilesPath'] : [];
        $availableManifests = array_map(function($path) {
          return pathinfo($path, PATHINFO_FILENAME);
        }, array_keys($manifestFilesPath));
        $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

   
        
        foreach($localBackups as $name => $details) {
          $md5 = $details[7];
          if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['pcloud'])) {
            continue;
          }

          $isBackupNotExists = !in_array($md5, $availableManifests) || !in_array($name, array_keys($backupsFileName));
          if ($isBackupNotExists && !(isset($requiresUpload['current_upload']['task']) && $requiresUpload['current_upload']['task'] == 'pcloud_' . $md5)) {
            $requiresUpload['queue']['pcloud_' . $md5] = [
                'name' => $name,
                'md5'  => $details[7],
                'json' => $details[7] . '.json'
            ];
          }
        }
  
        update_option('bmip_to_be_uploaded', $requiresUpload);
        return ['status' => 'success'];
      }
  
    /************************************************************************************************************* */
    /*********************  pCloud Authorization Functions  ***************************************************** */
    /************************************************************************************************************* */

    /**
     * configureAccessToken - Configures the access token for pCloud
     * 
     * @return string|bool access token if success, false if error
     */
    public function configureAccessToken()
    {
        $uri = home_url();
        if (substr($uri, 0, 4) != 'http') {
          if (is_ssl()) $uri = 'https://' . home_url();
          else $uri = 'http://' . home_url();
        }
        

        $authorizationCode = get_option($this->authCodeOption, '');
        $pcloudId = get_option($this->id, '');
    
        $url = 'https://authentication.backupbliss.com/v1/pcloud/token';
        $response = wp_remote_post($url, array(
          'method' => 'POST',
          'timeout' => 15,
          'redirection' => 2,
          'httpversion' => '1.0',
          'blocking' => true,
          'body' => array(
            'client_id' => $authorizationCode,
            'site_token' => $pcloudId,
            'redirect_uri' => $uri
          )
        ));
    
        if (is_wp_error($response)) {
          $error_message = $response->get_error_message();
          Logger::error('[BMI PRO] Something went wrong during getting pcloud token:' . $error_message);
        } else {
          $result = json_decode($response['body']);
          if (isset($result->access_token) && $result->access_token) {
            $accessToken = $result->access_token;
            update_option($this->accessToken, $accessToken);
            update_option('bmip_pcloud_was_connected', true);
            $this->resetSDK();
            return $accessToken;
          }
        }
        if (get_option('bmip_pcloud_was_connected')) {
          set_transient('bmip_pcloud_issue', 'auth_error_disconnected');
          delete_option($this->accessToken);
          $this->resetSDK();
        }
        return false;
    }


    /**
     * verifyConnection - Checks if the pCloud is still granted and tokens are not expired
     * 
     * @return array explain the status of the connection in format
     *   [
     *    'status' => 'success' | 'error',
     *    'result' => 'connected' | 'disconnected' 
     *  ]
     */
    public function verifyConnection() {
        $tempKeyPCloudFile = BMI_TMP . DIRECTORY_SEPARATOR . 'pcloudKeys.php';
        $lockFile = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock';
        if (file_exists($tempKeyPCloudFile) && !file_exists($lockFile)) {
            $pcloudKeys = file_get_contents($tempKeyPCloudFile);
            $lines = explode("\n", $pcloudKeys);
            $allOptionsLoaded = false;

            if (count($lines) == 6) {
                if (function_exists('wp_load_alloptions')) {
                    wp_load_alloptions(true);
                    $allOptionsLoaded = true;
                }
                update_option($this->id, substr($lines[1], 2));
                update_option($this->authCodeOption, substr($lines[2], 2));
                update_option($this->accessToken, substr($lines[3], 2));
                update_option('bmip_pcloud_hostname', substr($lines[4], 2));
            }

            if (strpos(site_url(), 'tastewp') !== false) {
                if (function_exists('wp_load_alloptions') && !$allOptionsLoaded) {
                    wp_load_alloptions(true);
                }
                update_option('__tastewp_redirection_performed', true);
                update_option('auto_smart_tastewp_redirect_performed', 1);
                update_option('tastewp_auto_activated', true);
                update_option('__tastewp_sub_requested', true);
            }
            unlink($tempKeyPCloudFile);
            $this->resetSDK();
        }

        $res = $this->getSpaceUsage() ? 'connected' : 'disconnected';
        if ($res == 'connected') {
            update_option('bmip_pcloud_was_connected', true);
            if (get_transient('bmip_pcloud_issue') == 'auth_error_disconnected') {
                delete_transient('bmip_pcloud_issue');
            }
        } else {
            if (get_option('bmip_pcloud_was_connected')) {
                set_transient('bmip_pcloud_issue', 'auth_error_disconnected');
            }
        }
        return ['status' => 'success', 'result' => $res];
    }


      /**
       * disconnect - Removes the pCloud connection
       * 
       * @return array explain the status of the connection in format
       *  [
       *   'status' => 'success' | 'error'
       * ]
       */
      public function disconnect() {

        $baseurl = home_url();
        if (substr($baseurl, 0, 4) != 'http') {
          if (is_ssl()) $baseurl = 'https://' . home_url();
          else $baseurl = 'http://' . home_url();
        }

        $pcloudAuthCode = get_option($this->authCodeOption, '');
        $pcloudId = get_option($this->id, '');
  
        $url = 'https://authentication.backupbliss.com/v1/pcloud/disconnect';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 2,
            'httpversion' => '1.0',
            'blocking' => true,
            'body' => array(
                'client_id' => $pcloudAuthCode,
                'site_token' => $pcloudId,
                'redirect_uri' => $baseurl
            )
        ));
        delete_option($this->authCodeOption);
        delete_option($this->id);
        delete_option($this->accessToken);
        delete_option('bmip_pcloud_hostname');
        delete_transient('bmip_pcloud_issue');
        delete_option('bmip_pcloud_was_connected');
        Dashboard\bmi_set_config('STORAGE::EXTERNAL::PCLOUD', false);
        $this->resetSDK();
  
  
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Logger::error('[BMI PRO] Something went wrong during pCloud removal process:' . $error_message);
            return [ 'status' => 'error' ];
        }

        return [ 'status' => 'success' ];
  
      }

}
