<?php

declare(strict_types=1);

namespace Posta;

/**
 * HTTP transport shared by every resource client.
 *
 * Unwraps Posta's `{"success": …, "data": …}` envelope and turns non-2xx
 * responses into {@see PostaException}. Uses cURL only, so the package has no
 * Composer dependencies.
 *
 * @internal
 */
class Http
{
    /** Header that selects the active workspace for workspace-scoped endpoints. */
    public const WORKSPACE_HEADER = 'X-Posta-Workspace-Id';

    /** Header carrying a webhook delivery's HMAC signature. */
    public const SIGNATURE_HEADER = 'X-Posta-Signature';

    /** Path prefix shared by every workspace-scoped endpoint. */
    public const WS = '/workspaces/current';

    /** Client library version, reported in the User-Agent header. */
    public const VERSION = '0.2.0';

    private string $baseUrl;
    private string $rootUrl;
    private string $credential;
    private int $timeout;
    /** @var array<string, string> */
    private array $extraHeaders;

    /**
     * @param array<string, string> $headers Extra headers sent with every request
     */
    public function __construct(
        string $baseUrl,
        string $credential,
        int $timeout = 30,
        ?int $workspaceId = null,
        array $headers = [],
        ?string $userAgent = null
    ) {
        $this->rootUrl = rtrim($baseUrl, '/');
        $this->baseUrl = $this->rootUrl . '/api/v1';
        $this->credential = $credential;
        $this->timeout = $timeout;

        $headers['User-Agent'] = $userAgent ?? ('posta-php/' . self::VERSION);
        if ($workspaceId !== null) {
            $headers[self::WORKSPACE_HEADER] = (string) $workspaceId;
        }
        $this->extraHeaders = $headers;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Build a query string, omitting null and empty values.
     *
     * @param array<string, mixed> $params
     */
    public static function query(array $params): string
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $clean[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }
        return $clean === [] ? '' : '?' . http_build_query($clean);
    }

    /**
     * Drop keys whose value is null.
     *
     * Omitting a field and sending it as `null` mean different things to the
     * API — the second clears a stored value — so callers pass null for
     * "leave alone" and an explicit null-valued array when they mean to clear.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public static function body(array $fields): array
    {
        return array_filter($fields, static fn ($v) => $v !== null);
    }

    /** URL-encode a path segment. */
    public static function seg(string|int $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * GET, returning the envelope's `data`.
     *
     * @return mixed
     * @throws PostaException
     */
    public function get(string $path)
    {
        return $this->envelope('GET', $path);
    }

    /**
     * GET, returning the full paginated envelope (`data` plus `pageable`).
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getPage(string $path): array
    {
        $decoded = $this->raw('GET', $path);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * POST, returning the envelope's `data`.
     *
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    public function post(string $path, ?array $body = null)
    {
        return $this->envelope('POST', $path, $body);
    }

    /**
     * PUT, returning the envelope's `data`.
     *
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    public function put(string $path, ?array $body = null)
    {
        return $this->envelope('PUT', $path, $body);
    }

    /**
     * PATCH, returning the envelope's `data`.
     *
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    public function patch(string $path, ?array $body = null)
    {
        return $this->envelope('PATCH', $path, $body);
    }

    /**
     * DELETE, returning the envelope's `data` (often nothing).
     *
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    public function delete(string $path, ?array $body = null)
    {
        return $this->envelope('DELETE', $path, $body);
    }

    /**
     * Fetch a URL outside the versioned API, returning the body as-is.
     *
     * The health probes answer with a bare object rather than the
     * `{"success", "data"}` envelope every /api/v1 endpoint uses.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getRoot(string $path): array
    {
        [$body, $status, $type] = $this->send('GET', $this->rootUrl . $path);
        $this->throwIfError($status, $body);
        $decoded = $body === '' ? [] : json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Download a binary body, returning `['data' => string, 'content_type' => string]`.
     *
     * Used by endpoints that answer with a file rather than JSON: message and
     * inbound attachments, and raw .eml messages.
     *
     * @return array{data: string, content_type: string}
     * @throws PostaException
     */
    public function download(string $path): array
    {
        [$body, $status, $type] = $this->send('GET', $this->baseUrl . $path);
        $this->throwIfError($status, $body);
        return ['data' => $body, 'content_type' => $type];
    }

    /**
     * POST a multipart/form-data body, returning the envelope's `data`.
     *
     * @param array<string, string> $fields Extra text fields sent alongside the file
     * @return mixed
     * @throws PostaException
     */
    public function upload(
        string $path,
        string $filename,
        string $content,
        string $field = 'file',
        array $fields = []
    ) {
        $boundary = bin2hex(random_bytes(16));
        $parts = '';
        foreach ($fields as $name => $value) {
            $parts .= "--{$boundary}\r\n"
                . "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n"
                . "{$value}\r\n";
        }
        $parts .= "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"{$field}\"; filename=\"{$filename}\"\r\n"
            . "Content-Type: application/octet-stream\r\n\r\n"
            . $content . "\r\n"
            . "--{$boundary}--\r\n";

        [$body, $status, $type] = $this->send(
            'POST',
            $this->baseUrl . $path,
            $parts,
            'multipart/form-data; boundary=' . $boundary
        );
        $this->throwIfError($status, $body);
        $decoded = $body === '' ? null : json_decode($body, true);
        return is_array($decoded) ? ($decoded['data'] ?? null) : null;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    private function envelope(string $method, string $path, ?array $body = null)
    {
        $decoded = $this->raw($method, $path, $body);
        return is_array($decoded) ? ($decoded['data'] ?? null) : null;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return mixed
     * @throws PostaException
     */
    private function raw(string $method, string $path, ?array $body = null)
    {
        $payload = $body === null ? null : json_encode($body);
        [$responseBody, $status, $type] = $this->send(
            $method,
            $this->baseUrl . $path,
            $payload,
            'application/json'
        );
        $this->throwIfError($status, $responseBody);

        // 204 No Content (or any successful empty body) carries no envelope.
        if ($responseBody === '' || $status === 204) {
            return null;
        }
        return json_decode($responseBody, true);
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     * @throws PostaException
     */
    private function send(
        string $method,
        string $url,
        ?string $payload = null,
        ?string $contentType = null
    ): array {
        $headers = ['Accept: application/json'];
        if ($this->credential !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->credential;
        }
        if ($contentType !== null) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        foreach ($this->extraHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new PostaException('HTTP request failed: ' . $error, 0);
        }
        return [(string) $body, $status, $type];
    }

    /** @throws PostaException */
    private function throwIfError(int $status, string $body): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }
        $decoded = $body === '' ? null : json_decode($body, true);
        $message = 'Unexpected status ' . $status;
        $info = null;
        if (is_array($decoded) && isset($decoded['error']) && is_array($decoded['error'])) {
            $info = $decoded['error'];
            $message = $info['message'] ?? $message;
        }
        throw new PostaException($message, $status, $info);
    }
}
