<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Reads recorded bounces and complaints, and records them for a provider. */
class Bounces
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of bounces. Needs an API key with the `read` scope.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage('/bounces' . Http::query($options));
    }

    /**
     * Return a page of bounces through the workspace-scoped endpoint.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listInWorkspace(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/bounces' . Http::query($options));
    }

    /**
     * File a bounce against a recipient.
     *
     * For callers relaying notifications from a provider Posta does not poll
     * itself. $emailId is the UUID of the email that bounced and $type is
     * "hard" or "soft".
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function record(string $emailId, string $recipient, string $type, string $reason = ''): array
    {
        return $this->http->post(Http::WS . '/bounces', Http::body([
            'email_id'  => $emailId,
            'recipient' => $recipient,
            'type'      => $type,
            'reason'    => $reason ?: null,
        ]));
    }
}
