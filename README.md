# Posta PHP Client

A lightweight PHP client for the [Posta](https://github.com/goposta/posta) email API.

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## Installation

### Composer

```bash
composer require goposta/posta-php
```

### Manual

Copy `src/PostaClient.php` and `src/PostaException.php` into your project and include them.

## Usage

```php
use Posta\PostaClient;

$client = new PostaClient('https://posta.example.com', 'your-api-key');
```

### Send Email

```php
$response = $client->sendEmail([
    'from'    => 'sender@example.com',
    'to'      => ['recipient@example.com'],
    'subject' => 'Hello',
    'html'    => '<h1>Hello World</h1>',
]);
// $response = ['id' => '...', 'status' => 'queued']
```

### Send Template Email

```php
$response = $client->sendTemplateEmail([
    'template'      => 'welcome',
    'to'            => ['user@example.com'],
    'template_data' => ['name' => 'John'],
]);
```

### Send Batch Emails

```php
$response = $client->sendBatch([
    'template'   => 'newsletter',
    'recipients' => [
        ['email' => 'alice@example.com', 'template_data' => ['name' => 'Alice']],
        ['email' => 'bob@example.com',   'template_data' => ['name' => 'Bob']],
    ],
]);
// $response = ['total' => 2, 'sent' => 2, 'failed' => 0, ...]
```

### Get Email Status

```php
$status = $client->getEmailStatus('email-uuid');
// $status = ['id' => '...', 'status' => 'sent', 'retry_count' => 0, ...]
```

### Error Handling

```php
use Posta\PostaException;

try {
    $client->sendEmail([...]);
} catch (PostaException $e) {
    echo $e->getStatusCode(); // HTTP status code
    echo $e->getMessage();    // Error message
    $info = $e->getErrorInfo(); // Parsed error details (nullable)
}
```

## Contributing

Contributions are welcome! Please open an issue to discuss proposed changes before submitting a pull request.

## License

This project is licensed under the Apache License 2.0. See [LICENSE](LICENSE) for details.

---

## Copyright

Copyright © 2026 Jonas Kaninda
