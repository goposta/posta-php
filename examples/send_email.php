<?php

require_once __DIR__ . '/../src/PostaClient.php';
require_once __DIR__ . '/../src/PostaException.php';

use Posta\PostaClient;
use Posta\PostaException;

$client = new PostaClient('https://posta.example.com', 'your-api-key');

try {
    // Send a single email
    $response = $client->sendEmail([
        'from'    => 'sender@example.com',
        'to'      => ['recipient@example.com'],
        'subject' => 'Hello from Posta',
        'html'    => '<h1>Hello!</h1><p>This is a test email.</p>',
    ]);
    echo "Email sent! ID: {$response['id']}, Status: {$response['status']}\n";

    // Send a template email
    $response = $client->sendTemplateEmail([
        'template'      => 'welcome',
        'to'            => ['user@example.com'],
        'template_data' => ['name' => 'John'],
    ]);
    echo "Template email sent! ID: {$response['id']}\n";

    // Send batch emails
    $response = $client->sendBatch([
        'template'   => 'newsletter',
        'recipients' => [
            ['email' => 'user1@example.com', 'template_data' => ['name' => 'Alice']],
            ['email' => 'user2@example.com', 'template_data' => ['name' => 'Bob']],
        ],
    ]);
    echo "Batch sent! Total: {$response['total']}, Sent: {$response['sent']}\n";

    // Check email status
    $status = $client->getEmailStatus($response['results'][0]['id']);
    echo "Email status: {$status['status']}\n";
} catch (PostaException $e) {
    echo "Error: {$e->getMessage()}\n";
}
