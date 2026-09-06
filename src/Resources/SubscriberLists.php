<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages lists and who belongs to them.
 *
 * A "static" list has explicit members. A "segment" list derives its members
 * from filter rules evaluated at send time.
 */
class SubscriberLists
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a list. `type` is "static" (the default) or "segment".
     *
     * @param array{name: string, description?: string, type?: string, filter_rules?: array<int, array<string, mixed>>} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/subscriber-lists', $request);
    }

    /**
     * Return a page of lists with their member counts.
     *
     * @param array{page?: int, size?: int, q?: string, sort?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/subscriber-lists' . Http::query($options));
    }

    /**
     * Return one list with its member count.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/subscriber-lists/' . Http::seg($id));
    }

    /**
     * Change a list. The type cannot be changed after creation.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/subscriber-lists/' . Http::seg($id), $request);
    }

    /**
     * Remove a list. The subscribers themselves are not deleted.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/subscriber-lists/' . Http::seg($id));
    }

    /**
     * Return a page of the subscribers on a list.
     *
     * For a segment list this evaluates the filter rules.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listMembers(int $listId, array $options = []): array
    {
        return $this->http->getPage(
            Http::WS . '/subscriber-lists/' . Http::seg($listId) . '/members' . Http::query($options)
        );
    }

    /**
     * Put an existing subscriber on a static list.
     *
     * @throws PostaException
     */
    public function addMember(int $listId, int $subscriberId): void
    {
        $this->http->post(
            Http::WS . '/subscriber-lists/' . Http::seg($listId) . '/members',
            ['subscriber_id' => $subscriberId]
        );
    }

    /**
     * Take a subscriber off a static list.
     *
     * This is not an opt-out: use unsubscribe() to record one.
     *
     * @throws PostaException
     */
    public function removeMember(int $listId, int $subscriberId): void
    {
        $this->http->delete(
            Http::WS . '/subscriber-lists/' . Http::seg($listId) . '/members',
            ['subscriber_id' => $subscriberId]
        );
    }

    /**
     * Count the subscribers a candidate segment would match.
     *
     * @param array<int, array<string, mixed>> $filterRules
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function previewSegment(array $filterRules): array
    {
        return $this->http->post(
            Http::WS . '/subscriber-lists/preview-segment',
            ['filter_rules' => $filterRules]
        );
    }

    /**
     * Add an address to a list by name, creating the list on first use.
     *
     * Clears any prior opt-out for it. Idempotent, and reachable with a
     * `send`-scoped API key, so a signup form can call it directly.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function subscribe(string $email, string $list, string $name = ''): array
    {
        return $this->http->post('/subscriber-lists/subscribe', Http::body([
            'email' => $email,
            'list'  => $list,
            'name'  => $name ?: null,
        ]));
    }

    /**
     * Opt an address out of one list.
     *
     * The subscriber's global status is untouched. Idempotent.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function unsubscribe(int $listId, string $email, string $reason = ''): array
    {
        return $this->http->post(
            '/subscriber-lists/' . Http::seg($listId) . '/unsubscribe',
            Http::body(['email' => $email, 'reason' => $reason ?: null])
        );
    }

    /**
     * Reverse a list-scoped opt-out.
     *
     * For a static list, this also puts the subscriber back on it. Idempotent.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function resubscribe(int $listId, string $email): array
    {
        return $this->http->post(
            '/subscriber-lists/' . Http::seg($listId) . '/resubscribe',
            ['email' => $email]
        );
    }

    /**
     * Opt an address out through the workspace-scoped endpoint.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function unsubscribeInWorkspace(int $listId, string $email, string $reason = ''): array
    {
        return $this->http->post(
            Http::WS . '/subscriber-lists/' . Http::seg($listId) . '/unsubscribe',
            Http::body(['email' => $email, 'reason' => $reason ?: null])
        );
    }

    /**
     * Reverse an opt-out through the workspace-scoped endpoint.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function resubscribeInWorkspace(int $listId, string $email): array
    {
        return $this->http->post(
            Http::WS . '/subscriber-lists/' . Http::seg($listId) . '/resubscribe',
            ['email' => $email]
        );
    }
}
