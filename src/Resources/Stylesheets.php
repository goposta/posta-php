<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages reusable CSS shared by template versions.
 *
 * Keeping the house style in one stylesheet means changing it once rather than
 * in every template.
 */
class Stylesheets
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a stylesheet.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(string $name, string $css): array
    {
        return $this->http->post(Http::WS . '/stylesheets', ['name' => $name, 'css' => $css]);
    }

    /**
     * Return a page of stylesheets.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/stylesheets' . Http::query($options));
    }

    /**
     * Replace a stylesheet's name and CSS.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, string $name, string $css): array
    {
        return $this->http->put(
            Http::WS . '/stylesheets/' . Http::seg($id),
            ['name' => $name, 'css' => $css]
        );
    }

    /**
     * Remove a stylesheet. Versions referencing it fall back to none.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/stylesheets/' . Http::seg($id));
    }
}
