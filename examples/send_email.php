<?php

declare(strict_types=1);

/**
 * Exercises a representative slice of the Posta PHP client: sending,
 * templates, subscribers, campaigns, and webhook verification.
 */

require __DIR__ . '/../vendor/autoload.php';

use Posta\PostaClient;
use Posta\PostaException;
use Posta\WebhookEvent;

$posta = new PostaClient(
    'https://posta.example.com',
    'psk_your_api_key',
    workspaceId: 1,
);

try {
    // A plain transactional send.
    $resp = $posta->emails->send([
        'from'    => 'Acme <hello@example.com>',
        'to'      => ['user@example.com'],
        'subject' => 'Hello from Posta',
        'html'    => '<h1>Hello!</h1><p>This is a test email.</p>',
        'text'    => 'Hello! This is a test email.',
    ]);
    echo "sent: id={$resp['id']} status={$resp['status']}\n";

    // Poll its delivery status.
    $status = $posta->emails->status($resp['id']);
    echo "status: {$status['status']} (retries: {$status['retry_count']})\n";

    // Send from a stored template.
    $posta->emails->sendTemplate([
        'template'      => 'welcome',
        'to'            => ['user@example.com'],
        'from'          => 'noreply@example.com',
        'template_data' => ['name' => 'Alice'],
    ]);

    // Batch send with per-recipient variables.
    $batch = $posta->emails->sendBatch([
        'template'   => 'welcome',
        'from'       => 'noreply@example.com',
        'recipients' => [
            ['email' => 'a@example.com', 'template_data' => ['name' => 'Ada']],
            ['email' => 'b@example.com', 'template_data' => ['name' => 'Grace']],
        ],
    ]);
    echo "batch: {$batch['sent']} sent, {$batch['failed']} failed\n";

    // Check an address before adding it to a list.
    $verdict = $posta->emails->verify('user@example.com');
    echo "verify: {$verdict['status']} (score {$verdict['score']})\n";

    // Page through recent emails.
    $page = $posta->emails->list(['size' => 10, 'sort' => '-created_at']);
    echo 'emails: ' . count($page['data']) . ' of ' . $page['pageable']['total_elements'] . "\n";

    // Register a webhook. The secret is returned only here.
    $hook = $posta->webhooks->create(
        'https://example.com/hooks/posta',
        [WebhookEvent::EMAIL_SENT, WebhookEvent::EMAIL_FAILED]
    );
    echo "webhook {$hook['id']} registered; store secret {$hook['secret']}\n";
} catch (PostaException $err) {
    // Errors carry the API's status and message.
    fwrite(STDERR, "posta {$err->getStatusCode()}: " . ($err->getErrorInfo()['message'] ?? '') . "\n");
    exit(1);
}

/**
 * Authenticate an incoming Posta webhook and act on it.
 *
 * Verify the signature against the exact bytes received: decoding and
 * re-encoding the JSON changes them, and the HMAC will not match — so read
 * php://input, not a parsed body.
 */
function handleWebhook(string $rawBody, ?string $signature, string $secret): void
{
    if (!WebhookEvent::verifySignature($rawBody, $signature, $secret)) {
        http_response_code(401);
        exit;
    }

    $event = json_decode($rawBody, true);
    switch ($event['event'] ?? '') {
        case WebhookEvent::EMAIL_SENT:
            error_log("delivered: {$event['email_id']}");
            break;
        case WebhookEvent::EMAIL_FAILED:
            error_log("failed: {$event['email_id']}");
            break;
    }
    http_response_code(200);
}
