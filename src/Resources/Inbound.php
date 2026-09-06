<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Reads the email Posta received.
 *
 * Covers both its inbound SMTP listener and messages relayed in by an external
 * provider's webhook.
 */
class Inbound
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of inbound emails.
     *
     * `source` is how the message arrived: "smtp" or "webhook".
     *
     * @param array{page?: int, size?: int, status?: string, source?: string, sender?: string, q?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/inbound-emails' . Http::query($options));
    }

    /**
     * Return one inbound email with its parsed bodies.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(string $uuid): array
    {
        return $this->http->get(Http::WS . '/inbound-emails/' . Http::seg($uuid));
    }

    /**
     * Remove an inbound email and its stored raw message.
     *
     * @throws PostaException
     */
    public function delete(string $uuid): void
    {
        $this->http->delete(Http::WS . '/inbound-emails/' . Http::seg($uuid));
    }

    /**
     * Re-dispatch the webhook for an inbound email whose forwarding failed.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function retry(string $uuid): array
    {
        return $this->http->post(Http::WS . '/inbound-emails/' . Http::seg($uuid) . '/retry');
    }

    /**
     * Download the original RFC 5322 message (.eml).
     *
     * For callers that need headers Posta did not parse out.
     *
     * @return array{data: string, content_type: string}
     * @throws PostaException
     */
    public function downloadRaw(string $uuid): array
    {
        return $this->http->download(Http::WS . '/inbound-emails/' . Http::seg($uuid) . '/raw');
    }

    /**
     * Download one attachment.
     *
     * @return array{data: string, content_type: string}
     * @throws PostaException
     */
    public function downloadAttachment(string $uuid, int $index): array
    {
        return $this->http->download(
            Http::WS . '/inbound-emails/' . Http::seg($uuid) . '/attachments/' . Http::seg($index)
        );
    }
}
