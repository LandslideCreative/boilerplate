<?php

namespace BMI\Plugin\External;


// This file contains the SFTP class responsible for handling all SFTP operations.
// It uses the phpseclib library to connect to the SFTP server.
//
// Configuration options for SFTP connection:
// - bmip_sftp_host: hostname or IP address
// - bmip_sftp_port: port number
// - bmip_sftp_authType: 'key' or 'password'
// - bmip_sftp_username: username for the connection
// - bmip_sftp_password: username password or private key
// - bmip_sftp_passphrase: (optional) passphrase for the private key
// - bmip_sftp_fingerprint: (optional) fingerprint of the server key without algorithm prefix
// - bmip_sftp_remote_path: (optional) path to the remote directory
//
// Plugin configuration options for SFTP:
// - STORAGE::EXTERNAL::SFTP: boolean value to enable or disable SFTP
// General configuration options:
// - bmip_sftp_reconnect_after: transient key to store the time when the SFTP connection should be reconnected to avoid wasting resources on trying to reconnect too often

require_once BMI_PRO_INC . '/vendor/autoload.php';

use phpseclib\Net\SFTP;
use BMI\Plugin\Dashboard;
use BMI\Plugin\BMI_Logger as Logger;
use phpseclib\Crypt\RSA;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Backup_Migration_Plugin as BMP;

// Exception Class for SFTP Client Errors
class SftpClientException extends \Exception
{
}

// Interface for SFTP/SCP operations
interface RemoteFileTransferInterface {
    public function uploadChunk($fileName, $data);
    public function uploadFile($fileName, $localPath);
    public function deleteFile($fileName);
    public function createFile($fileName);
    public function createDirectoryIfNotExists($fullPath);
    public function disconnect();
    public function listFiles();
    public function downloadFile($fileName, $localPath, $offset = 0, $length = -1);
    public function connect($host, $port, $authType, $username, $password, $fingerPrint = null, $passphrase = null);
    public function getFileMeta($fileName);
    public function getFileContent($fileName, $offset, $length);
}


class PhpseclibSftpClient implements RemoteFileTransferInterface {
    private $sftp;
    private $rootPath;
    private $status;
    private $enabled = false;
    private $configuration = [];

    /**
     * Constructor — stores configuration without connecting.
     * The actual TCP connection is deferred until ensureConnected() is called
     * (either explicitly or via getStatus() / any operation method).
     *
     * @param array $configuration (optional) Configuration options for SFTP
     */
    public function __construct($configuration = [])
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::SFTP');
        if (!($isEnabled === true || $isEnabled === 'true')) {
            $this->sftp = null;
            $this->enabled = false;
            return;
        }

        $this->enabled = true;
        $this->configuration = $configuration;
        $this->rootPath = $configuration['path'] ?? '/BACKUP_MIGRATION_BACKUPS';
    }

    /**
     * Lazily establish the SFTP connection on first use.
     *
     * @return bool true if connected, false otherwise
     */
    public function ensureConnected()
    {
        if ($this->sftp !== null) return true;
        if (!$this->enabled) return false;

        $host = isset($this->configuration['host']) ? $this->configuration['host'] : false;
        $port = isset($this->configuration['port']) ? $this->configuration['port'] : false;
        $authType = isset($this->configuration['authType']) ? $this->configuration['authType'] : false;
        $username = isset($this->configuration['username']) ? $this->configuration['username'] : false;
        $password = isset($this->configuration['password']) ? $this->configuration['password'] : false;
        $fingerPrint = isset($this->configuration['fingerPrint']) ? $this->configuration['fingerPrint'] : null;
        $passphrase = isset($this->configuration['passphrase']) ? $this->configuration['passphrase'] : null;

        if ($host === false || $username === false || $password === false) {
            return false;
        }

        try {
            $this->connect($host, $port, $authType, $username, $password, $fingerPrint, $passphrase);
            return true;
        } catch (SftpClientException $e) {
            $this->sftp = null;
            return false;
        }
    }

    /**
     * Check whether valid SFTP credentials are configured (without connecting).
     *
     * @return bool
     */
    public function isConfigured()
    {
        if (!$this->enabled) return false;
        $host = isset($this->configuration['host']) ? $this->configuration['host'] : false;
        $username = isset($this->configuration['username']) ? $this->configuration['username'] : false;
        $password = isset($this->configuration['password']) ? $this->configuration['password'] : false;
        return $host !== false && $username !== false && $password !== false;
    }

    /**
     * Get the status of the SFTP connection.
     * Triggers lazy connection if not yet established.
     */
    public function getStatus()
    {
        if ($this->sftp === null) {
            $this->ensureConnected();
        }
        return $this->sftp !== null && !in_array(get_transient('bmip_sftp_issue'), ['disconnected']);
    }



    /**
     * Connect to SFTP server
     *
     * @param string $host Hostname or IP address
     * @param int $port Port number
     * @param string $authType 'key' or 'password'
     * @param string $username Username for the connection
     * @param string $password Username password or private key
     * @param string|null $fingerPrint (optional) Fingerprint of the server key without algorithm prefix
     * @param string|null $passphrase (optional) Passphrase for the private key
     * @return bool
     * @throws SftpClientException If connection fails
     */
    public function connect($host, $port, $authType, $username, $password, $fingerPrint = null, $passphrase = null)
    {
        // Retry TCP connection up to 3 times with progressive backoff before giving up.
        // Servers under load may transiently refuse connections.
        $maxConnectionRetries = 3;
        $connected = false;

        for ($attempt = 1; $attempt <= $maxConnectionRetries; $attempt++) {
            $this->sftp = new SFTP($host, $port);
            if (!$host || !$port || $this->sftp->_connect()) {
                $connected = true;
                break;
            }
            if ($attempt < $maxConnectionRetries) {
                usleep(500000 * $attempt); // 0.5 s, then 1 s before the final attempt
            }
        }

        if (!$connected) {
            // Increment the consecutive-failure counter so a single transient blip does
            // not immediately surface the "disconnected" admin notice.
            $failures = intval(get_transient('bmip_sftp_connect_failures')) + 1;
            set_transient('bmip_sftp_connect_failures', $failures, 10 * MINUTE_IN_SECONDS);
            if ($failures >= 3) {
                set_transient('bmip_sftp_issue', 'disconnected', 2 * MINUTE_IN_SECONDS);
            }
            throw new SftpClientException('Failed to connect to SFTP server. Check the host and port.');
        }

        // Successful TCP connection — reset the failure counter and clear any stale notice.
        delete_transient('bmip_sftp_connect_failures');

        if (get_transient('bmip_sftp_issue') == 'disconnected') {
            delete_transient('bmip_sftp_issue');
        }

        if ($fingerPrint != null) {
            $serverFingerprint = $this->getServerFingerprint();
            $isFingerprintMatch = false;
            foreach ($serverFingerprint as $key => $value) {
                if ($value == $fingerPrint) {
                    $isFingerprintMatch = true;
                    break;
                }
            }
            if (!$isFingerprintMatch) {
                throw new SftpClientException('It seems that the server fingerprint does not match.');
            }
        }

        if ($authType == 'key') {
            $this->loginWithKey($username, $password, $passphrase);
        } elseif ($authType == 'password') {
            $this->loginWithPassword($username, $password);
        } else {
            throw new SftpClientException('Unsupported authentication type.');
        }

        $this->createDirectoryIfNotExists($this->rootPath);

        return true;
    }

    public function setRootPath($path)
    {
        $this->rootPath = $path;
    }

    /**
     * Upload chunk of data to SFTP server
     *
     * @param string $fileName
     * @param string $data
     * @return bool
     * @throws SftpClientException If upload fails
     */
    public function uploadChunk($fileName, $data)
    {
        if ($this->getStatus() === false) return false;

        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        if (!$this->sftp->put($remotePath, $data, SFTP::RESUME)) {
            throw new SftpClientException('Failed to upload chunk.');
        }

        return true;
    }

    /**
     * Delete file from SFTP server
     *
     * @param string $fileName
     * @return bool
     */
    public function deleteFile($fileName)
    {
        if ($this->getStatus() === false) return false;

        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->delete($remotePath);
    }

    /**
     * Create file on SFTP server
     *
     * @param string $fileName
     * @return bool
     */
    public function createFile($fileName)
    {
        if ($this->getStatus() === false) return false;
        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->touch($remotePath);
    }

    /**
     * Disconnect from SFTP server
     *
     * @return bool
     */
    public function disconnect()
    {
        if ($this->sftp !== null) {
            $this->sftp->disconnect();
            $this->sftp = null;
        }
        return true;
    }

    /**
     * List files on SFTP server
     *
     * @return array|false array with files on the server, false on error
     * @see https://phpseclib.com/docs/sftp#directory-listing for array structure
     */
    public function listFiles()
    {
        if ($this->getStatus() === false) return false;
        $files = $this->sftp->rawlist($this->rootPath);
        return $files === false ? [] : $files;

    }

    /**
     * Download file from SFTP server
     *
     * @param string $fileName
     * @param string $localPath
     * @param int $offset
     * @param int $length
     * @return bool
     */
    public function downloadFile($fileName, $localPath, $offset = 0, $length = -1)
    {
        if ($this->getStatus() === false) return false;
        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->get($remotePath, $localPath, $offset, $length);
    }

    /**
     * Get file metadata
     *
     * @param string $fileName
     * @return array|bool False if no file found, array with file metadata
     * @see https://phpseclib.com/docs/sftp#file-information for array structure
     */
    public function getFileMeta($fileName)
    {
        if ($this->getStatus() === false) return false;
        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->stat($remotePath);
    }

    /**
     * Upload file to SFTP server
     *
     * @param string $fileName
     * @param string $localPath
     * @return bool
     */
    public function uploadFile($fileName, $localPath)
    {
        if ($this->getStatus() === false) return false;
        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * Get file content
     *
     * @param string $fileName
     * @param string $offset
     * @param string $length
     * @return string|bool False if no file found, string with file content
     */
    public function getFileContent($fileName, $offset = 0, $length = -1)
    {
        if ($this->getStatus() === false) return false;
        $remotePath = BMP::fixSlashes($this->rootPath . DIRECTORY_SEPARATOR . $fileName);
        return $this->sftp->get($remotePath, false, $offset, $length);
    }

    /**
     * Create directory on SFTP server if it does not exist
     *
     * @param string $fullPath
     * @return bool
     */
    public function createDirectoryIfNotExists($fullPath)
    {
        if ($this->getStatus() === false) return false;
        $fullPath = BMP::fixSlashes($fullPath);
        
        if ($this->sftp->is_dir($fullPath)) {
            return true;
        }
    
        $parts = explode(DIRECTORY_SEPARATOR, trim($fullPath, DIRECTORY_SEPARATOR));
        $currentPath = '';
    
        foreach ($parts as $part) {
            $currentPath .= DIRECTORY_SEPARATOR . $part;
            
            if (!$this->sftp->is_dir($currentPath)) {
                if (!$this->sftp->mkdir($currentPath)) {
                    // Log or handle error appropriately if directory creation fails
                    error_log("Failed to create directory: $currentPath");
                    return false;
                }
            }
        }
    
        return $this->sftp->is_dir($fullPath);
    }
    

    /**
     * Get server fingerprint
     *
     * @return array|bool False if no key loaded, array with sha256 and md5 fingerprints
     * @throws SftpClientException If key loading fails
     */
    private function getServerFingerprint()
    {
        $serverPubKey = $this->sftp->getServerPublicHostKey();
        if ($serverPubKey === false) {
            throw new SftpClientException('Failed to retrieve server public host key.');
        }

        $hostKey = new RSA();
        if (!$hostKey->loadKey($serverPubKey)) {
            throw new SftpClientException('Server key load failed.');
        }

        return [
            'sha256' => $hostKey->getPublicKeyFingerprint('sha256'),
            'md5' => $hostKey->getPublicKeyFingerprint('md5')
        ];
    }

    /**
     * Login with key
     *
     * @param string $username
     * @param string $privateKey
     * @param string $passphrase
     * @return bool
     * @throws SftpClientException If key loading or login fails
     */
    private function loginWithKey($username, $privateKey, $passphrase = null)
    {
        $hostKey = new RSA();
        if ($passphrase !== null) {
            $hostKey->setPassword($passphrase);
        }

        if (!$hostKey->loadKey($privateKey)) {
            throw new SftpClientException('Key load failed.');
        }

        if (!$this->sftp->login($username, $hostKey)) {
            // Detect if login failed because key requires passphrase
            if (strpos($privateKey, 'ENCRYPTED') !== false && $passphrase === null) {
                throw new SftpClientException('Passphrase required.');
            }
            throw new SftpClientException('Invalid authentication');
        }

        return true;
    }

    /**
     * Login with password
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws SftpClientException If login fails
     */
    private function loginWithPassword($username, $password)
    {
        if (!$this->sftp->login($username, $password)) {
            throw new SftpClientException('Credentials are not valid. Please check the username and password.');
        }

        return true;
    }
}

// Factory for Creating Remote File Transfer Clients
class RemoteFileTransferFactory
{
    public static function create($type = 'phpseclib', $configuration = [])
    {
        switch ($type) {
            case 'phpseclib':
                return new PhpseclibSftpClient( $configuration);
            default:
                throw new SftpClientException('Unsupported file transfer type.');
        }
    }
}


class BMI_External_SFTP {

    private $sftpClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $configuration = $this->retrieveSftpCredentials();
        $this->sftpClient = RemoteFileTransferFactory::create('phpseclib', $configuration);
        add_action('bmi_premium_remove_backup_file', [&$this, 'deleteBackup']);
        add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteBackupJson']);
        
        // Restart the upload process when SFTP settings are updated
        add_action('update_option_bmip_sftp_host', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_port', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_authType', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_username', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_password', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_fingerprint', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_sftp_passphrase', [&$this, 'restartUploadProcess']);
        add_action('set_transient_bmip_sftp_connection_status', [&$this, 'restartUploadProcess']);
        add_action('delete_transient_bmip_sftp_issue', [&$this, 'resetIssueDisplayOption']);
        add_action('set_transient_bmip_sftp_issue', [&$this, 'resetIssueDisplayOption']);
        add_action('update_option_bmip_sftp_remote_path', [&$this, 'restartUploadProcess']);
    }

    public function resetIssueDisplayOption()
    {
        delete_option('bmip_sftp_dismiss_issue');
    }

    /**
     * Checks for backups to upload to SFTP server
     *
     * @return array Status of the upload process
     */
    public function checkForBackupsToUpload()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::SFTP');
        if (!($isEnabled === true || $isEnabled === 'true')) {
            update_option('bmip_to_be_uploaded', [ 'current_upload' => [], 'queue' => [] ]);
            return ['status' => 'not_enabled'];
        }
        if ($this->sftpClient->getStatus() === false) {
            return ['status' => 'error'];
        }
    
        $requiresUpload = get_option('bmip_to_be_uploaded', [
          'current_upload' => [],
          'queue' => [],
          'failed' => []
        ]);

        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
        $backups = new Backups();
        $backupsAvailable = $backups->getAvailableBackups("local");
        $localBackups = $backupsAvailable['local'];
        $parsedSftpFiles = $this->getParsedFiles();
        if ($parsedSftpFiles === false) {
            return ['status' => 'error'];
        }
        $backupsFileName = isset($parsedSftpFiles['zipFilesName']) ? $parsedSftpFiles['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedSftpFiles['jsonFilesPath']) ? $parsedSftpFiles['jsonFilesPath'] : [];
        $availableManifests = array_map(function ($path) {
            return pathinfo($path, PATHINFO_FILENAME);
        }, $manifestFilesPath);
        $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

        foreach ($localBackups as $name => $details) {
            $md5 = $details[7];
            if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['sftp'])) {
                continue;
            }
            $isBackupNotExists = !in_array($md5, $availableManifests) || !in_array($name, array_keys($backupsFileName));
            if ($isBackupNotExists && !(isset($requiresUpload['current_upload']['task']) && $requiresUpload['current_upload']['task'] == 'sftp_' . $md5)) {
                $requiresUpload['queue']['sftp_' . $md5] = [
                    'name' => $name,
                    'md5' => $md5,
                    'json' => $md5 . '.json',
                ];
            }
        }

        update_option('bmip_to_be_uploaded', $requiresUpload);
        return ['status' => 'success'];
    }

    /**
     * Restarts the upload process of backups
     *
     * @return array Status of the upload process
     */
    public function restartUploadProcess()
    {
        $requiredToUpload = get_option('bmip_to_be_uploaded', [
            'current_upload' => [],
            'queue' => [],
            'failed' => []
        ]);

        if (isset($requiredToUpload['current_upload']['task']) && strpos($requiredToUpload['current_upload']['task'], 'sftp') !== false) {
            unset($requiredToUpload['current_upload']);
        }

        if (!isset($requiredToUpload['failed'])) {
            $requiredToUpload['failed'] = [];
        }

        foreach ($requiredToUpload['failed'] as $key => $value) {
            if (strpos($key, 'sftp_') !== false) {
                unset($requiredToUpload['failed'][$key]);
            }
        }

        update_option('bmip_to_be_uploaded', $requiredToUpload);
        return $this->checkForBackupsToUpload();
    }

    /**
     * Retrieves and parses files from the SFTP server
     *
     * @return array|bool Parsed files or false on error
     */
    public function getParsedFiles()
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }
        $zipFilesName = [];
        $jsonFilesPath = [];
        $files = $this->sftpClient->listFiles();
        if ($files === false) {
            return false;
        }


        foreach ($files as $filename => $metadata) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($extension, array('zip', 'tar', 'gz'))) {
                $zipFilesName[$filename] = ['id' => $filename, 'size' => $metadata['size']];
            } elseif ($extension === 'json') {
                $jsonFilesPath[] = $filename;
            }
        }

        return compact('zipFilesName', 'jsonFilesPath');
    }

    /**
     * Check whether SFTP credentials are configured (without connecting).
     *
     * @return bool
     */
    public function isConfigured()
    {
        return $this->sftpClient->isConfigured();
    }

    /**
     * Verifies the SFTP connection status (triggers actual connection if needed).
     *
     * @return array Connection status
     */
    public function verifyConnection()
    {
        if ($this->sftpClient->getStatus() === false) {
            return ['status' => 'error', 'result' => 'disconnected'];
        }
        return ['status' => 'success', 'result' => 'connected'];
    }

    /**
     * Tests the SFTP server connection
     *
     * @param string $host
     * @param int $port
     * @param string $authType
     * @param string $username
     * @param string $password
     * @param string|null $fingerPrint
     * @param string|null $passphrase
     * @return array Connection test result
     */
    public function testConnection($host, $port, $authType, $username, $password, $fingerPrint = null, $passphrase = null, $remotePath = null)
    {
        try {
            $this->sftpClient->setRootPath($remotePath);
            $result = $this->sftpClient->connect($host, $port, $authType, $username, $password, $fingerPrint, $passphrase);
            $testFileName = 'bmip_test_' . time() . '.txt';
            $testContent = 'test';
            $createResult = $this->sftpClient->createFile($testFileName);
            
            if ($createResult === false) {
                throw new SftpClientException('Cannot create a file in the directory: ' . $remotePath . '. This folder may be blocked or not writable.');
            }

            $writeResult = $this->sftpClient->uploadChunk($testFileName, $testContent);
            if ($writeResult === false) {
                throw new SftpClientException('Cannot write to the file in the directory: ' . $remotePath . '. This folder may be blocked or not writable.');
            }

            $readResult = $this->sftpClient->getFileContent($testFileName, 0, strlen($testContent));
            if ($readResult !== $testContent) {
                throw new SftpClientException('Cannot read the file in the directory: ' . $remotePath . '. This folder may be blocked or not readable.');
            }

            $deleteResult = $this->sftpClient->deleteFile($testFileName);
            if ($deleteResult === false) {
                throw new SftpClientException('Cannot delete the file in the directory: ' . $remotePath . '. This folder may be blocked or not writable.');
            }

            return ['status' => 'success', 'result' => $result === true ? 'connected' : 'disconnected'];
        } catch (SftpClientException $e) {
            return ['status' => 'error', 'result' => $e->getMessage()];
        }
    }

    /**
     * Disconnects from the SFTP server
     *
     * @return array Status of the disconnection
     */
    public function disconnect()
    {
        try {
            delete_option('bmip_sftp_host');
            delete_option('bmip_sftp_port');
            delete_option('bmip_sftp_authType');
            delete_option('bmip_sftp_username');
            delete_option('bmip_sftp_password');
            delete_option('bmip_sftp_fingerprint');
            delete_option('bmip_sftp_passphrase');
            $this->restartUploadProcess();

            return ['status' => 'success'];
        } catch (\Exception $e) {
            Logger::error('[BMI PRO] Disconnect error: ' . $e->getMessage());
            return ['status' => 'error'];
        }
    }

    /**
     * Uploads a backup to the SFTP server
     *
     * @param string $backupName Name of the backup to upload
     * @param int $offset Offset to start uploading from
     * @param string $md5 MD5 of the backup to upload
     * @return array Upload status
     */
    public function uploadBackup($backupName, $offset, $md5)
    {
        if ($this->sftpClient->getStatus() === false) {
            return ['status' => 'error', 'error' => 'disconnected'];
        }
        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
        $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';

        if (!file_exists($backupPath) || !file_exists($manifestPath)) {
            Logger::error('[BMI PRO] File not found: ' . $backupName);
            return [
                'status' => 'error',
                'error' => 'internal_file_not_found'
            ];
          }

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $fileSize = filesize($backupPath);

        if (($availableMemory / 4) <= $fileSize && $fileSize < 10485760) { // 10MB
            $uploadResult = $this->sftpClient->uploadFile($backupName, $backupPath);
            if ($uploadResult) {
                $manifestUploadResult = $this->sftpClient->uploadFile($md5 . '.json', $manifestPath);
                if ($manifestUploadResult) {
                    return ['status' => 'success'];
                } else {
                    return ['status' => 'error', 'error' => 'manifest'];
                }
            } else {
                return ['status' => 'error', 'error' => 'start_upload_in_one_go'];
            }
        }

        $fileMeta = $this->sftpClient->getFileMeta($backupName);
        if ($fileMeta === false) {
            $creatingResult = $this->sftpClient->createFile($backupName);
            if ($creatingResult === false) {
                return ['status' => 'error', 'error' => 'create_file'];
            }
        } else {
            $remoteFileSize = $fileMeta['size'];
            if ($remoteFileSize != $offset) {
                $this->sftpClient->deleteFile($backupName);
                return ['status' => 'error', 'error' => 'incorrect_offset'];
            }
        }

        if ($offset < $fileSize) {
            $newOffset = $this->uploadChunk($backupName, $offset);
            if ($newOffset === false) {
                return ['status' => 'error', 'error' => 'chunk'];
            }
            return ['status' => 'continue', 'offset' => $newOffset];
        } else {
            $manifestUploadResult = $this->sftpClient->uploadFile($md5 . '.json', $manifestPath);
            if ($manifestUploadResult) {
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'error' => 'manifest'];
            }
        }
    }

    /**
     * Uploads a chunk of a backup to the SFTP server
     *
     * @param string $backupName Name of the backup to upload
     * @param int $offset Offset to start uploading from
     * @param int $chunkSize Size of the chunk to upload
     * @return int|bool New offset if success, false if error
     */
    public function uploadChunk($backupName, $offset, $chunkSize = 10485760) // 10MB
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }

        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;

        $backupFile = fopen($backupPath, 'r');
        if ($backupFile === false) {
            Logger::error('[BMI PRO] Unable to open backup file: ' . $backupName);
            return false;
        }

        if (fseek($backupFile, $offset) !== 0) {
            fclose($backupFile);
            Logger::error('[BMI PRO] Failed to seek in file: ' . $backupName);
            return false;
        }

        $data = fread($backupFile, $chunkSize);
        if ($data === false) {
            fclose($backupFile);
            Logger::error('[BMI PRO] Failed to read from file: ' . $backupName);
            return false;
        }

        $uploadingResult = $this->sftpClient->uploadChunk($backupName, $data);
        if (!$uploadingResult) {
            fclose($backupFile);
            Logger::error('[BMI PRO] Failed to upload chunk for file: ' . $backupName);
            return false;
        }

        $newOffset = $offset + strlen($data);
        fclose($backupFile);

        return $newOffset;
    }

    /**
     * Checks if a file exists on the SFTP server
     *
     * @param string $fileName Path to the file on the SFTP server
     * @return bool
     */
    public function isFileExists($fileName)
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }
        $file = $this->sftpClient->getFileMeta($fileName);
        return $file !== false;
    }

    /**
     * Get file metadata from the SFTP server
     * 
     * @param string $fileName Path to the file on the SFTP server
     * @return array|bool File metadata or false on error
     */
    public function getFileMeta($fileName)
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }
        return $this->sftpClient->getFileMeta($fileName);
    }

    /**
     * Deletes a backup from the SFTP server
     *
     * @param string $md5 MD5 of the backup to delete
     * @return bool
     */
    public function deleteBackup($md5)
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }

        $manifestFile = $md5 . '.json';
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile)) {
            $manifestContent = json_decode(file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile), true);
        } else {
            $manifestContent = json_decode($this->sftpClient->getFileContent($manifestFile), true);
        }
        $backupName = isset($manifestContent['name']) ? $manifestContent['name'] : '';
        if (empty($backupName)) {
            Logger::error('[BMI PRO] Manifest does not contain backup name.');
            return false;
        }
        $deleteManifest = $this->sftpClient->deleteFile($manifestFile);
        $deleteBackup = $this->sftpClient->deleteFile($backupName);
        return $deleteManifest && $deleteBackup;
    }

    /**
     * Deletes a backup manifest from the SFTP server
     *
     * @param string $md5 MD5 of the backup to delete
     * @return bool
     */
    public function deleteBackupJson($md5)
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }

        $manifestFile = $md5 . '.json';
        return $this->sftpClient->deleteFile($manifestFile);
    }

    /**
     * Retrieves SFTP credentials from temporary file or options
     *
     * @return array Credentials array
     */
    private function retrieveSftpCredentials()
    {
        $tempKeySFTPFile = BMI_TMP . DIRECTORY_SEPARATOR . 'sftpKeys.php';
        $options = array(
            'bmip_sftp_host',
            'bmip_sftp_port',
            'bmip_sftp_username',
            'bmip_sftp_authType',
            'bmip_sftp_password',
            'bmip_sftp_remote_path',
            'bmip_sftp_fingerprint',
            'bmip_sftp_passphrase'
        );
        if (file_exists($tempKeySFTPFile) && !file_exists(BMI_BACKUPS . '/.migration_lock')) {
            $sftpKeys = file_get_contents($tempKeySFTPFile);
            $lines = explode("\n", $sftpKeys);
    
            foreach ($options as $index => $option) {
                if (isset($lines[$index + 1])) {
                    if ($option == 'bmip_sftp_password') {
                        update_option($option, base64_decode(trim(substr($lines[$index + 1], 2))));
                    } else {
                        update_option($option, trim(substr($lines[$index + 1], 2)));
                    }
                }
            }

            unlink($tempKeySFTPFile);
        }

        $credentials = array(
            'host' => get_option('bmip_sftp_host', false),
            'port' => get_option('bmip_sftp_port', false),
            'username' => get_option('bmip_sftp_username', false),
            'authType' => get_option('bmip_sftp_authType', false),
            'password' => get_option('bmip_sftp_password', false),
            'path' => get_option('bmip_sftp_remote_path', false),
            'fingerPrint' => get_option('bmip_sftp_fingerprint', null),
            'passphrase' => get_option('bmip_sftp_passphrase', null),
        );


        return $credentials;
    }

    /**
     * Get file content from the SFTP server
     * 
     * @param string $fileName Path to the file on the SFTP server
     * @param string $range Range of bytes to retrieve in the format "start-end"
     * @return string|bool File content or false on error
     */
    public function getFileContent($fileName, $range = '0-0')
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }
        if ($range === '0-0') {
            return $this->sftpClient->getFileContent($fileName);
        }

        $range = explode('-', $range);
        
        if (count($range) !== 2) {
            return false;
        }

        $offset = intval($range[0]);
        $length = intval($range[1]) - $offset + 1;


        return $this->sftpClient->getFileContent($fileName, $offset, $length);
    }

    /**
     * Get the manifest content from the SFTP server
     * 
     * @param string $md5 MD5 of the backup
     * @return array|bool Manifest content or false on error
     */
    public function getManifestContent($md5)
    {
        if ($this->sftpClient->getStatus() === false) {
            return false;
        }
        $manifestFile = $md5 . '.json';
        $manifestContent = $this->sftpClient->getFileContent($manifestFile);
        if ($manifestContent === false) {
            return false;
        }
        return json_decode($manifestContent, true);
    }

    /**
     * Destructor
     * Disconnects from the SFTP server
     */
    public function __destruct()
    {
        $this->sftpClient->disconnect();
    }
}