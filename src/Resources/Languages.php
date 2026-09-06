<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the languages a workspace's templates can be localized into. */
class Languages
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a language. $code is a BCP 47 tag such as "en" or "pt-BR".
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(string $code, string $name, bool $isDefault = false): array
    {
        return $this->http->post(Http::WS . '/languages', [
            'code'       => $code,
            'name'       => $name,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Return a page of languages.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/languages' . Http::query($options));
    }

    /**
     * Change a language.
     *
     * @param array{code?: string, name?: string, is_default?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/languages/' . Http::seg($id), $request);
    }

    /**
     * Remove a language. Localizations already written in it are kept.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/languages/' . Http::seg($id));
    }
}
