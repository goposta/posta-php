<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Reads delivery and engagement analytics for the workspace. */
class Analytics
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return email volume and status analytics over the requested window.
     *
     * `from` and `to` are ISO-8601 timestamps bounding the window.
     *
     * @param array{from?: string, to?: string, status?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function emails(array $options = []): array
    {
        return $this->http->get(Http::WS . '/analytics' . Http::query($options));
    }

    /**
     * Return delivery and bounce trends plus send-latency percentiles.
     *
     * @param array{from?: string, to?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function dashboard(array $options = []): array
    {
        return $this->http->get(Http::WS . '/analytics/dashboard' . Http::query($options));
    }

    /**
     * Return deliverability broken down by recipient mailbox provider.
     *
     * A reputation problem at one provider shows up here first.
     *
     * @param array{from?: string, to?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function providers(array $options = []): array
    {
        return $this->http->get(Http::WS . '/analytics/providers' . Http::query($options));
    }

    /**
     * Return the workspace's headline counters.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function dashboardStats(): array
    {
        return $this->http->get(Http::WS . '/dashboard/stats');
    }
}
