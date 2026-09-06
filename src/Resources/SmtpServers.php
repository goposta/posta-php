<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the SMTP relays Posta delivers outbound mail through. */
class SmtpServers
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Register an SMTP server.
     *
     * `encryption` is "none", "tls", or "starttls"; `allowed_emails`
     * restricts which From addresses may use it.
     *
     * @param array{host: string, port: int, name?: string, username?: string, password?: string, encryption?: string, allowed_emails?: string[], max_retries?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/smtp-servers', $request);
    }

    /**
     * Return a page of SMTP servers.
     *
     * Includes any shared server the platform offers the workspace.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/smtp-servers' . Http::query($options));
    }

    /**
     * Return one SMTP server.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/smtp-servers/' . Http::seg($id));
    }

    /**
     * Change an SMTP server. Omit `password` to keep the stored one.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/smtp-servers/' . Http::seg($id), $request);
    }

    /**
     * Remove an SMTP server.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/smtp-servers/' . Http::seg($id));
    }

    /**
     * Open a connection and authenticate, without sending anything.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function test(int $id): array
    {
        return $this->http->post(Http::WS . '/smtp-servers/' . Http::seg($id) . '/test');
    }
}
