<?php

declare(strict_types=1);

namespace Posta;

/**
 * Scopes an API key can carry.
 *
 * A key reaches only what its scopes allow. Note that SEND grants none of the
 * others: a send-only key is confined to the public send API and cannot read
 * or modify workspace resources.
 */
final class Scope
{
    /** Sending, verification, and subscriber-list opt-ins. */
    public const SEND = 'send';
    /** Reading emails, bounces, webhook deliveries, and workspace resources. */
    public const READ = 'read';
    /** Mutating workspace resources. */
    public const WRITE = 'write';
    /** Managing webhook endpoints. */
    public const WEBHOOKS = 'webhooks';
    /** Tenant administration: keys, members, invitations, settings, SSO. */
    public const ADMIN = 'admin';
    /** Every scope. */
    public const ALL = '*';
}
