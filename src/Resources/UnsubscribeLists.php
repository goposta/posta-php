<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages the named opt-out lists that List-Unsubscribe headers point at.
 *
 * Referencing one from a send lets Posta mint the signed one-click URL and
 * record the opt-out against that list alone.
 */
class UnsubscribeLists
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add an unsubscribe list.
     *
     * `public_name` is what a recipient sees on the opt-out page.
     *
     * @param array{name: string, public_name?: string, description?: string, active?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/unsubscribe-lists', $request);
    }

    /**
     * Return a page of unsubscribe lists.
     *
     * @param array{page?: int, size?: int, q?: string, sort?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/unsubscribe-lists' . Http::query($options));
    }

    /**
     * Return one unsubscribe list.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/unsubscribe-lists/' . Http::seg($id));
    }

    /**
     * Change an unsubscribe list.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/unsubscribe-lists/' . Http::seg($id), $request);
    }

    /**
     * Remove an unsubscribe list.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/unsubscribe-lists/' . Http::seg($id));
    }
}
