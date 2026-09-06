<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages sending domains and their DNS verification.
 *
 * A workspace that requires verified domains refuses to send from one that has
 * not passed its checks.
 */
class Domains
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Register a domain and return the DNS records to publish for it.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function add(string $domain): array
    {
        return $this->http->post(Http::WS . '/domains', ['domain' => $domain]);
    }

    /**
     * Return a page of domains.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/domains' . Http::query($options));
    }

    /**
     * Return one domain with its DNS records and verification state.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/domains/' . Http::seg($id));
    }

    /**
     * Re-run the DNS lookups and report each check's outcome.
     *
     * DNS propagates slowly, so this is expected to be called repeatedly until
     * `fully_verified` is true.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function verify(int $id): array
    {
        return $this->http->post(Http::WS . '/domains/' . Http::seg($id) . '/verify');
    }

    /**
     * Remove a domain.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/domains/' . Http::seg($id));
    }
}
