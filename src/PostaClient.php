<?php

declare(strict_types=1);

namespace Posta;

/**
 * Posta PHP client for the public email API.
 *
 * Supports sending emails, template emails, batch emails,
 * and checking email delivery status.
 *
 * Usage:
 *   $client = new PostaClient('https://posta.example.com', 'your-api-key');
 *   $response = $client->sendEmail([
 *       'from'    => 'sender@example.com',
 *       'to'      => ['recipient@example.com'],
 *       'subject' => 'Hello',
 *       'html'    => '<h1>Hello World</h1>',
 *   ]);
 */
class PostaClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    /**
     * @param string $baseUrl Base URL of the Posta instance (e.g. https://posta.example.com)
     * @param string $apiKey  API key for authentication
     * @param int    $timeout HTTP timeout in seconds (default: 30)
     */
    public function __construct(string $baseUrl, string $apiKey, int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/api/v1';
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Send a single email.
     *
     * @param array{
     *     from: string,
     *     to: string[],
     *     subject: string,
     *     html?: string,
     *     text?: string,
     *     attachments?: array<array{filename: string, content: string, content_type: string}>,
     *     headers?: array<string, string>,
     *     list_unsubscribe_url?: string,
     *     list_unsubscribe_post?: bool,
     *     send_at?: string,
     *     list?: string
     * } $request
     *
     * The optional `list` field auto-adds the recipient to a named subscriber
     * list (created on first use). Per-list opt-outs are honored — a
     * suppressed recipient returns with `skipped: true` and no send.
     *
     * @return array{id: string, status: string, list_id?: int, subscriber_id?: int, list_created?: bool, member_added?: bool, skipped?: bool, skipped_reason?: string}
     * @throws PostaException
     */
    public function sendEmail(array $request): array
    {
        return $this->post('/emails/send', $request);
    }

    /**
     * Send an email using a template.
     *
     * Provide either template_id or template (name). template_id is preferred (primary key lookup);
     * template is a fallback when the ID is not known.
     *
     * @param array{
     *     template_id?: int,    Template numeric ID (preferred)
     *     template?: string,    Template name (fallback when template_id is not provided)
     *     to: string[],
     *     language?: string,
     *     from?: string,
     *     template_data?: array<string, mixed>,
     *     attachments?: array<array{filename: string, content: string, content_type: string}>
     * } $request
     * @return array{id: string, status: string}
     * @throws PostaException
     */
    public function sendTemplateEmail(array $request): array
    {
        return $this->post('/emails/send-template', $request);
    }

    /**
     * Send batch emails using a template.
     *
     * Provide either template_id or template (name). template_id is preferred (primary key lookup);
     * template is a fallback when the ID is not known.
     *
     * @param array{
     *     template_id?: int,    Template numeric ID (preferred)
     *     template?: string,    Template name (fallback when template_id is not provided)
     *     language?: string,
     *     from?: string,
     *     recipients: array<array{email: string, language?: string, template_data?: array<string, mixed>}>
     * } $request
     * @return array{total: int, sent: int, failed: int, skipped: int, results: array}
     * @throws PostaException
     */
    public function sendBatch(array $request): array
    {
        return $this->post('/emails/batch', $request);
    }

    /**
     * Get the delivery status of an email.
     *
     * @param string $emailId Email UUID
     * @return array{id: string, status: string, error_message?: string, retry_count: int, created_at: string, sent_at?: string}
     * @throws PostaException
     */
    public function getEmailStatus(string $emailId): array
    {
        return $this->get('/emails/' . urlencode($emailId) . '/status');
    }

    /**
     * Retry a failed email delivery.
     *
     * Only emails with status "failed" can be retried, subject to the retry limit
     * configured on the SMTP server.
     *
     * @param string $emailId Email UUID
     * @return array{id: string, status: string}
     * @throws PostaException
     */
    public function retryEmail(string $emailId): array
    {
        return $this->post('/emails/' . urlencode($emailId) . '/retry', []);
    }

    /**
     * Add an email to a named subscriber list. The list is created on first
     * use. Any prior list-scoped opt-out for this (list, email) is cleared.
     * Idempotent.
     *
     * @param string $email Recipient email
     * @param string $list  List name (created if it doesn't exist)
     * @param string $name  Optional subscriber display name
     * @return array{list_id: int, subscriber_id: int, email: string, action: string, list_created?: bool, subscriber_created?: bool, member_added?: bool}
     * @throws PostaException
     */
    public function subscribeToList(string $email, string $list, string $name = ''): array
    {
        $body = ['email' => $email, 'list' => $list];
        if ($name !== '') {
            $body['name'] = $name;
        }
        return $this->post('/subscriber-lists/subscribe', $body);
    }

    /**
     * Opt an email out of a specific subscriber list. Idempotent; does not
     * change the subscriber's global status.
     *
     * @param int    $listId List ID
     * @param string $email  Recipient email address
     * @param string $reason Optional audit reason (default: "api")
     * @return array{list_id: int, subscriber_id: int, email: string, action: string}
     * @throws PostaException
     */
    public function unsubscribeFromList(int $listId, string $email, string $reason = ''): array
    {
        $body = ['email' => $email];
        if ($reason !== '') {
            $body['reason'] = $reason;
        }
        return $this->post('/subscriber-lists/' . $listId . '/unsubscribe', $body);
    }

    /**
     * Reverse a list-scoped opt-out and re-add (for static lists). Idempotent.
     *
     * @param int    $listId List ID
     * @param string $email  Recipient email address
     * @return array{list_id: int, subscriber_id: int, email: string, action: string}
     * @throws PostaException
     */
    public function resubscribeToList(int $listId, string $email): array
    {
        return $this->post('/subscriber-lists/' . $listId . '/resubscribe', ['email' => $email]);
    }


    /**
     * @throws PostaException
     */
    private function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    /**
     * @throws PostaException
     */
    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @throws PostaException
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $responseBody = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            throw new PostaException('HTTP request failed: ' . $curlError, 0);
        }

        $decoded = json_decode((string) $responseBody, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = 'Unexpected status ' . $statusCode;
            if (is_array($decoded) && isset($decoded['error']['message'])) {
                $message = $decoded['error']['message'];
            }
            throw new PostaException($message, $statusCode, $decoded['error'] ?? null);
        }

        if (!is_array($decoded) || !($decoded['success'] ?? false)) {
            throw new PostaException('Invalid response from server', $statusCode);
        }

        return $decoded['data'] ?? [];
    }
}
