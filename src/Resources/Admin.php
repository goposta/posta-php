<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Platform administration.
 *
 * Covers users, plans, shared SMTP servers, domains across every workspace,
 * platform settings, announcements, the event log, and the update check.
 *
 * These endpoints accept only an administrator's session token — an API key is
 * never a valid credential here, whatever scopes it carries.
 */
class Admin
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add an account, bypassing self-registration. `role` is "admin" or "user".
     *
     * @param array{email: string, password: string, role: string, name?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createUser(array $request): array
    {
        return $this->http->post('/admin/users', $request);
    }

    /**
     * Return a page of platform accounts.
     *
     * @param array{page?: int, size?: int, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listUsers(array $options = []): array
    {
        return $this->http->getPage('/admin/users' . Http::query($options));
    }

    /**
     * Change an account's role or standing.
     *
     * @param array{role?: string, active?: bool, email_verified?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateUser(int $id, array $request): array
    {
        return $this->http->put('/admin/users/' . Http::seg($id), $request);
    }

    /**
     * Schedule an account for deletion after the usual grace period.
     *
     * @throws PostaException
     */
    public function deleteUser(int $id): void
    {
        $this->http->delete('/admin/users/' . Http::seg($id));
    }

    /**
     * Remove an account and its data immediately, skipping the grace period.
     *
     * This cannot be undone.
     *
     * @throws PostaException
     */
    public function forceDeleteUser(int $id): void
    {
        $this->http->delete('/admin/users/' . Http::seg($id) . '/force');
    }

    /**
     * Call off a scheduled account deletion.
     *
     * @throws PostaException
     */
    public function cancelUserDeletion(int $id): void
    {
        $this->http->post('/admin/users/' . Http::seg($id) . '/cancel-deletion');
    }

    /**
     * Turn off an account's two-factor authentication.
     *
     * For recovering a user who has lost their authenticator.
     *
     * @throws PostaException
     */
    public function disableUser2fa(int $id): void
    {
        $this->http->delete('/admin/users/' . Http::seg($id) . '/2fa');
    }

    /**
     * Sign an account out everywhere.
     *
     * @throws PostaException
     */
    public function revokeUserSessions(int $id): void
    {
        $this->http->post('/admin/users/' . Http::seg($id) . '/revoke-sessions');
    }

    /**
     * Return one account's usage figures.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function userMetrics(int $id): array
    {
        return $this->http->get('/admin/users/' . Http::seg($id) . '/metrics');
    }

    /**
     * Return the workspaces an account belongs to.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listUserWorkspaces(int $id): array
    {
        return $this->http->get('/admin/users/' . Http::seg($id) . '/workspaces') ?? [];
    }

    /**
     * Return the plan assigned to an account.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function userPlan(int $id): array
    {
        return $this->http->get('/admin/users/' . Http::seg($id) . '/plan');
    }

    /**
     * Put an account on a plan.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function assignUserPlan(int $userId, int $planId): array
    {
        return $this->http->post(
            '/admin/users/' . Http::seg($userId) . '/plan',
            ['plan_id' => $planId]
        );
    }

    /**
     * Return the plan assigned to a workspace.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function workspacePlan(int $workspaceId): array
    {
        return $this->http->get('/admin/workspaces/' . Http::seg($workspaceId) . '/plan');
    }

    /**
     * Put a workspace on a plan.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function assignWorkspacePlan(int $workspaceId, int $planId): array
    {
        return $this->http->post(
            '/admin/workspaces/' . Http::seg($workspaceId) . '/plan',
            ['plan_id' => $planId]
        );
    }

    /**
     * Add a plan.
     *
     * @param array{name: string, description?: string, is_default?: bool, daily_rate_limit?: int, hourly_rate_limit?: int, max_batch_size?: int, max_attachment_size_mb?: int, max_api_keys?: int, max_domains?: int, max_smtp_servers?: int, max_workspaces?: int, email_log_retention_days?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createPlan(array $request): array
    {
        return $this->http->post('/admin/plans', $request);
    }

    /**
     * Return a page of plans.
     *
     * @param array{page?: int, size?: int, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listPlans(array $options = []): array
    {
        return $this->http->getPage('/admin/plans' . Http::query($options));
    }

    /**
     * Return one plan.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getPlan(int $id): array
    {
        return $this->http->get('/admin/plans/' . Http::seg($id));
    }

    /**
     * Change a plan.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updatePlan(int $id, array $request): array
    {
        return $this->http->put('/admin/plans/' . Http::seg($id), $request);
    }

    /**
     * Remove a plan. Accounts on it fall back to the default plan.
     *
     * @throws PostaException
     */
    public function deletePlan(int $id): void
    {
        $this->http->delete('/admin/plans/' . Http::seg($id));
    }

    /**
     * Make a plan the one new accounts receive.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function setDefaultPlan(int $id): array
    {
        return $this->http->patch('/admin/plans/' . Http::seg($id) . '/default');
    }

    /**
     * Register a shared SMTP server.
     *
     * Offered to workspaces that have configured none of their own.
     * `security_mode` is "permissive" or "strict".
     *
     * @param array{name: string, host: string, port: int, username?: string, password?: string, encryption?: string, security_mode?: string, allowed_domains?: string[], max_retries?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createServer(array $request): array
    {
        return $this->http->post('/admin/servers', $request);
    }

    /**
     * Return a page of shared SMTP servers.
     *
     * @param array{page?: int, size?: int, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listServers(array $options = []): array
    {
        return $this->http->getPage('/admin/servers' . Http::query($options));
    }

    /**
     * Return one shared SMTP server.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getServer(int $id): array
    {
        return $this->http->get('/admin/servers/' . Http::seg($id));
    }

    /**
     * Change a shared SMTP server. Omit `password` to keep the stored one.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateServer(int $id, array $request): array
    {
        return $this->http->put('/admin/servers/' . Http::seg($id), $request);
    }

    /**
     * Remove a shared SMTP server.
     *
     * @throws PostaException
     */
    public function deleteServer(int $id): void
    {
        $this->http->delete('/admin/servers/' . Http::seg($id));
    }

    /**
     * Put a shared SMTP server back into rotation.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function enableServer(int $id): array
    {
        return $this->http->post('/admin/servers/' . Http::seg($id) . '/enable');
    }

    /**
     * Take a shared SMTP server out of rotation without deleting it.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function disableServer(int $id): array
    {
        return $this->http->post('/admin/servers/' . Http::seg($id) . '/disable');
    }

    /**
     * Open a connection to a shared SMTP server, without sending anything.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function testServer(int $id): array
    {
        return $this->http->post('/admin/servers/' . Http::seg($id) . '/test');
    }

    /**
     * Return a page of domains across every workspace.
     *
     * @param array{page?: int, size?: int, search?: string, status?: string, workspace?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listDomains(array $options = []): array
    {
        return $this->http->getPage('/admin/domains' . Http::query($options));
    }

    /**
     * Return one domain with the DNS records it needs.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getDomain(int $id): array
    {
        return $this->http->get('/admin/domains/' . Http::seg($id));
    }

    /**
     * Re-run DNS verification for a domain in any workspace.
     *
     * @throws PostaException
     */
    public function verifyDomain(int $id): void
    {
        $this->http->post('/admin/domains/' . Http::seg($id) . '/verify');
    }

    /**
     * Mark a domain's ownership verified, or withdraw that, without a lookup.
     *
     * An override for a domain that cannot publish the record. $reason is
     * recorded in the audit log.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function setDomainVerification(int $id, bool $ownershipVerified, string $reason = ''): array
    {
        return $this->http->put(
            '/admin/domains/' . Http::seg($id) . '/verification',
            Http::body(['ownership_verified' => $ownershipVerified, 'reason' => $reason ?: null])
        );
    }

    /**
     * Return the deployment's overall usage and runtime health.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function metrics(): array
    {
        return $this->http->get('/admin/metrics');
    }

    /**
     * Return platform-wide email volume and status analytics.
     *
     * @param array{from?: string, to?: string, status?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function analytics(array $options = []): array
    {
        return $this->http->get('/admin/analytics' . Http::query($options));
    }

    /**
     * Return platform-wide delivery and bounce trends.
     *
     * @param array{from?: string, to?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function dashboardAnalytics(array $options = []): array
    {
        return $this->http->get('/admin/analytics/dashboard' . Http::query($options));
    }

    /**
     * Return platform-wide deliverability by recipient provider.
     *
     * @param array{from?: string, to?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function providerAnalytics(array $options = []): array
    {
        return $this->http->get('/admin/analytics/providers' . Http::query($options));
    }

    /**
     * Return a page of platform events.
     *
     * @param array{page?: int, size?: int, category?: string, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listEvents(array $options = []): array
    {
        return $this->http->getPage('/admin/events' . Http::query($options));
    }

    /**
     * Return one platform event with its full metadata.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getEvent(int $id): array
    {
        return $this->http->get('/admin/events/' . Http::seg($id));
    }

    /**
     * Return the platform's configuration entries.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function settings(): array
    {
        return $this->http->get('/admin/settings') ?? [];
    }

    /**
     * Change platform configuration entries.
     *
     * Only the keys supplied are touched. Each entry is a
     * ['key' => …, 'value' => …] pair.
     *
     * @param array<int, array{key: string, value: string}> $settings
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function updateSettings(array $settings): array
    {
        return $this->http->put('/admin/settings', ['settings' => $settings]) ?? [];
    }

    /**
     * Broadcast a notice to every user.
     *
     * `severity` is "info", "warning", or "critical".
     *
     * @param array{title: string, message?: string, severity?: string, link?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createAnnouncement(array $request): array
    {
        return $this->http->post('/admin/announcements', $request);
    }

    /**
     * Return a page of announcements.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listAnnouncements(array $options = []): array
    {
        return $this->http->getPage('/admin/announcements' . Http::query($options));
    }

    /**
     * Retract an announcement, removing it from every user's notifications.
     *
     * @throws PostaException
     */
    public function deleteAnnouncement(int $id): void
    {
        $this->http->delete('/admin/announcements/' . Http::seg($id));
    }

    /**
     * Report whether a newer Posta release is available.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateStatus(): array
    {
        return $this->http->get('/admin/update');
    }

    /**
     * Hide the update notice for one version, until a later one appears.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function dismissUpdate(string $version): array
    {
        return $this->http->post('/admin/update/dismiss', ['version' => $version]);
    }

    /**
     * Return every configured SSO provider, including hidden and disabled ones.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listOAuthProviders(): array
    {
        return $this->http->get('/admin/oauth/providers') ?? [];
    }

    /**
     * Configure an SSO provider.
     *
     * For a standards-compliant OIDC provider `issuer` alone is enough — Posta
     * discovers the endpoints. Set `auth_url`, `token_url`, and
     * `userinfo_url` only for one that publishes no discovery document.
     *
     * @param array{name: string, slug: string, type: string, client_id: string, client_secret: string, issuer?: string, auth_url?: string, token_url?: string, userinfo_url?: string, scopes?: string, allowed_domains?: string, auto_register?: bool, hidden?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createOAuthProvider(array $request): array
    {
        return $this->http->post('/admin/oauth/providers', $request);
    }

    /**
     * Change an SSO provider. Omit `client_secret` to keep the stored one.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateOAuthProvider(int $id, array $request): array
    {
        return $this->http->put('/admin/oauth/providers/' . Http::seg($id), $request);
    }

    /**
     * Remove an SSO provider.
     *
     * Accounts linked to it fall back to password sign-in.
     *
     * @throws PostaException
     */
    public function deleteOAuthProvider(int $id): void
    {
        $this->http->delete('/admin/oauth/providers/' . Http::seg($id));
    }
}
