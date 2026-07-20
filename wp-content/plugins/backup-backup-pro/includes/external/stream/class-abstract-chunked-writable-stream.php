<?php

declare(strict_types=1);

namespace BMI\Plugin\External\Stream;

use BatchZipStream\Contracts\WritableStreamInterface;
use BatchZipStream\Exceptions\WriteFailureException;
use BMI\Plugin\Progress\ProgressStorageInterface;
use BMI\Plugin\Progress\FileProgressStorage;
use BMI\Plugin\Services\IncrementalChainedHasher;

require_once BMI_INCLUDES . '/progress/class-file-progress-storage.php';
require_once BMI_INCLUDES . '/progress/interface-progress-storage.php';
require_once BMI_INCLUDES . '/services/class-incremental-chained-hasher.php';
require_once BMI_PRO_INC . 'vendor/BatchZipStream/Contracts/WritableStreamInterface.php';

/**
 * Abstract base for all chunked, resumable, hash-verified writable streams.
 *
 * ── Chunking  –––––––––––––––––––––––––––––––––––––––––––──────────────────
 *   When the buffer reaches $chunkSize (default 10 MB), exactly ONE chunk of
 *   $chunkSize bytes is hashed and dispatched to the destination.  Any
 *   overflow remains in the buffer and is processed on the next write() call
 *   that crosses the threshold.  A single write() call never dispatches more
 *   than one chunk.
 *
 * ── Subclass contract ──────────────────────────────────────────────────────
 *   Required:  uploadChunk(), completeUpload(), abortUpload()
 *   Optional:  onClose(), onFinalize(), onAbort()  (lifecycle hooks)
 */
abstract class AbstractChunkedWritableStream implements WritableStreamInterface
{
    // ── Constants ──────────────────────────────────────────────────────────

    /** Minimum upload chunk size: 10 MB. */
    public const MIN_UPLOAD_CHUNK_SIZE = 10485760;

    /** Minimum final-chunk threshold meaningful for multi-part APIs: 5 MB. */
    public const MIN_FINAL_CHUNK_SIZE = 5242880;

    /** Streaming read buffer size for hash and trim I/O passes. */
    private const IO_BUFFER = 65536; // 64 KB

    // ── Disk buffer ────────────────────────────────────────────────────────

    /** @var string Absolute path to the persistent disk buffer file. */
    private string $bufferFile;

    /** @var resource|null Append-mode write handle; null when closed. */
    private $bufferHandle = null;

    /** @var int Current byte size of the disk buffer file. */
    private int $bufferSize = 0;

    // ── Stream counters ────────────────────────────────────────────────────

    /** @var int Total bytes accepted by write(). */
    private int $bytesWritten = 0;

    /** @var int Stream cursor (always equals $bytesWritten). */
    private int $position = 0;

    /** @var int Total bytes confirmed dispatched to the destination. */
    private int $bytesFlushed = 0;

    /** @var int Byte size of the most recently dispatched chunk. */
    private int $lastChunkSize = 0;

    // ── Lifecycle flags ────────────────────────────────────────────────────

    /** @var bool True while the stream accepts writes. */
    private bool $isOpen = false;

    /** @var bool True after finalize() completes successfully. */
    private bool $isFinalized = false;

    /** @var bool True after abort() is called. */
    private bool $isAborted = false;

    // ── Hash state ─────────────────────────────────────────────────────────

    /** @var IncrementalChainedHasher Chained-hash service; owns the running-hash state. */
    private IncrementalChainedHasher $hasher;

    /** @var int Upload chunk size in bytes (independent of the hasher's chain-link size). */
    private int $chunkSize;

    // ── Persistence ────────────────────────────────────────────────────────

    /** @var ProgressStorageInterface Stores { running_hash, bytes_flushed, … } between batches. */
    private ProgressStorageInterface $baseState;

    // ── Subclass-accessible ────────────────────────────────────────────────

    /** @var string Hex fingerprint of the filename (md5). Available to subclasses. */
    protected string $stateKey;

    /** @var bool True when BMI_DEBUG is defined and truthy. Available to subclasses. */
    protected bool $debug;

    // ── Constructor ────────────────────────────────────────────────────────

    /**
     * @param string                        $filename  Archive name — used as the state-key seed.
     * @param int                           $chunkSize Upload boundary in bytes. Must be >= 10 MB.
     * @param int                           $hashChunkSize Hash chunk size in bytes.
     *                                                     Defaults to CHAINED_HASH_CHUNK_SIZE, used when hasher is null; ignored otherwise.
     * @param string                        $algorithm PHP hash() algorithm (default 'md5').
     *                                                 used when hasher is null; ignored otherwise.
     * @param IncrementalChainedHasher|null $hasher    Custom hasher instance; defaults to new IncrementalChainedHasher($algorithm).
     *                                                  Inject a hasher with a different $hashChunkSize to decouple hash
     *                                                  link granularity from the upload chunk size.
     * @param ProgressStorageInterface|null $baseState Custom state backend; defaults to FileProgressStorage.
     * @throws WriteFailureException If chunk size is below the minimum or buffer directory cannot be created.
     */
    public function __construct(
        string $filename,
        int $chunkSize = self::MIN_UPLOAD_CHUNK_SIZE,
        int $hashChunkSize = CHAINED_HASH_CHUNK_SIZE,
        string $algorithm = 'md5',
        ?IncrementalChainedHasher $hasher = null,
        ?ProgressStorageInterface $baseState = null
    ) {
        if ($chunkSize < self::MIN_UPLOAD_CHUNK_SIZE) {
            throw new WriteFailureException(sprintf(
                'Chunk size must be >= %d bytes; got %d.',
                self::MIN_UPLOAD_CHUNK_SIZE,
                $chunkSize
            ));
        }

        $this->stateKey  = md5($filename);
        $this->chunkSize = $chunkSize;
        $this->debug     = defined('BMI_DEBUG') && BMI_DEBUG;
        $this->hasher    = $hasher ?? new IncrementalChainedHasher($algorithm, $hashChunkSize);

        $this->baseState = $baseState
            ?? new FileProgressStorage(BMI_TMP, 'stream-base-' . $this->stateKey . '.json');

        $bufferDir = BMI_TMP . DIRECTORY_SEPARATOR . 'stream_buffer';
        if (!is_dir($bufferDir) && !mkdir($bufferDir, 0755, true)) {
            throw new WriteFailureException('Cannot create stream buffer directory: ' . $bufferDir);
        }

        $this->bufferFile = $bufferDir . DIRECTORY_SEPARATOR . 'buf-' . $this->stateKey . '.bmi';

        $this->restoreBaseState();
        $this->openBufferHandle();

        $this->isOpen = true;
    }

    // ── WritableStreamInterface ────────────────────────────────────────────

    /**
     * Write data directly to the disk buffer.
     *
     * Triggers exactly ONE chunk upload (Option B) when the buffer reaches
     * $chunkSize — never more than one dispatch per write() call.
     *
     * {@inheritDoc}
     */
    final public function write(string $data): int
    {
        if ($this->isFinalized) {
            throw new WriteFailureException('Cannot write to a finalised stream.');
        }

        if ($this->isAborted) {
            throw new WriteFailureException('Cannot write to an aborted stream.');
        }

        if (!$this->isOpen) {
            throw new WriteFailureException('Stream is not open.');
        }

        $length = strlen($data);
        if ($length === 0) {
            return 0;
        }

        if (fwrite($this->bufferHandle, $data) === false) {
            throw new WriteFailureException(
                'Failed to write data to stream buffer: ' . $this->bufferFile
            );
        }

        $this->bufferSize   += $length;
        $this->bytesWritten += $length;
        $this->position     += $length;

        $this->hasher->update($data);

        // Option B: fire at most ONE chunk upload per write() call.
        if ($this->bufferSize >= $this->chunkSize) {
            $this->processChunk();
        }

        return $length;
    }

    /**
     * No-op: every write() already commits data directly to disk.
     *
     * {@inheritDoc}
     */
    final public function flush(): void
    {
        if ($this->isAborted) {
            throw new WriteFailureException('Stream has been aborted.');
        }

        if (!$this->isOpen) {
            throw new WriteFailureException('Stream is not open.');
        }

        // All data is already on disk — nothing to flush.
    }

    /**
     * End of batch: close the write handle and persist state.
     * Does NOT upload remaining buffer or finalise the archive.
     *
     * {@inheritDoc}
     */
    final public function close(): void
    {
        if (!$this->isOpen) {
            return;
        }

        $this->closeBufferHandle();
        $this->saveBaseState();
        $this->isOpen = false;

        $this->onClose();
    }

    /**
     * Hash and upload all remaining buffered data, then complete the destination.
     *
     * {@inheritDoc}
     */
    final public function finalize(): void
    {
        if ($this->isFinalized) {
            return;
        }

        if ($this->isAborted) {
            throw new WriteFailureException('Cannot finalise an aborted stream.');
        }

        $this->closeBufferHandle();
        $this->hasher->finalize();

        if ($this->bufferSize > 0) {
            $this->processChunkFinal();
        }

        $this->completeUpload();
        $this->clearBaseState();
        $this->deleteBufferDir();

        $this->isFinalized = true;
        $this->isOpen      = false;

        $this->onFinalize();
    }

    /**
     * Discard all buffered data and state without completing the upload.
     *
     * {@inheritDoc}
     */
    final public function abort(): void
    {
        if ($this->isFinalized || $this->isAborted) {
            return;
        }

        $this->closeBufferHandle();
        $this->deleteBufferDir();
        $this->clearBaseState();

        $this->isAborted = true;
        $this->isOpen    = false;

        $this->abortUpload();
        $this->onAbort();
    }

    /** {@inheritDoc} */
    final public function isOpen(): bool
    {
        return $this->isOpen;
    }

    /** {@inheritDoc} */
    final public function getPosition(): int
    {
        return $this->position;
    }

    /** {@inheritDoc} */
    final public function getBytesWritten(): int
    {
        return $this->bytesWritten;
    }

    // ── Extended public API ────────────────────────────────────────────────

    /** @return bool True after finalize() completes. */
    final public function isFinalized(): bool
    {
        return $this->isFinalized;
    }

    /** @return bool True after abort() is called. */
    final public function isAborted(): bool
    {
        return $this->isAborted;
    }

    /** @return int Total bytes confirmed dispatched to the destination. */
    final public function getBytesFlushed(): int
    {
        return $this->bytesFlushed;
    }

    /** @return int Current disk buffer size in bytes (data not yet dispatched). */
    final public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    /** @return int Byte size of the most recently dispatched chunk. */
    final public function getLastChunkSize(): int
    {
        return $this->lastChunkSize;
    }

    /**
     * Current running chained-hash value, or the sealed final digest after finalize().
     * Returns an empty string before any full chunk has been processed (H₀ seed).
     *
     * @return string Hex-encoded digest.
     */
    final public function getHash(): string
    {
        return $this->hasher->getRunningHash();
    }

    /** @return string PHP hash() algorithm name in use (e.g. 'sha256'). */
    final public function getAlgorithm(): string
    {
        return $this->hasher->getAlgorithm();
    }

    // ── Abstract provider interface ────────────────────────────────────────

    /**
     * Dispatch one chunk of data to the destination.
     *
     * Called immediately after the chunk's hash has been computed and advanced.
     * For non-final chunks the payload is exactly $chunkSize bytes.
     * For the final chunk it is the remaining bytes (> 0, may be < $chunkSize).
     *
     * @param string $data      Binary chunk payload.
     * @param int    $startByte Byte offset of this chunk within the complete file.
     * @param int    $endByte   Last byte offset of this chunk (inclusive).
     * @param bool   $isFinal   True when this is the last chunk of the archive.
     * @throws WriteFailureException On unrecoverable dispatch failure.
     */
    abstract protected function uploadChunk(
        string $data,
        int $startByte,
        int $endByte,
        bool $isFinal
    ): void;

    /**
     * Signal that all chunks have been dispatched and the destination may finalise.
     * Called once by finalize() after the last chunk upload.
     *
     * @throws WriteFailureException On completion failure.
     */
    abstract protected function completeUpload(): void;

    /**
     * Release provider-side resources when the upload is aborted.
     * Called once by abort(). Should not throw.
     */
    abstract protected function abortUpload(): void;

    // ── Optional lifecycle hooks ───────────────────────────────────────────

    /**
     * Called at the end of close().
     * Override to persist provider-specific state between batches.
     */
    protected function onClose(): void {}

    /**
     * Called at the end of finalize().
     * Override to clean up provider-specific resources after completion.
     */
    protected function onFinalize(): void {}

    /**
     * Called at the end of abort().
     * Override to clean up provider-specific resources after abort.
     */
    protected function onAbort(): void {}

    // ── Private chunk processing ───────────────────────────────────────────

    /**
     * Hash exactly $chunkSize bytes from the buffer head, upload them, then
     * trim the buffer file to keep only the overflow.
     *
     * The running hash is restored to its pre-call value if the upload fails,
     * keeping state consistent for the next attempt.
     *
     * @throws WriteFailureException On hash read or upload failure.
     */
    private function processChunk(): void
    {
        $this->closeBufferHandle();

        try {
            $data      = $this->readSlice(0, $this->chunkSize);
            $startByte = $this->bytesFlushed;
            $endByte   = $startByte + $this->chunkSize - 1;

            $this->uploadChunk($data, $startByte, $endByte, false);
            unset($data);
        } catch (\Throwable $e) {
            $this->openBufferHandle();

            throw $e instanceof WriteFailureException
                ? $e
                : new WriteFailureException('Chunk upload failed: ' . $e->getMessage(), 0, $this->bytesWritten);
        }

        // Upload succeeded — trim buffer, advance counters, persist state.
        $this->trimBuffer($this->chunkSize);
        $this->bufferSize   -= $this->chunkSize;
        $this->bytesFlushed += $this->chunkSize;
        $this->lastChunkSize = $this->chunkSize;

        $this->saveBaseState();
        $this->openBufferHandle();
    }

    /**
     * Hash and upload the entire remaining buffer as the final chunk.
     * Called by finalize() when $bufferSize > 0.
     *
     * @throws WriteFailureException On hash read or upload failure.
     */
    private function processChunkFinal(): void
    {
        $remaining = $this->bufferSize;
        $data      = $this->readSlice(0, $remaining);
        $startByte = $this->bytesFlushed;
        $endByte   = $startByte + $remaining - 1;

        $this->uploadChunk($data, $startByte, $endByte, true);
        unset($data);

        $this->bytesFlushed  += $remaining;
        $this->lastChunkSize  = $remaining;
        $this->bufferSize     = 0;
    }

    // ── I/O helpers ────────────────────────────────────────────────────────

    /**
     * Open (or reopen) the buffer file in append mode.
     *
     * @throws WriteFailureException If the file handle cannot be obtained.
     */
    private function openBufferHandle(): void
    {
        $mode               = file_exists($this->bufferFile) ? 'ab' : 'wb';
        $this->bufferHandle = fopen($this->bufferFile, $mode);

        if ($this->bufferHandle === false) {
            $this->bufferHandle = null;
            throw new WriteFailureException(
                'Cannot open stream buffer file: ' . $this->bufferFile
            );
        }
    }

    /**
     * Close the write handle if open.
     */
    private function closeBufferHandle(): void
    {
        if ($this->bufferHandle !== null) {
            fclose($this->bufferHandle);
            $this->bufferHandle = null;
        }
    }

    /**
     * Read exactly $length bytes from the buffer file at $offset.
     *
     * @param int $offset Starting byte offset within the buffer file.
     * @param int $length Number of bytes to read.
     * @return string The raw bytes read.
     * @throws WriteFailureException On file open or read failure.
     */
    private function readSlice(int $offset, int $length): string
    {
        $handle = fopen($this->bufferFile, 'rb');
        if ($handle === false) {
            throw new WriteFailureException(
                'Cannot open buffer file for reading: ' . $this->bufferFile
            );
        }

        if ($offset > 0) {
            fseek($handle, $offset);
        }

        $data = fread($handle, $length);
        if ($data === false || strlen($data) !== $length) {
            fclose($handle);
            throw new WriteFailureException(
                'Failed to read expected bytes from buffer file: ' . $this->bufferFile
            );
        }

        fclose($handle);

        return $data;
    }

    /**
     * Remove the first $keepFrom bytes from the buffer file, keeping only the tail.
     * If $keepFrom >= $bufferSize the file is truncated to empty.
     *
     * Uses a temp-file + atomic rename to avoid data corruption on failure.
     *
     * @param int $keepFrom Number of leading bytes to discard.
     * @throws WriteFailureException On read or write failure during trim.
     */
    private function trimBuffer(int $keepFrom): void
    {
        if ($keepFrom <= 0 || !file_exists($this->bufferFile)) {
            return;
        }

        $tail = $this->bufferSize - $keepFrom;

        if ($tail <= 0) {
            // Nothing left to keep — truncate to empty.
            file_put_contents($this->bufferFile, '');
            return;
        }

        // Copy tail to a temp file, then rename atomically.
        $tmpFile     = $this->bufferFile . '.trim';
        $readHandle  = fopen($this->bufferFile, 'rb');
        $writeHandle = fopen($tmpFile, 'wb');

        if ($readHandle === false || $writeHandle === false) {
            if ($readHandle  !== false) fclose($readHandle);
            if ($writeHandle !== false) fclose($writeHandle);
            throw new WriteFailureException('Cannot open files for buffer trim operation.');
        }

        fseek($readHandle, $keepFrom);

        while (!feof($readHandle)) {
            $piece = fread($readHandle, self::IO_BUFFER);
            if ($piece === false || $piece === '') {
                break;
            }
            fwrite($writeHandle, $piece);
        }

        fclose($readHandle);
        fclose($writeHandle);

        rename($tmpFile, $this->bufferFile);
    }

    /**
     * Delete the disk buffer directory if it exists.
     */
    private function deleteBufferDir(): void
    {
        if (file_exists($this->bufferFile)) {
            @unlink($this->bufferFile);
        }
        @rmdir(dirname($this->bufferFile));
    }

    // ── State persistence ──────────────────────────────────────────────────

    /**
     * Persist running hash, bytes_flushed, algorithm, and chunk_size.
     * Called after every successful chunk upload and at close().
     */
    private function saveBaseState(): void
    {
        $this->baseState->save([
            'bytes_flushed' => $this->bytesFlushed,
            'chunk_size'    => $this->chunkSize,
            'algorithm'     => $this->hasher->getAlgorithm(),
            'running_hash'  => $this->hasher->getRunningHash(),
            'pending'       => base64_encode($this->hasher->getPending()),
        ]);
    }

    /**
     * Restore running hash and bytes_flushed from the last saved state.
     * Buffer size is derived from the actual file on disk (authoritative source).
     */
    private function restoreBaseState(): void
    {
        if ($this->baseState->exists()) {
            $saved = $this->baseState->load();
            if (is_array($saved)) {
                $this->bytesFlushed = (int)    ($saved['bytes_flushed'] ?? 0);
                $runningHash        = (string) ($saved['running_hash']  ?? '');
                $pending            = base64_decode((string) ($saved['pending'] ?? ''), true) ?: '';
                $this->hasher->setState($runningHash, $pending);
            }
        }

        // Buffer file is the authoritative source for unprocessed data.
        $this->bufferSize = file_exists($this->bufferFile)
            ? (int) filesize($this->bufferFile)
            : 0;

        // Logical counters are derived — no need to persist them separately.
        $this->bytesWritten = $this->bytesFlushed + $this->bufferSize;
        $this->position     = $this->bytesWritten;
    }

    /**
     * Remove the base state JSON file.
     */
    private function clearBaseState(): void
    {
        $this->baseState->clear();
    }
}
