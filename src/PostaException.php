<?php

declare(strict_types=1);

namespace Posta;

/**
 * Exception thrown when a Posta API call fails.
 */
class PostaException extends \RuntimeException
{
    /** @var array<string, mixed>|null */
    private ?array $errorInfo;

    /**
     * @param string     $message    Error message
     * @param int        $statusCode HTTP status code
     * @param array<string, mixed>|null $errorInfo Parsed error info from the API response
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

    /**
     * Parsed error details from the API, if available.
     *
     * @return array<string, mixed>|null
     */
    public function getErrorInfo(): ?array
    {
        return $this->errorInfo;
    }

    /** The API's structured error code, e.g. "rate_limited". */
    public function getErrorCode(): ?string
    {
        return $this->errorInfo['code'] ?? null;
    }

    /** True when the API answered 404: no such record. */
    public function isNotFound(): bool
    {
        return $this->getCode() === 404;
    }

    /** True when the API answered 401: missing or revoked credential. */
    public function isUnauthorized(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * True when the API answered 403.
     *
     * For an API key this usually means the key lacks the scope the endpoint
     * requires.
     */
    public function isForbidden(): bool
    {
        return $this->getCode() === 403;
    }

    /** True when the API answered 429: rate limited. */
    public function isRateLimited(): bool
    {
        return $this->getCode() === 429;
    }
}
