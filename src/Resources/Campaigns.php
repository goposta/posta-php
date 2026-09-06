<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages bulk sends to a subscriber list.
 *
 * Covers the whole lifecycle: draft, schedule, send, pause, resume, cancel.
 */
class Campaigns
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a campaign.
     *
     * Set `scheduled_at` to send later; leave it out and the campaign stays a
     * draft until send() is called. `send_rate` caps deliveries per hour, and
     * `send_at_local_time` staggers delivery so each subscriber receives it at
     * the scheduled hour in their own timezone.
     *
     * @param array{name: string, subject: string, from_email: string, list_id: int, template_id: int, from_name?: string, template_version_id?: int, template_data?: array<string, mixed>, language?: string, scheduled_at?: string, send_rate?: int, send_at_local_time?: bool, ab_test_enabled?: bool, ab_test_variants?: array<int, array<string, mixed>>} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/campaigns', $request);
    }

    /**
     * Return a page of campaigns with their delivery counters.
     *
     * @param array{page?: int, size?: int, status?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/campaigns' . Http::query($options));
    }

    /**
     * Return one campaign with its delivery counters.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/campaigns/' . Http::seg($id));
    }

    /**
     * Change a campaign that has not started sending.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/campaigns/' . Http::seg($id), $request);
    }

    /**
     * Remove a campaign.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/campaigns/' . Http::seg($id));
    }

    /**
     * Start a campaign immediately, ignoring any schedule on it.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function send(int $id): array
    {
        return $this->http->post(Http::WS . '/campaigns/' . Http::seg($id) . '/send');
    }

    /**
     * Halt a sending campaign. Recipients already sent to are not resent.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function pause(int $id): array
    {
        return $this->http->post(Http::WS . '/campaigns/' . Http::seg($id) . '/pause');
    }

    /**
     * Continue a paused campaign from where it stopped.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function resume(int $id): array
    {
        return $this->http->post(Http::WS . '/campaigns/' . Http::seg($id) . '/resume');
    }

    /**
     * Stop a campaign for good. It cannot be resumed afterwards.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function cancel(int $id): array
    {
        return $this->http->post(Http::WS . '/campaigns/' . Http::seg($id) . '/cancel');
    }

    /**
     * Copy a campaign into a fresh draft.
     *
     * Lets a recurring send be repeated without rebuilding it.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function duplicate(int $id): array
    {
        return $this->http->post(Http::WS . '/campaigns/' . Http::seg($id) . '/duplicate');
    }

    /**
     * Return a page of per-subscriber sends with their engagement times.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listMessages(int $id, array $options = []): array
    {
        return $this->http->getPage(
            Http::WS . '/campaigns/' . Http::seg($id) . '/messages' . Http::query($options)
        );
    }

    /**
     * Return the engagement analytics for a campaign.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function analytics(int $id): array
    {
        return $this->http->get(Http::WS . '/campaigns/' . Http::seg($id) . '/analytics');
    }
}
