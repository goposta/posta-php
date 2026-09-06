# Posta PHP Client

Official PHP client for the [Posta](https://github.com/goposta/posta) email
platform.

It covers the whole Posta API: transactional and templated sending, batch
sends, address verification, templates with versions and localizations,
campaigns, subscribers and lists, suppressions and bounces, domains, SMTP
servers and relay credentials, webhooks, web forms and the messages they
collect, inbound email, workspace administration, and the platform admin
surface.

Uses `ext-curl` and `ext-json` only — no Composer dependencies.

## Installation

```bash
composer require goposta/posta-php
```

**Requires:** PHP 8.1+

## Quick start

```php
use Posta\PostaClient;

$posta = new PostaClient('https://posta.example.com', 'psk_your_api_key');

$resp = $posta->emails->send([
    'from'    => 'Acme <hello@example.com>',
    'to'      => ['user@example.com'],
    'subject' => 'Hello from Posta',
    'html'    => '<h1>Hello!</h1>',
]);

echo "sent: id={$resp['id']} status={$resp['status']}\n";
```

## Credentials

Most machine-facing endpoints take an API key:

```php
$posta = new PostaClient('https://posta.example.com', 'psk_...');
```

Account-level endpoints (`/users/me/*`) and the platform admin surface accept
only a user session token — an API key is never a valid credential there:

```php
$auth = (new PostaClient($baseUrl, ''))->auth->login('admin@example.com', $password);
$admin = PostaClient::withToken($baseUrl, $auth['token']);
```

### Workspaces

Workspace-scoped endpoints resolve the active workspace from the
`X-Posta-Workspace-Id` header. A workspace-bound API key already carries its
workspace; an account-wide key or a user session must name one:

```php
$posta = new PostaClient($baseUrl, $apiKey, timeout: 30, workspaceId: 42);
```

### API key scopes

A key reaches only what its scopes allow. `Scope::SEND` covers the public send
API; `READ` and `WRITE` cover reading and mutating workspace resources;
`WEBHOOKS` covers webhook management; `ADMIN` covers tenant administration
(keys, members, settings); `ALL` grants everything.

A 403 from an endpoint you expect to work usually means a missing scope —
`$err->isForbidden()` distinguishes it.

## Client options

```php
$posta = new PostaClient(
    'https://posta.example.com',
    'psk_...',
    timeout: 15,                                    // seconds, default 30
    workspaceId: 42,
    headers: ['X-Request-Source' => 'batch-job'],
    userAgent: 'my-app/1.0',
);
```

## Resources

| Property | Covers |
|---|---|
| `emails` | send, sendTemplate, sendBatch, preview, verify, status, retry, list, get |
| `bounces` | list, record |
| `suppressions` | list, add, remove |
| `webhooks` | list, create, delete, deliveries |
| `templates` | CRUD, versions, localizations, preview, sendTest, import/export |
| `languages`, `stylesheets` | CRUD |
| `domains` | add, list, get, verify, delete |
| `smtpServers`, `smtpCredentials` | CRUD, test, revoke |
| `subscribers` | CRUD, JSON and CSV bulk import |
| `subscriberLists` | CRUD, members, segments, subscribe/unsubscribe/resubscribe |
| `unsubscribeLists`, `contacts` | CRUD / read |
| `campaigns` | CRUD, send, pause, resume, cancel, duplicate, messages, analytics |
| `analytics` | emails, dashboard, providers, dashboardStats |
| `forms` | CRUD, rotateKey, snippet, nonce, public submit |
| `messages`, `messageFilters` | list, triage, reply, attachments; filter CRUD and dry-run |
| `inbound` | list, get, retry, raw `.eml`, attachments |
| `apiKeys` | create, list, get, revoke, delete |
| `workspaces` | CRUD, members, invitations, settings, SSO, audit log, export/import, GDPR |
| `users` | profile, password, 2FA, sessions, settings, notifications (session credential) |
| `auth` | login, register, password reset, email verification, SSO discovery |
| `admin` | users, plans, shared servers, domains, settings, announcements, events, metrics |
| `system` | info, healthz, readyz |

Every method returns the decoded `data` from the API envelope; list methods
return the whole envelope, so `pageable` is reachable alongside the rows.

## Examples

### Templated and batch sends

```php
$posta->emails->sendTemplate([
    'template'      => 'welcome',
    'to'            => ['user@example.com'],
    'template_data' => ['name' => 'Ada'],
]);

$batch = $posta->emails->sendBatch([
    'template'   => 'welcome',
    'recipients' => [
        ['email' => 'a@example.com', 'template_data' => ['name' => 'Ada']],
        ['email' => 'b@example.com', 'template_data' => ['name' => 'Grace']],
    ],
]);
echo "{$batch['sent']} sent, {$batch['failed']} failed\n";
```

Validate without sending by passing `true` as the second argument:

```php
$report = $posta->emails->send($request, true);
```

### One-click unsubscribe

Reference a Posta-managed unsubscribe list and Posta mints the signed one-click
URL, recording opt-outs against that list alone:

```php
$posta->emails->send([
    'from'        => 'news@example.com',
    'to'          => ['user@example.com'],
    'subject'     => 'This week',
    'html'        => '<p>…</p>',
    'unsubscribe' => ['list_id' => 7],
]);
```

### Templates, versions, localizations

```php
$tpl = $posta->templates->create(['name' => 'welcome', 'default_language' => 'en']);
$ver = $posta->templates->createVersion($tpl['id']);
$posta->templates->createLocalization($tpl['id'], $ver['id'], [
    'language'         => 'en',
    'subject_template' => 'Welcome, {{.name}}',
    'html_template'    => '<h1>Welcome, {{.name}}</h1>',
]);
$posta->templates->activateVersion($tpl['id'], $ver['id']);
```

### Campaigns

```php
$camp = $posta->campaigns->create([
    'name'        => 'Launch',
    'subject'     => "We're live",
    'from_email'  => 'news@example.com',
    'list_id'     => $listId,
    'template_id' => $tpl['id'],
]);
$posta->campaigns->send($camp['id']);

$stats = $posta->campaigns->analytics($camp['id']);
echo "open rate {$stats['analytics']['open_rate']}%\n";
```

### Paging

`page` is zero-based; omitting `size` lets the server apply its default.

```php
$page = $posta->emails->list(['page' => 0, 'size' => 50, 'sort' => '-created_at']);
echo $page['pageable']['total_elements'] . "\n";
foreach ($page['data'] as $email) {
    echo $email['subject'] . "\n";
}
```

### Verifying webhooks

Posta signs each delivery with HMAC-SHA256 over the raw body, in the
`X-Posta-Signature` header as `sha256=<hex>`. Verify against the exact bytes
received — re-serializing the JSON changes them:

```php
use Posta\WebhookEvent;

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_POSTA_SIGNATURE'] ?? null;

if (!WebhookEvent::verifySignature($raw, $sig, $secret)) {
    http_response_code(401);
    exit;
}

$event = json_decode($raw, true);
switch ($event['event']) {
    case WebhookEvent::EMAIL_SENT:
        error_log("delivered: {$event['email_id']}");
        break;
    case WebhookEvent::EMAIL_FAILED:
        error_log("failed: {$event['email_id']}");
        break;
}
http_response_code(200);
```

Event constants live on `Posta\WebhookEvent`: `EMAIL_SENT`, `EMAIL_FAILED`,
`EMAIL_INBOUND`, `EMAIL_UNSUBSCRIBED`, `EMAIL_COMPLAINED`, `CAMPAIGN_STARTED`,
`CAMPAIGN_COMPLETED`, `MESSAGE_RECEIVED`, `MESSAGE_SPAM`.

### Web forms

```php
$form = $posta->forms->create([
    'name'            => 'Contact',
    'allowed_origins' => ['https://example.com'],
    'strict_origin'   => true,
    'notify_emails'   => ['team@example.com'],
]);

$snippet = $posta->forms->snippet($form['id']);
echo $snippet['html'];

$inbox = $posta->messages->list(['state' => 'new']);
```

## Errors

Non-2xx responses throw `PostaException`, carrying the status and the decoded
error envelope:

```php
use Posta\PostaException;

try {
    $posta->emails->send($request);
} catch (PostaException $err) {
    error_log("posta {$err->getStatusCode()}: " . ($err->getErrorInfo()['message'] ?? ''));
    if ($err->isRateLimited()) {
        retryLater();
    }
}
```

Methods cover the common cases: `isNotFound()`, `isUnauthorized()`,
`isForbidden()`, `isRateLimited()`, plus `getErrorCode()` for the API's
structured error code.

## License

Apache-2.0
