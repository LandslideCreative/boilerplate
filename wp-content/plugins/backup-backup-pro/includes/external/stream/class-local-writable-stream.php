<?php

declare(strict_types=1);

namespace BMI\Plugin\External\Stream;

use BatchZipStream\Exceptions\WriteFailureException;

/**
 * Local-filesystem implementation of AbstractChunkedWritableStream.
 *
 * Writes ZIP archive data to a file on the local filesystem using the same
 * chunked, hash-verified, resumable pipeline as cloud stream providers.
 *
 */
class LocalWritableStream extends AbstractChunkedWritableStream
{
    /** @var string Absolute path to the output archive file. */
    private string $targetFile;

/** @var resource|null Write handle for the output archive (kept open across chunks). */
    private $targetHandle = null;

    /**
     * @param string $targetFile Absolute path to the output archive (e.g. BMI_BACKUPS . '/backup.zip').
     * @param int    $chunkSize  Upload boundary in bytes (default 10 MB).
     * @throws WriteFailureException If chunk size is below the minimum.
     */
    public function __construct(
        string $targetFile,
        int $chunkSize = self::MIN_UPLOAD_CHUNK_SIZE
    ) {
        $this->targetFile = $targetFile;
        parent::__construct(basename($targetFile), $chunkSize);
    }

    // ── AbstractChunkedWritableStream ──────────────────────────────────────

    /**
     * Append a chunk to the output archive file.
     *
     * Opens the file in write mode ('wb') when startByte === 0 so a fresh
     * start always truncates any existing partial file.  Subsequent chunks
     * are appended through the handle opened on the first call.
     *
     * {@inheritDoc}
     */
    protected function uploadChunk(
        string $data,
        int $startByte,
        int $endByte,
        bool $isFinal
    ): void {
        if ($this->targetHandle === null) {
            $mode               = ($startByte === 0) ? 'wb' : 'ab';
            $this->targetHandle = fopen($this->targetFile, $mode);

            if ($this->targetHandle === false) {
                $this->targetHandle = null;
                throw new WriteFailureException(
                    'Cannot open target archive file: ' . $this->targetFile
                );
            }
        }

        if (fwrite($this->targetHandle, $data) === false) {
            throw new WriteFailureException(
                'Failed to write chunk to target archive: ' . $this->targetFile
            );
        }
    }

    /**
     * Close the output archive file handle.
     *
     * {@inheritDoc}
     */
    protected function completeUpload(): void
    {
        if ($this->targetHandle !== null) {
            fclose($this->targetHandle);
            $this->targetHandle = null;
        }
    }

    /**
     * Close the handle and delete the partial archive on abort.
     *
     * {@inheritDoc}
     */
    protected function abortUpload(): void
    {
        if ($this->targetHandle !== null) {
            fclose($this->targetHandle);
            $this->targetHandle = null;
        }

        if (file_exists($this->targetFile)) {
            @unlink($this->targetFile);
        }
    }

    // ── Additional public API ──────────────────────────────────────────────

    /** @return string Absolute path to the output archive file. */
    public function getTargetFile(): string
    {
        return $this->targetFile;
    }
}
