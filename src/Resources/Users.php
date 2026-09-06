<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages the signed-in account.
 *
 * Covers profile, password, two-factor authentication, sessions, settings, and
 * notifications. These endpoints accept only a user session token — an API key
 * is never a valid credential here.
 */
class Users
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return the signed-in account's profile.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function me(): array
    {
        return $this->http->get('/users/me');
    }

    /**
     * Change the display name, and whether sends require a verified domain.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateProfile(string $name, ?bool $requireVerifiedDomain = null): array
    {
        return $this->http->put('/users/me', Http::body([
            'name'                    => $name,
            'require_verified_domain' => $requireVerifiedDomain,
        ]));
    }

    /**
     * Set a new password, confirming the current one.
     *
     * @throws PostaException
     */
    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $this->http->put('/users/me/password', [
            'current_password' => $currentPassword,
            'new_password'     => $newPassword,
        ]);
    }

    /**
     * Send the address confirmation email again.
     *
     * @throws PostaException
     */
    public function resendVerificationEmail(): void
    {
        $this->http->post('/users/me/verify-email/resend');
    }

    /**
     * Return the quota and feature set applied to the account.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function plan(): array
    {
        return $this->http->get('/users/me/plan');
    }

    /**
     * Begin two-factor enrolment and return the TOTP secret.
     *
     * Two-factor is not active until verify2fa() confirms a code from it. The
     * `url` field is an otpauth:// URI, usually rendered as a QR code.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function setup2fa(): array
    {
        return $this->http->post('/users/me/2fa/setup');
    }

    /**
     * Confirm a code from the authenticator and switch two-factor on.
     *
     * @throws PostaException
     */
    public function verify2fa(string $code): void
    {
        $this->http->post('/users/me/2fa/verify', ['code' => $code]);
    }

    /**
     * Switch two-factor off, confirming a current code.
     *
     * @throws PostaException
     */
    public function disable2fa(string $code): void
    {
        $this->http->post('/users/me/2fa/disable', ['code' => $code]);
    }

    /**
     * Schedule the account for deletion after a grace period.
     *
     * cancelDeletion() reverses it while the grace period lasts.
     *
     * @throws PostaException
     */
    public function requestDeletion(): void
    {
        $this->http->post('/users/me/delete');
    }

    /**
     * Call off a scheduled account deletion.
     *
     * @throws PostaException
     */
    public function cancelDeletion(): void
    {
        $this->http->post('/users/me/cancel-deletion');
    }

    /**
     * Return the account's active sessions.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listSessions(): array
    {
        return $this->http->get('/users/me/sessions') ?? [];
    }

    /**
     * Sign one session out.
     *
     * @throws PostaException
     */
    public function revokeSession(int $id): void
    {
        $this->http->delete('/users/me/sessions/' . Http::seg($id));
    }

    /**
     * Sign out every session but this one.
     *
     * What to call after a password change on a possibly compromised account.
     *
     * @throws PostaException
     */
    public function revokeOtherSessions(): void
    {
        $this->http->post('/users/me/sessions/revoke-others');
    }

    /**
     * Sign out the session making the request.
     *
     * @throws PostaException
     */
    public function logout(): void
    {
        $this->http->post('/users/me/sessions/logout');
    }

    /**
     * Choose the workspace a request lands in when it names none.
     *
     * @throws PostaException
     */
    public function setDefaultWorkspace(int $workspaceId): void
    {
        $this->http->put('/users/me/default-workspace', ['workspace_id' => $workspaceId]);
    }

    /**
     * Return a page of the account's own audit events.
     *
     * @param array{page?: int, size?: int, category?: string, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function auditLog(array $options = []): array
    {
        return $this->http->getPage('/users/me/audit-log' . Http::query($options));
    }

    /**
     * Return the account's settings.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function settings(): array
    {
        return $this->http->get('/users/me/settings');
    }

    /**
     * Change the account's settings.
     *
     * Accepts the sending defaults (default_sender_email, default_sender_name,
     * default_language, default_template_id) and the notification preferences
     * (email_notifications, daily_report, notify_bounce_alerts,
     * notify_api_key_expiry, notify_new_message, notify_workspace_activity).
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateSettings(array $request): array
    {
        return $this->http->put('/users/me/settings', $request);
    }

    /**
     * Return the account's notifications.
     *
     * `open` keeps only notifications that have not been dismissed; `before`
     * pages backwards from a notification id.
     *
     * @param array{unread?: bool, open?: bool, category?: string, before?: int, limit?: int, scoped?: bool} $options
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listNotifications(array $options = []): array
    {
        return $this->http->get('/users/me/notifications' . Http::query($options)) ?? [];
    }

    /**
     * Return the notifications meant for the dashboard banner.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listBannerNotifications(): array
    {
        return $this->http->get('/users/me/notifications/banner') ?? [];
    }

    /**
     * Return the unread and open notification totals, for a badge.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function notificationCounts(): array
    {
        return $this->http->get('/users/me/notifications/counts');
    }

    /**
     * Mark the given notifications read.
     *
     * @param int[] $ids
     * @throws PostaException
     */
    public function markNotificationsRead(array $ids): void
    {
        $this->http->post('/users/me/notifications/read', ['ids' => $ids]);
    }

    /**
     * Mark every notification read.
     *
     * @throws PostaException
     */
    public function markAllNotificationsRead(): void
    {
        $this->http->post('/users/me/notifications/read-all');
    }

    /**
     * Remove the given notifications from the list.
     *
     * @param int[] $ids
     * @throws PostaException
     */
    public function dismissNotifications(array $ids): void
    {
        $this->http->post('/users/me/notifications/dismiss', ['ids' => $ids]);
    }

    /**
     * Remove every notification from the list.
     *
     * @throws PostaException
     */
    public function dismissAllNotifications(): void
    {
        $this->http->post('/users/me/notifications/dismiss-all');
    }

    /**
     * Return the external identities linked to the account.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listLinkedOAuthAccounts(): array
    {
        return $this->http->get('/users/me/oauth') ?? [];
    }

    /**
     * Detach an external identity from the account.
     *
     * @throws PostaException
     */
    public function unlinkOAuthAccount(int $providerId): void
    {
        $this->http->delete('/users/me/oauth/' . Http::seg($providerId));
    }
}
