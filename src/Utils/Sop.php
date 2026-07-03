<?php

namespace Rockberpro\RosaRouter\Utils;

use Rockberpro\RosaRouter\Core\Response;

/**
 * Origin allowlist gate.
 *
 * Rejects requests whose Origin is not in API_ALLOW_ORIGIN. Shares the
 * matching rules with {@see Cors} via {@see OriginPolicy}.
 */
class Sop
{
    /**
     * Returns a denial Response when the origin is not allowed, otherwise null.
     * Never terminates the process, so it is safe in the stateful server mode.
     */
    public static function check(): ?Response
    {
        if (OriginPolicy::resolve() !== null) {
            return null;
        }

        return new Response([
            'message' => 'Access denied for your host'
        ], Response::FORBIDDEN);
    }
}
