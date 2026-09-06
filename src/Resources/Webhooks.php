<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Registers endpoints Posta notifies, and reads the deliveries it made. */
class Webhooks
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of webhooks. Needs an API key with the `webhooks` scope.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage('/webhooks' . Http::query($options));
    }

    /**
     * Register a webhook endpoint.
     *
     * The response carries the signing secret, which is shown only here —
     * store it to verify deliveries. Event names are the constants on
     * \Posta\WebhookEvent.
     *
     * @param string[] $events
     * @param string[] $filters
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(string $url, array $events, array $filters = []): array
    {
        return $this->http->post('/webhooks', Http::body([
            'url'     => $url,
            'events'  => $events,
            'filters' => $filters ?: null,
        ]));
    }

    /**
     * Remove a webhook.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete('/webhooks/' . Http::seg($id));
    }

    /**
     * Return a page of delivery attempts, with each one's status and error.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listDeliveries(array $options = []): array
    {
        return $this->http->getPage('/webhook-deliveries' . Http::query($options));
    }

    /**
     * Return a page of webhooks through the workspace-scoped endpoint.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listInWorkspace(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/webhooks' . Http::query($options));
    }

    /**
     * Register a webhook through the workspace-scoped endpoint.
     *
     * @param string[] $events
     * @param string[] $filters
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createInWorkspace(string $url, array $events, array $filters = []): array
    {
        return $this->http->post(Http::WS . '/webhooks', Http::body([
            'url'     => $url,
            'events'  => $events,
            'filters' => $filters ?: null,
        ]));
    }

    /**
     * Remove a webhook through the workspace-scoped endpoint.
     *
     * @throws PostaException
     */
    public function deleteInWorkspace(int $id): void
    {
        $this->http->delete(Http::WS . '/webhooks/' . Http::seg($id));
    }

    /**
     * Return delivery attempts through the workspace-scoped endpoint.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listDeliveriesInWorkspace(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/webhook-deliveries' . Http::query($options));
    }
}
