<?php

namespace BMI\Plugin\External;

// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

use BMI\Plugin\BMI_Logger as Logger;

/**
 * BMI_PCloud_SDK
 * 
 * Lightweight pCloud API SDK for handling all pCloud API operations.
 * 
 * @see https://docs.pcloud.com/
 */
class BMI_PCloud_SDK
{
    /** @var string Access token for pCloud API */
    private $accessToken;

    /** @var string API hostname */
    private $apiUrl;

    /** @var int cURL timeout in seconds */
    private $timeout = 300;

    /** @var int Max chunk size for uploads (10MB) */
    private $chunkSize = 10485760;

    /**
     * @param string $accessToken pCloud API access token
     * @param string $apiUrl pCloud API hostname (US or EU)
     */
    public function __construct($accessToken, $apiUrl = 'https://api.pcloud.com')
    {
        $this->accessToken = $accessToken;
        $this->apiUrl = rtrim($apiUrl, '/');
    }

    /**
     * Set the access token
     * 
     * @param string $accessToken
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get the current access token
     * 
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Make a request to the pCloud API
     * 
     * @param string $endpoint API endpoint (e.g. 'listfolder', 'upload_create')
     * @param array $params Query parameters
     * @param string|null $body Raw body data for PUT requests (upload_write, uploadfile)
     * @param string $method HTTP method: 'GET', 'POST', or 'PUT'
     * @return array|false Decoded JSON response array on success, false on error
     */
    public function request($endpoint, $params = array(), $body = null, $method = 'GET')
    {
        if (!$this->accessToken) {
            Logger::error('[PCloud SDK] No access token set');
            return false;
        }

        $url = $this->apiUrl . '/' . $endpoint . '?' . http_build_query($params);

        $authHeader = 'Authorization: Bearer ' . $this->accessToken;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BMI pCloud SDK');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $authHeader
        ));

        switch ($method) {
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            default: // GET
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            unset($ch);
            Logger::error('[PCloud SDK] cURL error: ' . $error);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($httpCode !== 200) {
            Logger::error('[PCloud SDK] HTTP error ' . $httpCode . ': ' . $response);
            return false;
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            Logger::error('[PCloud SDK] Failed to decode JSON response');
            return false;
        }

        if (isset($decoded['result']) && $decoded['result'] != 0) {
            $errorMsg = isset($decoded['error']) ? $decoded['error'] : 'Unknown error';
            $errorCode = $decoded['result'];
            Logger::error('[PCloud SDK] API error ' . $errorCode . ': ' . $errorMsg);
            return false;
        }

        return $decoded;
    }


    /***************************************************
     * File Operations
     ***************************************************/

    /**
     * Get file metadata using file ID
     * 
     * @param int|string $fileId pCloud file ID
     * @return array|false File metadata array or false on error
     */
    public function getFileMeta($fileId)
    {
        $fileId = str_replace('f', '', $fileId);

        $response = $this->request('checksumfile', array('fileid' => $fileId));

        if ($response === false) {
            return false;
        }

        return isset($response['metadata']) ? $response['metadata'] : false;
    }

    /**
     * Delete a file in pCloud
     * 
     * @param string|int $file File path or file ID (prefixed with 'f')
     * @return bool True on success, false on error
     */
    public function deleteFile($file)
    {
        $params = array();

        $isId = false;
        if (is_string($file) && strpos($file, 'f') === 0) {
            $isId = intval(str_replace('f', '', $file)) != 0;
        }

        if ($isId) {
            $params['fileid'] = $file;
        } else {
            if (is_string($file) && strlen($file) > 0 && $file[0] !== '/') {
                $file = '/' . $file;
            }
            $params['path'] = $file;
        }

        $response = $this->request('deletefile', $params);

        return $response !== false;
    }

    /**
     * Get a temporary download link for a file
     * 
     * @param int|string $fileId pCloud file ID 
     * @return string|false Download URL or false on error
     */
    public function getFileLink($fileId)
    {
        $fileId = str_replace('f', '', $fileId);

        $response = $this->request('getfilelink', array('fileid' => $fileId));

        if ($response === false || !isset($response['hosts'][0]) || !isset($response['path'])) {
            return false;
        }

        return 'https://' . $response['hosts'][0] . $response['path'];
    }

    /**
     * Download file content from pCloud
     * 
     * @param int|string $fileId pCloud file ID
     * @param string $range Optional byte range (e.g. '0-100')
     * @return string|false File content or false on error
     */
    public function getFileContent($fileId, $range = '')
    {
        $url = $this->getFileLink($fileId);

        if (!$url) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($range) {
            curl_setopt($ch, CURLOPT_RANGE, $range);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            unset($ch);
            Logger::error('[PCloud SDK] Download error: ' . $error);
            return false;
        }

        unset($ch);

        return $response;
    }


    /***************************************************
     * Folder Operations
     ***************************************************/

    /**
     * List files in a pCloud folder
     * 
     * @param int $folderId Folder ID (0 = root)
     * @return array|false Array of file entries or false on error
     */
    public function listFiles($folderId = 0)
    {
        $response = $this->request('listfolder', array('folderid' => $folderId));

        if ($response === false || !isset($response['metadata']['contents'])) {
            return false;
        }

        return $response['metadata']['contents'];
    }


    /***************************************************
     * Upload Operations
     ***************************************************/

    /**
     * Create a new upload session for chunked uploads
     * 
     * @return int|false Upload ID or false on error
     */
    public function startUploadSession()
    {
        $response = $this->request('upload_create');

        if ($response === false || !isset($response['uploadid'])) {
            return false;
        }

        return $response['uploadid'];
    }

    /**
     * Upload a chunk of data to an active upload session
     * 
     * @param int $uploadId Upload session ID
     * @param string $filePath Local file path to read from
     * @param int $offset Byte offset to start reading from
     * @param int $maxRetries Maximum number of retries on failure
     * @return int|false New offset after successful upload, or false on error
     */
    public function uploadChunk($uploadId, $filePath, $offset, $maxRetries = 3)
    {
        if (!file_exists($filePath)) {
            Logger::error('[PCloud SDK] File not found: ' . $filePath);
            return false;
        }

        $fileSize = filesize($filePath);
        $chunkSize = $this->chunkSize;

        $retryCount = 0;
        while ($retryCount < $maxRetries) {
            if ($offset + $chunkSize > $fileSize) {
                $chunkSize = $fileSize - $offset;
            }

            $stream = fopen($filePath, 'r');
            if (!$stream || $offset >= $fileSize) {
                Logger::error('[PCloud SDK] Could not open file: ' . $filePath);
                return false;
            }

            fseek($stream, $offset);
            $chunk = fread($stream, $chunkSize);
            fclose($stream);

            $params = array(
                'uploadid' => $uploadId,
                'uploadoffset' => $offset
            );

            $response = $this->request('upload_write', $params, $chunk, 'PUT');

            if ($response === false) {
                $retryCount++;
                continue;
            }

            return $offset + $chunkSize;
        }

        Logger::error('[PCloud SDK] Max retries reached for uploading chunk');
        return false;
    }

    /**
     * Finish an upload session and save the file
     * 
     * @param int $uploadId Upload session ID
     * @param string $filename Filename to save as on pCloud
     * @param int $folderId Target folder ID (0 = root)
     * @return int|false File ID on success, false on error
     */
    public function finishUpload($uploadId, $filename, $folderId = 0)
    {
        $filename = pathinfo($filename, PATHINFO_BASENAME);

        $params = array(
            'uploadid' => $uploadId,
            'folderid' => $folderId,
            'name' => $filename
        );

        $response = $this->request('upload_save', $params);

        if ($response === false || !isset($response['metadata']['fileid'])) {
            return false;
        }

        return $response['metadata']['fileid'];
    }

    /**
     * Upload a file directly to pCloud (for smaller files)
     * 
     * @param string $filePath Local file path
     * @param int $folderId Target folder ID (0 = root)
     * @return int|false File ID on success, false on error
     */
    public function uploadFile($filePath, $folderId = 0)
    {
        if (!file_exists($filePath)) {
            Logger::error('[PCloud SDK] File not found: ' . $filePath);
            return false;
        }

        $filename = pathinfo($filePath, PATHINFO_BASENAME);

        $stream = fopen($filePath, 'r');
        if (!$stream) {
            Logger::error('[PCloud SDK] Could not open file: ' . $filePath);
            return false;
        }

        $content = stream_get_contents($stream);
        fclose($stream);

        $params = array(
            'folderid' => $folderId,
            'filename' => $filename
        );

        $response = $this->request('uploadfile', $params, $content, 'PUT');

        if ($response === false || !isset($response['metadata'][0]['fileid'])) {
            return false;
        }

        return $response['metadata'][0]['fileid'];
    }


    /***************************************************
     * Account Operations
     ***************************************************/

    /**
     * Get pCloud account space usage information
     * 
     * @return array|false Space usage info array or false on error
     */
    public function getSpaceUsage()
    {
        return $this->request('userinfo');
    }
}
