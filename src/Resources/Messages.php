<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Reads and triages the submissions collected by web forms. */
class Messages
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return a page of messages.
     *
     * `status` is the spam verdict (received, flagged, spam, rejected);
     * `state` is the triage state (new, open, replied, closed, spam).
     *
     * @param array{page?: int, size?: int, form_id?: int, status?: string, state?: string, unread?: bool, q?: string, after?: string, before?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/messages' . Http::query($options));
    }

    /**
     * Return one message with its fields, attachments, and reply thread.
     *
     * Reading a message marks it read.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(string $uuid): array
    {
        return $this->http->get(Http::WS . '/messages/' . Http::seg($uuid));
    }

    /**
     * Remove a message.
     *
     * @throws PostaException
     */
    public function delete(string $uuid): void
    {
        $this->http->delete(Http::WS . '/messages/' . Http::seg($uuid));
    }

    /**
     * Return total, unread, and spam counts plus the number of forms.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function stats(): array
    {
        return $this->http->get(Http::WS . '/messages/stats');
    }

    /**
     * Return submission volume for the last $days days (default 30).
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function analytics(?int $days = null): array
    {
        return $this->http->get(Http::WS . '/messages/analytics' . Http::query(['days' => $days]));
    }

    /**
     * Answer a message's sender and record the reply on the thread.
     *
     * The reply goes out through the workspace's normal email pipeline.
     *
     * @param array{subject?: string, text?: string, html?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function reply(string $uuid, array $request): array
    {
        return $this->http->post(Http::WS . '/messages/' . Http::seg($uuid) . '/reply', $request);
    }

    /**
     * Move a message through triage.
     *
     * $state is "new", "open", "replied", "closed", or "spam"; $read, when
     * given, also marks it read or unread.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateState(string $uuid, string $state, ?bool $read = null): array
    {
        return $this->http->put(
            Http::WS . '/messages/' . Http::seg($uuid) . '/state',
            Http::body(['state' => $state, 'read' => $read])
        );
    }

    /**
     * Give a message to a workspace member, or clear the assignment with null.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function assign(string $uuid, ?int $userId): array
    {
        return $this->http->put(
            Http::WS . '/messages/' . Http::seg($uuid) . '/assign',
            ['user_id' => $userId]
        );
    }

    /**
     * Quarantine a message, and optionally learn a filter from it.
     *
     * With `create_filter`, `kind` (keyword, phrase, email, domain, ip) and
     * `pattern` describe the rule to create, so later submissions like it are
     * caught on arrival.
     *
     * @param array{create_filter?: bool, kind?: string, pattern?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function markSpam(string $uuid, array $request = []): array
    {
        return $this->http->post(Http::WS . '/messages/' . Http::seg($uuid) . '/spam', $request);
    }

    /**
     * Clear the spam verdict on a message.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function markNotSpam(string $uuid): array
    {
        return $this->http->post(Http::WS . '/messages/' . Http::seg($uuid) . '/not-spam');
    }

    /**
     * Download one attachment.
     *
     * Indexes match the order of the message's `attachments`.
     *
     * @return array{data: string, content_type: string}
     * @throws PostaException
     */
    public function downloadAttachment(string $uuid, int $index): array
    {
        return $this->http->download(
            Http::WS . '/messages/' . Http::seg($uuid) . '/attachments/' . Http::seg($index)
        );
    }
}
