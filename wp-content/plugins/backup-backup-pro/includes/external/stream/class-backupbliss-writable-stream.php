<?php

declare(strict_types=1);

namespace BMI\Plugin\External\Stream;

use BatchZipStream\Exceptions\WriteFailureException;
use BMI\Plugin\External\BMI_External_BackupBliss;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Progress\ProgressStorageInterface;
use BMI\Plugin\Progress\FileProgressStorage;

require_once BMI_INCLUDES . '/progress/class-file-progress-storage.php';
require_once BMI_INCLUDES . '/progress/interface-progress-storage.php';
require_once BMI_INCLUDES . '/external/backupbliss.php';

/**
 * BackupBliss cloud storage stream.
 *
 * Extends AbstractChunkedWritableStream and delegates all buffer management,
 * chunking, and hash computation to the base class.  This class is solely
 * responsible for communicating with the BackupBliss upload API.
 *
 *
 * BackupBliss-specific behaviour:
 * - Lazy-initialised multi-part upload session.
 * - Per-chunk retry with exponential back-off.
 * - completeUpload() is skipped when the final chunk is < 5 MB (BackupBliss auto-completes such sessions).
 */
class BackupBlissWritableStream extends AbstractChunkedWritableStream
{
    /** @var BMI_External_BackupBliss BackupBliss API client. */
    private BMI_External_BackupBliss $backupBliss;

    /** @var string Remote filename for the archive. */
    private string $filename;

    /** @var array|null Active upload session data (contains 'upload_id'). */
    private ?array $uploadSession = null;

    /** @var int Maximum upload retry attempts per chunk. */
    private int $maxRetries;

    /** @var ProgressStorageInterface BackupBliss-specific state (upload session). */
    private ProgressStorageInterface $bbState;



    /**
     * @param string                        $filename   Remote filename for the ZIP archive.
     * @param BMI_External_BackupBliss      $backupBliss BackupBliss API client instance.
     * @param int                           $chunkSize  Chunk size in bytes (must be >= 10 MB).
     * @param int                           $maxRetries Maximum upload retries per chunk.
     * @param ProgressStorageInterface|null $state      Inject custom BackupBliss state storage.
     * @throws WriteFailureException If chunk size is below the minimum.
     */
    public function __construct(
        string $filename,
        BMI_External_BackupBliss $backupBliss,
        int $chunkSize = self::MIN_UPLOAD_CHUNK_SIZE,
        int $maxRetries = 3,
        ?ProgressStorageInterface $state = null
    ) {
        $this->filename    = $filename;
        $this->backupBliss = $backupBliss;
        $this->maxRetries  = $maxRetries;
        $this->bbState     = $state
            ?? new FileProgressStorage(BMI_TMP, 'backupbliss-bb-' . md5($filename) . '.json');

        // Parent constructor handles buffer init, state restore, and opens the stream.
        parent::__construct($filename, $chunkSize);

        // Restore BackupBliss-specific state (upload session ID) from previous batch.
        $this->restoreBBState();

        if ($this->debug) {
            Logger::log('[BackupBlissStream] Initialised: ' . $filename . ', chunk: ' . $chunkSize);
            if ($this->uploadSession !== null) {
                Logger::log('[BackupBlissStream] Resumed session: ' . ($this->uploadSession['upload_id'] ?? 'unknown'));
            }
        }
    }

    // ── AbstractChunkedWritableStream ──────────────────────────────────────

    /**
     * Upload one chunk to BackupBliss with exponential back-off retry.
     *
     * {@inheritDoc}
     */
    protected function uploadChunk(
        string $data,
        int $startByte,
        int $endByte,
        bool $isFinal
    ): void {
        if ($this->uploadSession === null) {
            $this->initUploadSession();
        }

        $length    = strlen($data);
        $attempt   = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->backupBliss->uploadChunkWithSession(
                    $this->uploadSession,
                    $data,
                    $startByte,
                    $endByte,
                    $this->getBytesWritten()
                );

                if ($response['status'] ?? false) {
                    if ($this->debug) {
                        Logger::log(sprintf(
                            '[BackupBlissStream] Chunk OK: bytes %d–%d (%d B)%s',
                            $startByte,
                            $endByte,
                            $length,
                            $isFinal ? ' [FINAL]' : ''
                        ));
                    }
                    set_transient('bmi_zip_upload_cooldown', true, 5);

                    return;
                }

                $lastError = $response['message'] ?? 'Unknown error';
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                if ($response['retry_after'] ?? null) {
                    $retryAfter = (int) $response['retry_after'];
                    if ($this->debug) {
                        Logger::log("[BackupBlissStream] Retry attempt $attempt after $retryAfter seconds due to error: $lastError");
                    }
                    sleep($retryAfter);
                } else {
                    if ($this->debug) {
                        Logger::log("[BackupBlissStream] Retry attempt $attempt after default back-off due to error: $lastError");
                    }
                    sleep((int) pow(2, $attempt - 1));
                }
            }
        }

        throw new WriteFailureException(sprintf(
            'BackupBliss upload failed after %d attempts (range %d–%d): %s',
            $this->maxRetries,
            $startByte,
            $endByte,
            $lastError ?? 'Unknown'
        ));
    }

    /**
     * Complete the BackupBliss multi-part upload session.
     *
     * Skipped when the final chunk is < 5 MB — BackupBliss auto-completes
     * such sessions without an explicit complete call.
     *
     * {@inheritDoc}
     */
    protected function completeUpload(): void
    {
        if ($this->uploadSession === null) {
            return; // No data was ever uploaded.
        }

        if ($this->getLastChunkSize() < self::MIN_FINAL_CHUNK_SIZE) {
            if ($this->debug) {
                Logger::log('[BackupBlissStream] Final chunk < 5 MB — BackupBliss auto-completes.');
            }

            return;
        }

        if ($this->debug) {
            Logger::log('[BackupBlissStream] Completing session: ' . $this->uploadSession['upload_id']);
        }

        $this->backupBliss->completeUpload($this->uploadSession['upload_id']);
    }

    /**
     * Discard BackupBliss-specific state on abort.
     * The upload session expires server-side; no explicit abort API call needed.
     *
     * {@inheritDoc}
     */
    protected function abortUpload(): void
    {
        $this->bbState->clear();
        $this->uploadSession = null;
    }

    /**
     * Persist the BackupBliss upload session at the end of each batch.
     *
     * {@inheritDoc}
     */
    protected function onClose(): void
    {
        $this->saveBBState();
    }

    /**
     * Clear BackupBliss-specific state after successful finalisation.
     *
     * {@inheritDoc}
     */
    protected function onFinalize(): void
    {
        $this->bbState->clear();
        $this->uploadSession = null;
    }

    // ── BackupBliss session management ─────────────────────────────────────

    /**
     * Lazily initialise the BackupBliss multi-part upload session.
     *
     * @throws WriteFailureException If session initialisation fails.
     */
    private function initUploadSession(): void
    {
        try {
            $this->uploadSession = $this->backupBliss->initiateUploadSession($this->filename);

            if (!$this->uploadSession || !isset($this->uploadSession['upload_id'])) {
                throw new WriteFailureException(
                    'Invalid upload session response from BackupBliss for: ' . $this->filename
                );
            }

            // Persist the session ID immediately so a mid-batch crash is recoverable.
            $this->saveBBState();

            if ($this->debug) {
                Logger::log('[BackupBlissStream] Session created: ' . $this->uploadSession['upload_id']);
            }
        } catch (WriteFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WriteFailureException(
                'Failed to init BackupBliss upload session: ' . $e->getMessage()
            );
        }
    }

    /**
     * Persist the upload session ID for cross-batch resumability.
     */
    private function saveBBState(): void
    {
        $this->bbState->save([
            'filename'       => $this->filename,
            'upload_session' => $this->uploadSession,
        ]);
    }

    /**
     * Restore the upload session from a previous batch's saved state.
     */
    private function restoreBBState(): void
    {
        if (!$this->bbState->exists()) {
            return;
        }

        $saved = $this->bbState->load();
        if (!is_array($saved)) {
            return;
        }

        if (($saved['filename'] ?? '') !== $this->filename) {
            if ($this->debug) {
                Logger::log('[BackupBlissStream] BB state filename mismatch — ignoring.');
            }

            return;
        }

        if (isset($saved['upload_session']) && is_array($saved['upload_session'])) {
            $this->uploadSession = $saved['upload_session'];
        }
    }

    // ── Additional public API ──────────────────────────────────────────────

    /** @return string|null Active BackupBliss upload session ID, or null. */
    public function getUploadSessionId(): ?string
    {
        return $this->uploadSession['upload_id'] ?? null;
    }

    /** @return string Remote filename. */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /** @return bool True if a saved BackupBliss state file exists. */
    public function hasState(): bool
    {
        return $this->bbState->exists();
    }

    /**
     * Destructor — ensure the stream is cleanly closed if not already.
     * Does NOT finalise — that must be called explicitly.
     */
    public function __destruct()
    {
        if ($this->isOpen() && !$this->isFinalized() && !$this->isAborted()) {
            try {
                $this->close();
            } catch (\Exception $e) {
                if ($this->debug) {
                    Logger::error('[BackupBlissStream] Error in destructor close: ' . $e->getMessage());
                }
            }
        }
    }
}
