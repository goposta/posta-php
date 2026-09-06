<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages web form endpoints.
 *
 * A form is a public URL a website posts to; its submissions land in the
 * workspace as messages.
 */
class Forms
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a form and return it with its public key.
     *
     * `allowed_origins` with `strict_origin` is the main defence against a
     * copied endpoint. `notify_mode` is "immediate", "hourly", "daily", or
     * "off".
     *
     * @param array{name: string, slug?: string, description?: string, allowed_origins?: string[], strict_origin?: bool, require_nonce?: bool, redirect_url?: string, allow_attachments?: bool, notify_emails?: string[], notify_mode?: string, reply_from?: string, reply_from_name?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/forms', $request);
    }

    /**
     * Return a page of forms.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/forms' . Http::query($options));
    }

    /**
     * Return one form.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/forms/' . Http::seg($id));
    }

    /**
     * Change a form. `status` is "active", "paused", or "archived".
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/forms/' . Http::seg($id), $request);
    }

    /**
     * Remove a form and the messages collected through it.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/forms/' . Http::seg($id));
    }

    /**
     * Issue a new public key.
     *
     * Existing embeds stop working the moment this returns, so update them
     * together.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function rotateKey(int $id): array
    {
        return $this->http->post(Http::WS . '/forms/' . Http::seg($id) . '/rotate-key');
    }

    /**
     * Return paste-ready HTML and fetch() code wired to a form.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function snippet(int $id): array
    {
        return $this->http->get(Http::WS . '/forms/' . Http::seg($id) . '/snippet');
    }

    /**
     * Issue a submission nonce for a form whose `require_nonce` is set.
     *
     * A public endpoint keyed by the form's public key; it needs no credential.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function nonce(string $publicKey): array
    {
        return $this->http->get('/f/' . Http::seg($publicKey) . '/nonce');
    }

    /**
     * Post a submission to a form's public ingest endpoint.
     *
     * It needs no credential, and always answers success for a stored or
     * silently rejected submission so a spam client learns nothing.
     *
     * @param array<string, mixed> $fields
     * @throws PostaException
     */
    public function submit(string $publicKey, array $fields): void
    {
        $this->http->post('/f/' . Http::seg($publicKey), $fields);
    }
}
