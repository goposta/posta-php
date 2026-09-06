<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the people a workspace sends campaigns to. */
class Subscribers
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a subscriber. `status` defaults to "subscribed".
     *
     * @param array{email: string, name?: string, status?: string, language?: string, timezone?: string, custom_fields?: array<string, mixed>} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/subscribers', $request);
    }

    /**
     * Return a page of subscribers.
     *
     * @param array{page?: int, size?: int, search?: string, status?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/subscribers' . Http::query($options));
    }

    /**
     * Return one subscriber.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/subscribers/' . Http::seg($id));
    }

    /**
     * Change a subscriber. The email address itself cannot be changed.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/subscribers/' . Http::seg($id), $request);
    }

    /**
     * Remove a subscriber and its list memberships.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/subscribers/' . Http::seg($id));
    }

    /**
     * Add or update many subscribers at once.
     *
     * Existing addresses are updated rather than duplicated.
     *
     * @param array<int, array<string, mixed>> $subscribers
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function importJson(array $subscribers): array
    {
        return $this->http->post(
            Http::WS . '/subscribers/import/json',
            ['subscribers' => $subscribers]
        );
    }

    /**
     * Add or update many subscribers from a CSV document.
     *
     * $columnMapping maps zero-based column indexes to subscriber fields and
     * defaults to [0 => 'email', 1 => 'name']. A "custom_fields." prefix
     * routes a column into the subscriber's custom fields, as in
     * [0 => 'email', 1 => 'name', 2 => 'custom_fields.company']. The header row
     * is always skipped.
     *
     * @param array<int, string> $columnMapping
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function importCsv(string $filename, string $content, array $columnMapping = []): array
    {
        $fields = [];
        if ($columnMapping !== []) {
            $fields['column_mapping'] = (string) json_encode(
                array_combine(
                    array_map('strval', array_keys($columnMapping)),
                    array_values($columnMapping)
                )
            );
        }
        return $this->http->upload(
            Http::WS . '/subscribers/import/csv',
            $filename,
            $content,
            'file',
            $fields
        ) ?? [];
    }
}
