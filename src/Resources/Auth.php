<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * The public endpoints: signing in, registering, recovering a password.
 *
 * They need no credential, so a client built for them can be created with an
 * empty key.
 */
class Auth
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Exchange an email and password for a session token.
     *
     * Supply $twoFactorCode when the account has 2FA enabled. Pass the
     * returned `token` to PostaClient::withToken() to reach account-level
     * endpoints.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function login(string $email, string $password, string $twoFactorCode = ''): array
    {
        return $this->http->post('/auth/login', Http::body([
            'email'           => $email,
            'password'        => $password,
            'two_factor_code' => $twoFactorCode ?: null,
        ]));
    }

    /**
     * Create an account, when the deployment allows self-registration.
     *
     * Check first with registrationStatus().
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function register(string $name, string $email, string $password): array
    {
        return $this->http->post('/auth/register', [
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);
    }

    /**
     * Report whether self-registration is enabled.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function registrationStatus(): array
    {
        return $this->http->get('/auth/registration-status') ?? [];
    }

    /**
     * Email a reset link.
     *
     * It always succeeds, whether or not the address has an account, so it
     * cannot be used to enumerate users.
     *
     * @throws PostaException
     */
    public function forgotPassword(string $email): void
    {
        $this->http->post('/auth/forgot-password', ['email' => $email]);
    }

    /**
     * Redeem a reset token and set a new password.
     *
     * @throws PostaException
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $this->http->post('/auth/reset-password', [
            'token'        => $token,
            'new_password' => $newPassword,
        ]);
    }

    /**
     * Redeem the token from a verification email.
     *
     * @throws PostaException
     */
    public function verifyEmail(string $token): void
    {
        $this->http->get('/auth/verify-email' . Http::query(['token' => $token]));
    }

    /**
     * Report which SSO provider, if any, an email domain is bound to.
     *
     * Lets a login page send the user straight to it.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function discoverSso(string $email): array
    {
        return $this->http->post('/auth/oauth/discover', ['email' => $email]);
    }

    /**
     * Return the SSO providers offered on the sign-in page.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function listOAuthProviders(): array
    {
        return $this->http->get('/auth/oauth/providers') ?? [];
    }

    /**
     * Return the URL that begins an OAuth sign-in with $provider.
     *
     * Redirect the browser here; Posta handles the callback itself.
     */
    public function authorizeUrl(string $provider): string
    {
        return $this->http->baseUrl() . '/auth/oauth/' . Http::seg($provider) . '/authorize';
    }
}
