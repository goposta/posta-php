<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Manages the spam rules applied to form submissions as they arrive. */
class MessageFilters
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a spam filter.
     *
     * `kind` selects what `pattern` matches: keyword, phrase, regex, email,
     * domain, or ip. `action` is score, flag, quarantine, reject, or
     * allowlist. `form_id` limits the rule to one form; omit it to apply it
     * workspace wide.
     *
     * @param array{kind: string, pattern: string, fields?: string[], action?: string, score?: float, case_sensitive?: bool, form_id?: int, note?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/message-filters', $request);
    }

    /**
     * Return a page of spam filters with their hit counts.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/message-filters' . Http::query($options));
    }

    /**
     * Change a spam filter.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/message-filters/' . Http::seg($id), $request);
    }

    /**
     * Remove a spam filter.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/message-filters/' . Http::seg($id));
    }

    /**
     * Report how many recent messages a candidate filter would match.
     *
     * Nothing is created, so a rule can be checked before it starts rejecting
     * mail.
     *
     * @param array{kind: string, pattern: string, case_sensitive?: bool, limit?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function test(array $request): array
    {
        return $this->http->post(Http::WS . '/message-filters/test', $request);
    }
}
