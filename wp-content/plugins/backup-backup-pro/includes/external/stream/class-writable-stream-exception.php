<?php

declare(strict_types=1);

namespace BMI\Plugin\External\Stream;

/**
 * Exception for WritableStream-specific errors.
 */
class WritableStreamException extends \Exception
{
    /** @var string Error code for categorization */
    private string $errorCode;

    /**
     * @param string $message Error message
     * @param string $errorCode Error code for programmatic handling
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, string $errorCode = 'UNKNOWN', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
    }

    /**
     * Get the error code.
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}