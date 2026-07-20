<?php

declare(strict_types=1);


namespace BMI\Plugin\External\Stream;


use BatchZipStream\Contracts\WritableStreamInterface;
use BMI\Plugin\External\Stream\WritableStreamException;
use BMI\Plugin\External\Stream\BMIZipStreamProvider;

/**
 * Factory for BackupBliss writable streams.
 */
class BackupBlissWritableStreamFactory implements WritableStreamFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(string $filename, array $options = []): WritableStreamInterface
    {
        // Lazy-load the BackupBliss stream class
        require_once BMI_PRO_INC . 'external/stream/class-backupbliss-writable-stream.php';
        require_once BMI_INCLUDES . '/external/backupbliss.php';

        $chunkSize = $options['chunk_size'] ?? 10485760; // 10MB default
        $maxRetries = $options['max_retries'] ?? 3;
        $state = $options['state'] ?? null;

        // Create BackupBliss client
        $backupBlissClass = '\\BMI\\Plugin\\External\\BMI_External_BackupBliss';
        if (!class_exists($backupBlissClass)) {
            throw new WritableStreamException(
                'BackupBliss client class not found',
                'PROVIDER_NOT_AVAILABLE'
            );
        }

        $backupBliss = new $backupBlissClass();
        $streamClass = '\\BMI\\Plugin\\External\\Stream\\BackupBlissWritableStream';

        if (!class_exists($streamClass)) {
            throw new WritableStreamException(
                'BackupBliss writable stream class not found',
                'STREAM_CLASS_NOT_FOUND'
            );
        }

        return new $streamClass(
            $filename,
            $backupBliss,
            $chunkSize,
            $maxRetries,
            $state
        );
    }

    /**
     * @inheritDoc
     */
    public function getProvider(): string
    {
        return BMIZipStreamProvider::BACKUPBLISS;
    }
}