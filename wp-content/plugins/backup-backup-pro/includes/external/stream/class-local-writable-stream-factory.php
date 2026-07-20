<?php

declare(strict_types=1);


namespace BMI\Plugin\External\Stream;


use BatchZipStream\Contracts\WritableStreamInterface;
use BMI\Plugin\External\Stream\BMIZipStreamProvider;

/**
 * Factory for the local-filesystem writable stream.
 */
class LocalWritableStreamFactory implements WritableStreamFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(string $filename, array $options = []): WritableStreamInterface
    {
        return new LocalWritableStream(BMI_BACKUPS . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * @inheritDoc
     */
    public function getProvider(): string
    {
        return BMIZipStreamProvider::LOCAL;
    }
}