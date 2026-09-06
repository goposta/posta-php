<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages workspaces and everything that governs one.
 *
 * Methods on "current" act on the workspace the credential resolves to — the
 * one a workspace-bound API key names, or the one given as $workspaceId when
 * the client is built.
 */
class Workspaces
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a workspace, owned by the caller.
     *
     * `seed_defaults` fills the new workspace with a starter set of languages
     * and templates.
     *
     * @param array{name: string, slug?: string, description?: string, default_language?: string, seed_defaults?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post('/workspaces', $request);
    }

    /**
     * Return every workspace the caller belongs to, with their role in each.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function list(): array
    {
        return $this->http->get('/workspaces') ?? [];
    }

    /**
     * Return the active workspace.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(): array
    {
        return $this->http->get(Http::WS);
    }

    /**
     * Change the active workspace.
     *
     * @param array{name?: string, description?: string, default_language?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(array $request): array
    {
        return $this->http->put(Http::WS, $request);
    }

    /**
     * Remove the active workspace and everything in it.
     *
     * This cannot be undone; export first with exportData().
     *
     * @throws PostaException
     */
    public function delete(): void
    {
        $this->http->delete(Http::WS);
    }

    /**
     * Return the workspace's members and their roles.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listMembers(): array
    {
        return $this->http->get(Http::WS . '/members') ?? [];
    }

    /**
     * Change a member's role: "owner", "admin", "editor", or "viewer".
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateMemberRole(int $memberId, string $role): array
    {
        return $this->http->put(Http::WS . '/members/' . Http::seg($memberId), ['role' => $role]);
    }

    /**
     * Remove a member from the workspace.
     *
     * @throws PostaException
     */
    public function removeMember(int $memberId): void
    {
        $this->http->delete(Http::WS . '/members/' . Http::seg($memberId));
    }

    /**
     * Offer workspace membership to an email address, at the given role.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function invite(string $email, string $role): array
    {
        return $this->http->post(Http::WS . '/invitations', ['email' => $email, 'role' => $role]);
    }

    /**
     * Return the workspace's pending invitations.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listInvitations(): array
    {
        return $this->http->get(Http::WS . '/invitations') ?? [];
    }

    /**
     * Withdraw a pending invitation.
     *
     * @throws PostaException
     */
    public function cancelInvitation(int $invitationId): void
    {
        $this->http->delete(Http::WS . '/invitations/' . Http::seg($invitationId));
    }

    /**
     * Return the invitations addressed to the signed-in user.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listMyInvitations(): array
    {
        return $this->http->get('/invitations') ?? [];
    }

    /**
     * Join a workspace using the token from an invitation email.
     *
     * @throws PostaException
     */
    public function acceptInvitation(string $token): void
    {
        $this->http->post('/invitations/accept', ['token' => $token]);
    }

    /**
     * Refuse an invitation using its token.
     *
     * @throws PostaException
     */
    public function declineInvitation(string $token): void
    {
        $this->http->post('/invitations/decline', ['token' => $token]);
    }

    /**
     * Join a workspace using an invitation id, which needs no token.
     *
     * @throws PostaException
     */
    public function acceptInvitationById(int $id): void
    {
        $this->http->post('/invitations/' . Http::seg($id) . '/accept');
    }

    /**
     * Refuse an invitation by its id.
     *
     * @throws PostaException
     */
    public function declineInvitationById(int $id): void
    {
        $this->http->post('/invitations/' . Http::seg($id) . '/decline');
    }

    /**
     * Return the workspace's settings.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function settings(): array
    {
        return $this->http->get(Http::WS . '/settings');
    }

    /**
     * Change the workspace's settings.
     *
     * @param array{default_sender_email?: string, default_sender_name?: string, require_verified_domain?: bool, bounce_auto_suppress?: bool, webhook_retry_count?: int, api_key_expiry_days?: int, timezone?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateSettings(array $request): array
    {
        return $this->http->put(Http::WS . '/settings', $request);
    }

    /**
     * Return the quota and feature set applied to the workspace.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function plan(): array
    {
        return $this->http->get(Http::WS . '/plan');
    }

    /**
     * Return a page of the workspace's audit events.
     *
     * @param array{page?: int, size?: int} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listAuditLog(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/audit-log' . Http::query($options));
    }

    /**
     * Return one audit event with its full metadata.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function getAuditEvent(int $id): array
    {
        return $this->http->get(Http::WS . '/audit-log/' . Http::seg($id));
    }

    /**
     * Return a portable snapshot of the workspace.
     *
     * For backup, or for moving it to another Posta deployment.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function exportData(): array
    {
        return $this->http->get(Http::WS . '/data/export');
    }

    /**
     * Restore a snapshot into the active workspace.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function importData(array $data): array
    {
        return $this->http->post(Http::WS . '/data/import', $data);
    }

    /**
     * Erase a data subject's contact, subscriber, and suppression records.
     *
     * Passing an empty email erases every contact in the workspace, so pass
     * the address you mean.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function deleteContactData(string $email): array
    {
        return $this->http->post(Http::WS . '/gdpr/delete-contacts', ['email' => $email]);
    }

    /**
     * Erase stored email records older than $olderThanDays.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function deleteEmailLogs(int $olderThanDays): array
    {
        return $this->http->post(
            Http::WS . '/gdpr/delete-email-logs',
            ['older_than_days' => $olderThanDays]
        );
    }

    /**
     * Return the workspace's single sign-on configuration.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sso(): array
    {
        return $this->http->get(Http::WS . '/sso');
    }

    /**
     * Configure single sign-on for the workspace.
     *
     * `allowed_domains` is a comma-separated list restricting which email
     * domains may sign in; `enforce_sso` refuses password logins for members
     * of this workspace.
     *
     * @param array{provider_id: int, allowed_domains?: string, auto_provision?: bool, enforce_sso?: bool} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function setSso(array $request): array
    {
        return $this->http->put(Http::WS . '/sso', $request);
    }

    /**
     * Remove the workspace's single sign-on configuration.
     *
     * @throws PostaException
     */
    public function deleteSso(): void
    {
        $this->http->delete(Http::WS . '/sso');
    }
}
