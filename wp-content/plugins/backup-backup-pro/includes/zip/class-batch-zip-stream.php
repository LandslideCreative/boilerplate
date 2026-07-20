<?php

declare(strict_types=1);

namespace BMI\Plugin\Pro\Zip;

/**
 * BMIZipStream - Cloud-Streaming ZIP Archive Wrapper
 *
 * This class provides a unified abstraction over the BatchZipStream library,
 * enabling direct cloud streaming during backup operations. It dynamically
 * resolves the appropriate WritableStream based on configuration.
 * 
 * Design Principles:
 * - Zero coupling between main codebase and BatchZipStream library
 * - Provider-agnostic architecture for future extensibility
 * - Feature-flagged activation with safe fallback
 * - Minimal integration footprint
 * 
 * Usage Scope:
 * - ONLY instantiate in backup-process.php
 * - Do NOT use BatchZipSession directly anywhere else
 * 
 *
 * @package BMI\Plugin\Pro\Zip
 */

use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\BMI_Logger as Logger;
use BatchZipStream\BatchZipSession;
use BatchZipStream\BatchZipWriter;
use BatchZipStream\Contracts\WritableStreamInterface;
use BatchZipStream\Contracts\ReadableStreamInterface;
use BatchZipStream\Contracts\StatePersistenceInterface;
use BatchZipStream\Core\ZipFormat;
use BatchZipStream\Streams\FileReadableStream;
use BatchZipStream\Exceptions\BatchZipStreamException;
use BMI\Plugin\External\Stream\WritableStreamFactoryInterface;
use BMI\Plugin\External\Stream\BackupBlissWritableStreamFactory;
use BMI\Plugin\External\Stream\BMIZipStreamProvider;
use BMI\Plugin\External\Stream\LocalWritableStreamFactory;
use BMI\Plugin\External\Stream\AbstractChunkedWritableStream;
use BMI\Plugin\External\BMI_External_Storage_Manager as ExternalStorageManager;
use BMI\Plugin\BMI_BackupMethodManager as MethodManager;
use BMI\Plugin\Services\BackupLifecycleManager as BackupLifecycleManager;


// Require BatchZipStream autoloader
require_once BMI_PRO_INC . 'vendor/BatchZipStream/autoload.php';
require_once BMI_PRO_INC . 'external/stream/autoload.php';

/**
 * BMIZipStream - Main wrapper over BatchZipStream library.
 * 
 * This class provides the only sanctioned interface for interacting with
 * BatchZipStream in the plugin codebase. All cloud-streaming ZIP operations
 * should go through this class.
 * 
 * 
 * Example Usage:
 * ```php
 * // Check if cloud streaming is enabled
 * if (BMIZipStream::isEnabled()) {
 *     $zipStream = new BMIZipStream($stateDir, 'backup.zip');
 *     $zipStream->startSession('my-backup-session');
 *     $zipStream->addFile('wordpress/file.txt', '/path/to/file.txt');
 *     $zipStream->saveProgress();
 *     // ... in next batch ...
 *     $zipStream->finalize();
 * }
 * ```
 */
class BMIZipStream
{
    /** @var string Configuration key for feature flag */
    private const CONFIG_ENABLED = 'STREAM:DIRECT_CLOUD_STREAMING:ENABLED';

    /** @var string Configuration key for provider */
    private const CONFIG_PROVIDER = 'STREAM:DIRECT_CLOUD_STREAMING:PROVIDER';

    /** @var BatchZipSession|null Internal session manager */
    private ?BatchZipSession $session = null;

    /** @var WritableStreamInterface|null The writable stream */
    private ?WritableStreamInterface $stream = null;

    /** @var string|null Current session ID */
    private ?string $sessionId = null;

    /** @var string State directory for persistence */
    private string $stateDirectory;

    /** @var string Target filename */
    private string $filename;

    /** @var string Provider identifier */
    private string $provider;

    /** @var int Compression level (0-9) */
    private int $compressionLevel;

    /** @var int Read-buffer size passed to BatchZipSession (ZIP file reading), in bytes. */
    private int $readChunkSize;

    /** @var int Upload chunk size for the underlying WritableStream (must be >= 10 MB). */
    private int $uploadChunkSize;

    /** @var bool Debug mode */
    private bool $debug;

    /** @var WritableStreamFactoryInterface|null Custom stream factory */
    private ?WritableStreamFactoryInterface $streamFactory = null;

    /** @var StatePersistenceInterface|null Custom state persistence */
    private ?StatePersistenceInterface $statePersistence = null;

    /** @var array<string, WritableStreamFactoryInterface> Stream factory registry */
    private static array $factoryRegistry = [];

    /**
     * Create a new BMIZipStream instance.
     *
     * @param string $stateDirectory  Directory for session state persistence.
     * @param string $filename        Target filename for the ZIP archive.
     * @param int    $compressionLevel Deflate compression level (0–9).
     * @param int    $readChunkSize   Read-buffer size for ZIP file reading (bytes).
     * @throws BMIZipStreamException If provider is not supported or not implemented.
     */
    public function __construct(
        string $stateDirectory,
        string $filename,
        int $compressionLevel = 6,
        int $readChunkSize = 65536
    ) {
        $this->stateDirectory  = $stateDirectory;
        $this->filename        = $filename;
        $this->compressionLevel = $compressionLevel;
        $this->readChunkSize   = $readChunkSize;
        $this->uploadChunkSize = 10485760; // 10 MB default
        $this->debug = defined('BMI_DEBUG') && BMI_DEBUG;

        // Resolve provider from configuration
        $this->provider = $this->resolveProvider();

        // Validate provider
        if (!BMIZipStreamProvider::isSupported($this->provider)) {
            throw new BMIZipStreamException(
                sprintf('Unsupported provider: %s', $this->provider),
                'UNSUPPORTED_PROVIDER'
            );
        }

        if (!BMIZipStreamProvider::isImplemented($this->provider)) {
            throw new BMIZipStreamException(
                sprintf('Provider not yet implemented: %s', $this->provider),
                'PROVIDER_NOT_IMPLEMENTED'
            );
        }

        if ($this->debug) {
            Logger::log(sprintf(
                '[BMIZipStream] Initialized with provider: %s, filename: %s',
                $this->provider,
                $this->filename
            ));
        }
    }

    /**
     * Check if direct cloud streaming feature is enabled.
     * 
     * @return bool True if the feature flag is enabled
     */
    public static function isEnabled()
    {
        return Dashboard\bmi_get_config(self::CONFIG_ENABLED) === true;
    }

    public static function canStream()
    {
        if (!self::isEnabled()) {
            return false;
        }

        $provider = self::getConfiguredProvider();
        require_once BMI_INCLUDES . '/external/external-storage-manager.php';
        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'class-backup-method-mananger.php';
        require_once BMI_PRO_INC . 'services/class-bmi-pro-backup-lifecycle-manager.php';

        $backupMethodManager = new MethodManager();
        $currentMethod = $backupMethodManager->currentMethod();

        $backupLifecycleManager = BackupLifecycleManager::getInstance();
        $currentStoreStrategy = $backupLifecycleManager->getConfiguredOption();

        $isProviderConfigured = ExternalStorageManager::getInstance()->isStorageConfigured($provider) || $provider === BMIZipStreamProvider::LOCAL;
        return $isProviderConfigured && BMIZipStreamProvider::isSupported($provider) && BMIZipStreamProvider::isImplemented($provider) && $currentMethod !== BMI_METHOD_DEFAULT && $currentStoreStrategy === BackupLifecycleManager::STORAGE_STRATEGY_CLOUD_ONLY;
    }
    /**
     * Get the configured provider.
     * 
     * @return string Provider identifier
     */
    public static function getConfiguredProvider()
    {
        $provider = Dashboard\bmi_get_config(self::CONFIG_PROVIDER);
        return is_string($provider) && !empty($provider)
            ? strtolower($provider)
            : BMIZipStreamProvider::BACKUPBLISS; // Default provider
    }

    /**
     * Register a custom stream factory for a provider.
     * 
     * This allows extending BMIZipStream with new providers without
     * modifying this class.
     * 
     * @param string $provider Provider identifier
     * @param WritableStreamFactoryInterface $factory Factory instance
     */
    public static function registerStreamFactory(
        $provider,
        $factory
    ) {
        self::$factoryRegistry[$provider] = $factory;
    }

    /**
     * Set a custom stream factory for this instance.
     * 
     * @param WritableStreamFactoryInterface $factory Factory instance
     * @return self For chaining
     */
    public function setStreamFactory($factory)
    {
        $this->streamFactory = $factory;
        return $this;
    }

    /**
     * Set a custom state persistence for this instance.
     * 
     * @param StatePersistenceInterface $persistence Persistence instance
     * @return self For chaining
     */
    public function setStatePersistence($persistence)
    {
        $this->statePersistence = $persistence;
        return $this;
    }

    /**
     * Start or resume a ZIP session.
     * 
     * @param string $sessionId Unique session identifier
     * @param int $lockTimeout Maximum time to wait for session lock
     * @return string The session ID
     * @throws BMIZipStreamException On session start failure
     */
    public function startSession($sessionId, $lockTimeout = 30)
    {
        $this->sessionId = $sessionId;

        try {
            // Create the writable stream
            $this->stream = $this->createWritableStream();

            // Create the BatchZipSession with stream
            if ($this->statePersistence !== null) {
                $this->session = BatchZipSession::withStream(
                    $this->statePersistence,
                    $this->stream,
                    $this->compressionLevel,
                    $this->readChunkSize
                );
            } else {
                $this->session = BatchZipSession::withStream(
                    $this->stateDirectory,
                    $this->stream,
                    $this->compressionLevel,
                    $this->readChunkSize
                );
            }

            // Start or resume the session
            $this->session->startSession($sessionId, $lockTimeout);

            if ($this->debug) {
                Logger::log(sprintf(
                    '[BMIZipStream] Session started: %s',
                    $sessionId
                ));
            }

            return $sessionId;

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to start session: %s', $e->getMessage()),
                'SESSION_START_FAILED',
                $e
            );
        }
    }

    /**
     * Add a file or an empty directory to the archive.
     *
     * Convenience wrapper that dispatches to addFile() or addEmptyDirectory()
     * based on whether $sourcePath is a directory.
     *
     * @param string   $archivePath       Path inside the ZIP archive.
     * @param string   $sourcePath        Local path to the file or directory.
     * @param int|null $compressionMethod Compression method (null = DEFLATE, files only).
     * @return self For chaining.
     * @throws BMIZipStreamException On add failure.
     */
    public function addPath(
        string $archivePath,
        string $sourcePath,
        ?int $compressionMethod = null
    ): self {
        if (is_dir($sourcePath)) {
            return $this->addEmptyDirectory($archivePath);
        }

        return $this->addFile($archivePath, $sourcePath, $compressionMethod);
    }

    /**
     * Add a file to the archive from a file path.
     * 
     * @param string $archivePath Path in the ZIP archive
     * @param string $sourcePath Path to the source file
     * @param int|null $compressionMethod Compression method (null = DEFLATE)
     * @return self For chaining
     * @throws BMIZipStreamException On add failure
     */
    public function addFile(
        $archivePath,
        $sourcePath,
        $compressionMethod = null
    ) {
        $this->ensureSessionStarted();

        $compressionMethod ??= ZipFormat::COMPRESSION_DEFLATE;

        try {
            $this->session->addFile($archivePath, $sourcePath, $compressionMethod);

            return $this;

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to add file %s: %s', $archivePath, $e->getMessage()),
                'ADD_FILE_FAILED',
                $e
            );
        }
    }

    /**
     * Add a file from string content.
     * 
     * @param string $archivePath Path in the ZIP archive
     * @param string $content File content
     * @param int|null $compressionMethod Compression method (null = DEFLATE)
     * @return self For chaining
     * @throws BMIZipStreamException On add failure
     */
    public function addFileFromString(
        $archivePath,
        $content,
        $compressionMethod = null
    ) {
        $this->ensureSessionStarted();

        $compressionMethod ??= ZipFormat::COMPRESSION_DEFLATE;

        try {
            $this->session->addFileFromString($archivePath, $content, $compressionMethod);

            return $this;

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to add file from string %s: %s', $archivePath, $e->getMessage()),
                'ADD_FILE_FROM_STRING_FAILED',
                $e
            );
        }
    }

    /**
     * Add a file from a readable stream.
     * 
     * @param string $archivePath Path in the ZIP archive
     * @param ReadableStreamInterface $source Source stream
     * @param int|null $compressionMethod Compression method (null = DEFLATE)
     * @param int|null $modificationTime Unix timestamp
     * @return self For chaining
     * @throws BMIZipStreamException On add failure
     */
    public function addFileFromStream(
        $archivePath,
        $source,
        $compressionMethod = null,
        $modificationTime = null
    ) {
        $this->ensureSessionStarted();

        $compressionMethod ??= ZipFormat::COMPRESSION_DEFLATE;

        try {
            $this->session->addFileFromStream(
                $archivePath,
                $source,
                $compressionMethod,
                $modificationTime
            );

            return $this;

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to add file from stream %s: %s', $archivePath, $e->getMessage()),
                'ADD_FILE_FROM_STREAM_FAILED',
                $e
            );
        }
    }

    /**
     * Add an empty directory to the archive.
     * 
     * @param string $archivePath Directory path in the archive
     * @param int|null $modificationTime Unix timestamp
     * @return self For chaining
     * @throws BMIZipStreamException On add failure
     */
    public function addEmptyDirectory(
        $archivePath,
        $modificationTime = null
    ) {
        $this->ensureSessionStarted();

        try {
            $this->session->addEmptyDirectory($archivePath, $modificationTime);

            return $this;

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to add directory %s: %s', $archivePath, $e->getMessage()),
                'ADD_DIRECTORY_FAILED',
                $e
            );
        }
    }

    /**
     * Save current progress.
     * 
     * Call this at the end of each batch to persist state.
     * 
     * @throws BMIZipStreamException On save failure
     */
    public function saveProgress()
    {
        $this->ensureSessionStarted();

        try {
            $this->session->saveProgress();

            if ($this->debug) {
                Logger::log('[BMIZipStream] Progress saved');
            }

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to save progress: %s', $e->getMessage()),
                'SAVE_PROGRESS_FAILED',
                $e
            );
        }
    }

    /**
     * Finalize the archive.
     * 
     * Writes the Central Directory and completes the ZIP.
     * Also finalizes the stream (completes cloud upload).
     * 
     * @param string $comment Optional archive comment
     * @throws BMIZipStreamException On finalization failure
     */
    public function finalize($comment = '')
    {
        $this->ensureSessionStarted();

        try {
            $this->session->finalize($comment);

            if ($this->debug) {
                Logger::log('[BMIZipStream] Archive finalized');
            }

        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to finalize archive: %s', $e->getMessage()),
                'FINALIZE_FAILED',
                $e
            );
        }
    }

    /**
     * Abort the session and clean up.
     * 
     * @param string $reason Abort reason
     * @param bool $deleteArchive Whether to delete partial archive
     */
    public function abort($reason = 'Aborted by user', $deleteArchive = true)
    {
        if ($this->session === null) {
            return;
        }

        try {
            $this->session->abort($reason, $deleteArchive);

            if ($this->debug) {
                Logger::log(sprintf('[BMIZipStream] Session aborted: %s', $reason));
            }

        } catch (\Throwable $e) {
            // Log but don't throw during abort
            if ($this->debug) {
                Logger::log(sprintf('[BMIZipStream] Error during abort: %s', $e->getMessage()));
            }
        }

        $this->session = null;
        $this->stream = null;
    }

    /**
     * Close the session without cleanup.
     * 
     * Use this to end a batch while preserving state for resumption.
     */
    public function close()
    {
        if ($this->session !== null) {
            try {
                $this->session->close();
            } catch (\Throwable $e) {
                if ($this->debug) {
                    Logger::log(sprintf('[BMIZipStream] Error during close: %s', $e->getMessage()));
                }
            }
        }

        $this->session = null;
    }

    /**
     * Get session statistics.
     * 
     * @return array|null Session stats or null if no session
     */
    public function getStats()
    {
        if ($this->session === null) {
            return null;
        }

        return $this->session->getStats();
    }

    /**
     * Check if a session exists.
     * 
     * @param string $sessionId Session identifier
     * @return bool
     */
    public function sessionExists($sessionId)
    {
        if ($this->session === null) {
            return false;
        }

        return $this->session->exists($sessionId);
    }

    /**
     * Get the current provider.
     * 
     * @return string Provider identifier
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Get the target filename.
     * 
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get the current session ID.
     * 
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Get the underlying BatchZipSession.
     * 
     * WARNING: Direct access to the session should be avoided.
     * Use the wrapper methods instead.
     * 
     * @return BatchZipSession|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get the underlying WritableStream.
     * 
     * @return WritableStreamInterface|null
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Get the final content-addressable hash of the archive.
     *
     * Returns the chained hash computed over all chunks written so far.
     * After finalize() this is the sealed digest of the complete archive.
     * Returns an empty string if no data has been processed yet or if the
     * stream does not support hashing.
     *
     * @return string Hex-encoded digest (e.g. md5), or '' if unavailable.
     */
    public function getHash(): string
    {
        if ($this->stream instanceof AbstractChunkedWritableStream) {
            return $this->stream->getHash();
        }

        return '';
    }

    /**
     * Get the hash algorithm used by the underlying stream.
     *
     * @return string Algorithm name (e.g. 'md5'), or '' if unavailable.
     */
    public function getHashAlgorithm(): string
    {
        if ($this->stream instanceof AbstractChunkedWritableStream) {
            return $this->stream->getAlgorithm();
        }

        return '';
    }

    /**
     * Upload the manifest via the underlying stream's upload mechanism.
     *
     * This is used for the backup manifest in the cloud streaming flow, allowing the manifest to be stored alongside the archive in the cloud provider, and avoiding the need for a local copy.
     * 
     * @param string $manifestContent The content of the manifest to upload.
    **/
    public function streamManifest(string $manifestContent): void
    {
        try{
            // resolve new stream instance
            $manifestStream = $this->createManifestStream();
        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to create manifest stream: %s', $e->getMessage()),
                'MANIFEST_STREAM_CREATION_FAILED',
                $e
            );
        }

        try {
            // Write manifest content to the stream
            $manifestStream->write($manifestContent);
            $manifestStream->finalize();
            if ($this->debug) {
                Logger::log('[BMIZipStream] Manifest uploaded via stream');
            }
        } catch (BatchZipStreamException $e) {
            throw new BMIZipStreamException(
                sprintf('Failed to upload manifest: %s', $e->getMessage()),
                'MANIFEST_UPLOAD_FAILED',
                $e
            );
        }

    }

    /**
     * Create a writable stream for the manifest upload.
     *
     * This uses the same provider and configuration as the main archive stream, but can be customized if needed (e.g. different chunk size).
     * 
     * @return WritableStreamInterface
     * @throws BMIZipStreamException On creation failure
     */
    private function createManifestStream(): WritableStreamInterface
    {
        if ($this->streamFactory !== null) {
            return $this->streamFactory->create($this->getHash() . '.json');
        }

        if (isset(self::$factoryRegistry[$this->provider])) {
            return self::$factoryRegistry[$this->provider]->create($this->getHash() . '.json');
        }

        $factory = $this->getBuiltInFactory();
        return $factory->create($this->getHash() . '.json');
    }



    /**
     * Resolve the provider from configuration.
     * 
     * @return string Provider identifier
     */
    private function resolveProvider()
    {
        return self::getConfiguredProvider();
    }

    /**
     * Create the writable stream for the current provider.
     * 
     * @return WritableStreamInterface
     * @throws BMIZipStreamException On creation failure
     */
    private function createWritableStream(): WritableStreamInterface
    {
        $options = ['chunk_size' => $this->uploadChunkSize];

        // Use custom factory if provided
        if ($this->streamFactory !== null) {
            return $this->streamFactory->create($this->filename, $options);
        }

        // Check registered factories
        if (isset(self::$factoryRegistry[$this->provider])) {
            return self::$factoryRegistry[$this->provider]->create($this->filename, $options);
        }

        // Use built-in factory for known providers
        $factory = $this->getBuiltInFactory();
        return $factory->create($this->filename, $options);
    }

    /**
     * Get the built-in factory for the current provider.
     * 
     * @return WritableStreamFactoryInterface
     * @throws BMIZipStreamException If no factory available
     */
    private function getBuiltInFactory()
    {
        switch ($this->provider) {
            case BMIZipStreamProvider::LOCAL:
                return new LocalWritableStreamFactory();
            case BMIZipStreamProvider::BACKUPBLISS:
                return new BackupBlissWritableStreamFactory();

            // Future providers would be added here:
            // case BMIZipStreamProvider::GDRIVE:
            //     return new GDriveWritableStreamFactory();
            // case BMIZipStreamProvider::DROPBOX:
            //     return new DropboxWritableStreamFactory();
            // etc.

            default:
                throw new BMIZipStreamException(
                    sprintf('No factory available for provider: %s', $this->provider),
                    'NO_FACTORY_AVAILABLE'
                );
        }
    }

    /**
     * Ensure a session has been started.
     * 
     * @throws BMIZipStreamException If no session is active
     */
    private function ensureSessionStarted()
    {
        if ($this->session === null) {
            throw new BMIZipStreamException(
                'No active session. Call startSession() first.',
                'NO_ACTIVE_SESSION'
            );
        }
    }

    /**
     * Destructor - ensure resources are released.
     */
    public function __destruct()
    {
        $this->close();
    }
}
