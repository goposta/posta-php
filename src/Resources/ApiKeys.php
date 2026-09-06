<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the workspace's machine credentials. */
class ApiKeys
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Mint an API key.
     *
     * An empty `scopes` defaults to ["send"]; omitting `expires_in_days`
     * leaves the key permanent. The secret is returned once, as `key`; store
     * it now. Scope values are the constants on \Posta\Scope.
     *
     * @param array{name: string, scopes?: string[], allowed_ips?: string[], expires_in_days?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/api-keys', $request);
    }

    /**
     * Return a page of API keys. The secrets are not included.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/api-keys' . Http::query($options));
    }

    /**
     * Return one API key's metadata.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/api-keys/' . Http::seg($id));
    }

    /**
     * Disable a key without deleting it, so its past use stays auditable.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function revoke(int $id): array
    {
        return $this->http->put(Http::WS . '/api-keys/' . Http::seg($id) . '/revoke') ?? [];
    }

    /**
     * Remove a key entirely.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function delete(int $id): array
    {
        return $this->http->delete(Http::WS . '/api-keys/' . Http::seg($id)) ?? [];
    }
}
