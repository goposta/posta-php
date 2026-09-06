<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Sends mail and reads the resulting delivery records.
 *
 * Sending needs an API key with the `send` scope; list() and get() need `read`.
 */
class Emails
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Send a single email.
     *
     * Pass `unsubscribe` as ['list_id' => int] (Posta-managed) or
     * ['url' => string, 'mailto' => string, 'one_click' => bool]
     * (caller-managed). Referencing a Posta-managed list lets Posta mint the
     * signed one-click URL itself.
     *
     * With $dryRun the request is validated but not sent, and the response is
     * the verification payload: which recipients are suppressed, whether the
     * sending domain is verified, and how the template renders.
     *
     * @param array{
     *     from: string,
     *     to: string[],
     *     subject: string,
     *     html?: string,
     *     text?: string,
     *     attachments?: array<array{filename: string, content: string, content_type: string}>,
     *     headers?: array<string, string>,
     *     unsubscribe?: array{list_id?: int, url?: string, mailto?: string, one_click?: bool},
     *     send_at?: string
     * } $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function send(array $request, bool $dryRun = false): array
    {
        return $this->http->post('/emails/send' . Http::query(['dry_run' => $dryRun ?: null]), $request);
    }

    /**
     * Send an email rendered from a stored template.
     *
     * Identify the template by `template_id` (preferred) or `template` (name).
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendTemplate(array $request, bool $dryRun = false): array
    {
        return $this->http->post(
            '/emails/send-template' . Http::query(['dry_run' => $dryRun ?: null]),
            $request
        );
    }

    /**
     * Send one template to many recipients with per-recipient variables.
     *
     * Each entry in `recipients` takes `email` and optionally `template_data`
     * and `language`. The response reports each recipient's outcome
     * individually.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendBatch(array $request, bool $dryRun = false): array
    {
        return $this->http->post('/emails/batch' . Http::query(['dry_run' => $dryRun ?: null]), $request);
    }

    /**
     * Render a template with variables and return it without sending.
     *
     * @param array{template?: string, template_id?: int, language?: string, template_data?: array<string, mixed>} $request
     * @return array{subject: string, html: string, text: string}
     * @throws PostaException
     */
    public function preview(array $request): array
    {
        return $this->http->post('/emails/preview', $request);
    }

    /**
     * Check whether an address is worth sending to.
     *
     * Covers syntax, MX records, disposable and role-account detection, and
     * the caller's own suppression and bounce history. Results are cached;
     * pass $fresh to re-check.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function verify(string $email, bool $fresh = false): array
    {
        return $this->http->post(
            '/emails/verify' . Http::query(['fresh' => $fresh ?: null]),
            ['email' => $email]
        );
    }

    /**
     * Return a lightweight delivery status, suitable for polling.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function status(string $uuid): array
    {
        return $this->http->get('/emails/' . Http::seg($uuid) . '/status');
    }

    /**
     * Re-enqueue a failed email.
     *
     * Only emails in the `failed` state can be retried, and only up to the
     * SMTP server's retry limit.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function retry(string $uuid): array
    {
        return $this->http->post('/emails/' . Http::seg($uuid) . '/retry');
    }

    /**
     * Return a page of emails. Needs an API key with the `read` scope.
     *
     * @param array{page?: int, size?: int, q?: string, sort?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage('/emails' . Http::query($options));
    }

    /**
     * Return one email by UUID, including its rendered bodies.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(string $uuid): array
    {
        return $this->http->get('/emails/' . Http::seg($uuid));
    }

    /**
     * Return a page of emails through the workspace-scoped endpoint, which a
     * session credential can also reach.
     *
     * @param array{page?: int, size?: int, q?: string, sort?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listInWorkspace(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/emails' . Http::query($options));
    }

    /**
     * Return one email through the workspace-scoped endpoint.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getInWorkspace(string $uuid): array
    {
        return $this->http->get(Http::WS . '/emails/' . Http::seg($uuid));
    }
}

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
