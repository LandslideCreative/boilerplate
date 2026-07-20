<?php

namespace BMI\Plugin\External\Stream;

use BatchZipStream\Contracts\WritableStreamInterface;
use BMI\Plugin\External\Stream\WritableStreamException;


/**
 * Factory interface for creating WritableStream instances.
 * 
 * Implementations provide provider-specific stream creation logic.
 */
interface WritableStreamFactoryInterface
{
    /**
     * Create a writable stream for the given filename.
     * 
     * @param string $filename Target filename for the archive
     * @param array $options Provider-specific options
     * @return WritableStreamInterface
     * @throws WritableStreamException On creation failure
     */
    public function create(string $filename, array $options = []): WritableStreamInterface;

    /**
     * Get the provider identifier.
     * 
     * @return string
     */
    public function getProvider(): string;
}