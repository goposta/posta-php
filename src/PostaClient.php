<?php

declare(strict_types=1);

namespace Posta;

use Posta\Resources\Admin;
use Posta\Resources\Analytics;
use Posta\Resources\ApiKeys;
use Posta\Resources\Auth;
use Posta\Resources\Bounces;
use Posta\Resources\Campaigns;
use Posta\Resources\Contacts;
use Posta\Resources\Domains;
use Posta\Resources\Emails;
use Posta\Resources\Forms;
use Posta\Resources\Inbound;
use Posta\Resources\Languages;
use Posta\Resources\MessageFilters;
use Posta\Resources\Messages;
use Posta\Resources\SmtpCredentials;
use Posta\Resources\SmtpServers;
use Posta\Resources\Stylesheets;
use Posta\Resources\SubscriberLists;
use Posta\Resources\Subscribers;
use Posta\Resources\Suppressions;
use Posta\Resources\System;
use Posta\Resources\Templates;
use Posta\Resources\UnsubscribeLists;
use Posta\Resources\Users;
use Posta\Resources\Webhooks;
use Posta\Resources\Workspaces;

/**
 * Client for the Posta email platform.
 *
 * Its public properties group the API by resource. Every method returns the
 * decoded `data` from the API envelope and throws {@see PostaException} on a
 * non-2xx response.
 *
 * ## Credentials
 *
 * Most machine-facing endpoints take an API key. Account-level endpoints
 * (`/users/me/*`) and the platform admin surface accept only a user session
 * token, which {@see PostaClient::withToken()} supplies.
 *
 * ## Workspaces
 *
 * Workspace-scoped endpoints resolve the active workspace from the
 * `X-Posta-Workspace-Id` header. A workspace-bound API key carries its
 * workspace already; an account-wide key or a user session must name one with
 * $workspaceId.
 *
 * Usage:
 *   $posta = new PostaClient('https://posta.example.com', 'psk_your_api_key');
 *   $resp = $posta->emails->send([
 *       'from'    => 'Acme <hello@example.com>',
 *       'to'      => ['user@example.com'],
 *       'subject' => 'Hello from Posta',
 *       'html'    => '<h1>Hello!</h1>',
 *   ]);
 */
class PostaClient
{
    private Http $http;

    /** Sends mail and reads the resulting delivery records. */
    public Emails $emails;
    /** Reads recorded bounces and complaints. */
    public Bounces $bounces;
    /** Manages the workspace suppression list. */
    public Suppressions $suppressions;
    /** Registers webhook endpoints and reads delivery attempts. */
    public Webhooks $webhooks;
    /** Manages templates, versions, and localizations. */
    public Templates $templates;
    /** Manages the workspace's template languages. */
    public Languages $languages;
    /** Manages reusable CSS for templates. */
    public Stylesheets $stylesheets;
    /** Manages sending domains and their DNS verification. */
    public Domains $domains;
    /** Manages the SMTP servers Posta delivers through. */
    public SmtpServers $smtpServers;
    /** Manages credentials for the SMTP relay listener. */
    public SmtpCredentials $smtpCredentials;
    /** Manages subscriber records and bulk imports. */
    public Subscribers $subscribers;
    /** Manages lists, their members, and opt-outs. */
    public SubscriberLists $subscriberLists;
    /** Manages the lists behind List-Unsubscribe headers. */
    public UnsubscribeLists $unsubscribeLists;
    /** Reads the derived contact view of everyone mailed. */
    public Contacts $contacts;
    /** Manages bulk campaigns and their lifecycle. */
    public Campaigns $campaigns;
    /** Reads delivery and engagement analytics. */
    public Analytics $analytics;
    /** Manages web form endpoints and their embed snippets. */
    public Forms $forms;
    /** Reads and triages web form submissions. */
    public Messages $messages;
    /** Manages the spam filters applied to submissions. */
    public MessageFilters $messageFilters;
    /** Reads inbound email received by Posta. */
    public Inbound $inbound;
    /** Manages the workspace's API keys. */
    public ApiKeys $apiKeys;
    /** Manages workspaces, members, invitations, and settings. */
    public Workspaces $workspaces;
    /** Manages the signed-in account (session credential only). */
    public Users $users;
    /** Login, registration, and password recovery. */
    public Auth $auth;
    /** Platform administration (admin session only). */
    public Admin $admin;
    /** Build and health information. */
    public System $system;

    /**
     * @param string                $baseUrl     Base URL of the Posta instance, e.g. https://posta.example.com
     * @param string                $apiKey      An API key (psk_…), or a session token via withToken()
     * @param int                   $timeout     HTTP timeout in seconds (default: 30)
     * @param int|null              $workspaceId Active workspace for workspace-scoped endpoints
     * @param array<string, string> $headers     Extra headers sent with every request
     * @param string|null           $userAgent   Overrides the User-Agent header
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        int $timeout = 30,
        ?int $workspaceId = null,
        array $headers = [],
        ?string $userAgent = null
    ) {
        $this->http = new Http($baseUrl, $apiKey, $timeout, $workspaceId, $headers, $userAgent);

        $this->emails = new Emails($this->http);
        $this->bounces = new Bounces($this->http);
        $this->suppressions = new Suppressions($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->templates = new Templates($this->http);
        $this->languages = new Languages($this->http);
        $this->stylesheets = new Stylesheets($this->http);
        $this->domains = new Domains($this->http);
        $this->smtpServers = new SmtpServers($this->http);
        $this->smtpCredentials = new SmtpCredentials($this->http);
        $this->subscribers = new Subscribers($this->http);
        $this->subscriberLists = new SubscriberLists($this->http);
        $this->unsubscribeLists = new UnsubscribeLists($this->http);
        $this->contacts = new Contacts($this->http);
        $this->campaigns = new Campaigns($this->http);
        $this->analytics = new Analytics($this->http);
        $this->forms = new Forms($this->http);
        $this->messages = new Messages($this->http);
        $this->messageFilters = new MessageFilters($this->http);
        $this->inbound = new Inbound($this->http);
        $this->apiKeys = new ApiKeys($this->http);
        $this->workspaces = new Workspaces($this->http);
        $this->users = new Users($this->http);
        $this->auth = new Auth($this->http);
        $this->admin = new Admin($this->http);
        $this->system = new System($this->http);
    }

    /**
     * Build a client authenticated with a user session token (JWT).
     *
     * As returned by {@see Auth::login()}. Account-level endpoints under
     * /users/me and the platform admin surface accept only this credential.
     *
     * @param array<string, string> $headers
     */
    public static function withToken(
        string $baseUrl,
        string $token,
        int $timeout = 30,
        ?int $workspaceId = null,
        array $headers = [],
        ?string $userAgent = null
    ): self {
        return new self($baseUrl, $token, $timeout, $workspaceId, $headers, $userAgent);
    }

    // ── Compatibility ────────────────────────────────────────────────────
    //
    // Kept for source compatibility with earlier releases, which exposed the
    // send surface directly on the client. New code should use the resource
    // properties, which cover the whole API rather than this subset.

    /**
     * @deprecated Use $client->emails->send().
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendEmail(array $request, bool $dryRun = false): array
    {
        return $this->emails->send($request, $dryRun);
    }

    /**
     * @deprecated Use $client->emails->sendTemplate().
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendTemplateEmail(array $request, bool $dryRun = false): array
    {
        return $this->emails->sendTemplate($request, $dryRun);
    }

    /**
     * @deprecated Use $client->emails->sendBatch().
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendBatch(array $request, bool $dryRun = false): array
    {
        return $this->emails->sendBatch($request, $dryRun);
    }

    /**
     * @deprecated Use $client->emails->preview().
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function previewTemplate(array $request): array
    {
        return $this->emails->preview($request);
    }

    /**
     * @deprecated Use $client->emails->verify().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function verifyEmail(string $email, bool $fresh = false): array
    {
        return $this->emails->verify($email, $fresh);
    }

    /**
     * @deprecated Use $client->emails->status().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getEmailStatus(string $emailId): array
    {
        return $this->emails->status($emailId);
    }

    /**
     * @deprecated Use $client->emails->retry().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function retryEmail(string $emailId): array
    {
        return $this->emails->retry($emailId);
    }

    /**
     * @deprecated Use $client->emails->list(), which can also filter and sort.
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listEmails(int $page = 0, int $size = 20): array
    {
        return $this->emails->list(['page' => $page, 'size' => $size]);
    }

    /**
     * @deprecated Use $client->emails->get().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getEmail(string $id): array
    {
        return $this->emails->get($id);
    }

    /**
     * @deprecated Use $client->bounces->list().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listBounces(int $page = 0, int $size = 20): array
    {
        return $this->bounces->list(['page' => $page, 'size' => $size]);
    }

    /**
     * @deprecated Use $client->webhooks->list().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listWebhooks(int $page = 0, int $size = 20): array
    {
        return $this->webhooks->list(['page' => $page, 'size' => $size]);
    }

    /**
     * @deprecated Use $client->webhooks->create().
     * @param string[] $events
     * @param string[] $filters
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createWebhook(string $url, array $events, array $filters = []): array
    {
        return $this->webhooks->create($url, $events, $filters);
    }

    /**
     * @deprecated Use $client->webhooks->delete().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function deleteWebhook(int $id): array
    {
        $this->webhooks->delete($id);
        return [];
    }

    /**
     * @deprecated Use $client->webhooks->listDeliveries().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listWebhookDeliveries(int $page = 0, int $size = 20): array
    {
        return $this->webhooks->listDeliveries(['page' => $page, 'size' => $size]);
    }

    /**
     * @deprecated Use $client->subscriberLists->subscribe().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function subscribeToList(string $email, string $list, string $name = ''): array
    {
        return $this->subscriberLists->subscribe($email, $list, $name);
    }

    /**
     * @deprecated Use $client->subscriberLists->unsubscribe().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function unsubscribeFromList(int $listId, string $email, string $reason = ''): array
    {
        return $this->subscriberLists->unsubscribe($listId, $email, $reason);
    }

    /**
     * @deprecated Use $client->subscriberLists->resubscribe().
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function resubscribeToList(int $listId, string $email): array
    {
        return $this->subscriberLists->resubscribe($listId, $email);
    }
}
