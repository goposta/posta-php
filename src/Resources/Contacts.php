<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Reads the derived record of every address the workspace has mailed.
 *
 * Contacts are created by sending; they are not managed directly.
 */
class Contacts
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of contacts.
     *
     * @param array{page?: int, size?: int, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/contacts' . Http::query($options));
    }

    /**
     * Return one contact.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/contacts/' . Http::seg($id));
    }
}
