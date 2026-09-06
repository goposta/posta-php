<?php

declare(strict_types=1);

namespace Posta;

/**
 * Webhook event names, and verification of the signature Posta sends with each
 * delivery.
 *
 * Register a subset of the event constants on a webhook to choose what Posta
 * notifies you about.
 */
final class WebhookEvent
{
    /** A message was accepted by the destination MTA. */
    public const EMAIL_SENT = 'email.sent';
    /** A message permanently failed after retries. */
    public const EMAIL_FAILED = 'email.failed';
    /** An inbound email was received and parsed. */
    public const EMAIL_INBOUND = 'email.inbound';
    /** A recipient opted out via one-click unsubscribe. */
    public const EMAIL_UNSUBSCRIBED = 'email.unsubscribed';
    /** A recipient marked a message as spam. */
    public const EMAIL_COMPLAINED = 'email.complained';
    /** A campaign began sending. */
    public const CAMPAIGN_STARTED = 'campaign.started';
    /** A campaign finished sending. */
    public const CAMPAIGN_COMPLETED = 'campaign.completed';
    /** A web form submission passed scanning. */
    public const MESSAGE_RECEIVED = 'message.received';
    /** A web form submission was quarantined or rejected. */
    public const MESSAGE_SPAM = 'message.spam';

    /**
     * Report whether $signature authenticates $payload under $secret.
     *
     * Posta signs each delivery with HMAC-SHA256 over the raw request body and
     * sends it as `sha256=<hex>` in the `X-Posta-Signature` header. Pass the
     * header value verbatim, along with the exact bytes received —
     * re-serializing the JSON changes them and the check will fail.
     *
     * Example:
     *   $raw = file_get_contents('php://input');
     *   $sig = $_SERVER['HTTP_X_POSTA_SIGNATURE'] ?? null;
     *   if (!WebhookEvent::verifySignature($raw, $sig, $secret)) {
     *       http_response_code(401);
     *       exit;
     *   }
     */
    public static function verifySignature(string $payload, ?string $signature, string $secret): bool
    {
        if ($signature === null || $signature === '' || $secret === '') {
            return false;
        }
        $provided = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        $expected = hash_hmac('sha256', $payload, $secret);
        // hash_equals compares in constant time, so a wrong signature leaks
        // nothing about how much of it was right.
        return hash_equals($expected, $provided);
    }
}
