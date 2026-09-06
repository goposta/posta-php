<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the addresses Posta refuses to deliver to. */
class Suppressions
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of suppressed addresses.
     *
     * Set `list_id` to see the opt-outs recorded against a single unsubscribe
     * list.
     *
     * @param array{page?: int, size?: int, list_id?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/suppressions' . Http::query($options));
    }

    /**
     * Suppress an address.
     *
     * $listId scopes the suppression to a single unsubscribe list; without it
     * the address is suppressed workspace wide.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function add(string $email, string $reason = '', ?int $listId = null): array
    {
        return $this->http->post(Http::WS . '/suppressions', Http::body([
            'email'   => $email,
            'reason'  => $reason ?: null,
            'list_id' => $listId,
        ]));
    }

    /**
     * Lift a suppression, letting Posta deliver to the address again.
     *
     * Pass $listId to lift a list-scoped opt-out, or omit it for the
     * workspace-wide entry.
     *
     * @throws PostaException
     */
    public function remove(string $email, ?int $listId = null): void
    {
        $this->http->delete(Http::WS . '/suppressions', Http::body([
            'email'   => $email,
            'list_id' => $listId,
        ]));
    }
}
