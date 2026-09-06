<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/** Reads build and health information. */
class System
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Return the running build's name, version, and commit.
     *
     * Authenticated: the exact build is what an attacker needs to match a
     * deployment against known CVEs.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function info(): array
    {
        return $this->http->get('/info');
    }

    /**
     * Report process liveness. Public: it needs no credential.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function healthz(): array
    {
        return $this->http->getRoot('/healthz');
    }

    /**
     * Report whether the database and Redis are reachable.
     *
     * This is what a load balancer should gate traffic on. Public.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function readyz(): array
    {
        return $this->http->getRoot('/readyz');
    }
}
