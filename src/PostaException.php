<?php

declare(strict_types=1);

namespace Posta;

/**
 * Exception thrown when a Posta API call fails.
 */
class PostaException extends \RuntimeException
{
    private ?array $errorInfo;

    /**
     * @param string     $message    Error message
     * @param int        $statusCode HTTP status code
     * @param array|null $errorInfo  Parsed error info from the API response
     */
    public function __construct(string $message, int $statusCode = 0, ?array $errorInfo = null)
    {
        parent::__construct('posta: ' . $statusCode . ' ' . $message, $statusCode);
        $this->errorInfo = $errorInfo;
    }

    /** HTTP status code returned by the API. */
    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /** Parsed error details from the API, if available. */
    public function getErrorInfo(): ?array
    {
        return $this->errorInfo;
    }
}
