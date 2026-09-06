<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages credentials for Posta's own SMTP relay listener.
 *
 * They let an existing application send through Posta by pointing its SMTP
 * client at it, instead of calling the HTTP API.
 */
class SmtpCredentials
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Mint a relay credential.
     *
     * The password is returned once, in this response; store it now.
     * $allowedIps restricts which addresses may authenticate with it.
     *
     * @param string[] $allowedIps
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(string $name, array $allowedIps = []): array
    {
        return $this->http->post(Http::WS . '/smtp-credentials', Http::body([
            'name'        => $name,
            'allowed_ips' => $allowedIps ?: null,
        ]));
    }

    /**
     * Return a page of relay credentials.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/smtp-credentials' . Http::query($options));
    }

    /**
     * Return one relay credential.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/smtp-credentials/' . Http::seg($id));
    }

    /**
     * Disable a credential without deleting it, so past use stays auditable.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function revoke(int $id): array
    {
        return $this->http->post(Http::WS . '/smtp-credentials/' . Http::seg($id) . '/revoke');
    }

    /**
     * Remove a relay credential entirely.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function delete(int $id): array
    {
        return $this->http->delete(Http::WS . '/smtp-credentials/' . Http::seg($id)) ?? [];
    }
}
