<?php

namespace Rockberpro\RosaRouter\Utils;

use Rockberpro\RosaRouter\Core\Server;

/**
 * Resolves the request's Origin against the API_ALLOW_ORIGIN allowlist.
 *
 * API_ALLOW_ORIGIN is either '*' (allow any) or a comma-separated list of
 * origins (scheme + host [+ port]), e.g. "https://app.example.com,http://localhost:3000".
 * Shared by Cors and Sop so the matching rules live in one place.
 */
class OriginPolicy
{
    /**
     * The configured allowlist is a wildcard.
     */
    public static function allowsAny(): bool
    {
        return DotEnv::get('API_ALLOW_ORIGIN') === '*';
    }

    /**
     * Returns the request Origin when it is allowed, otherwise null.
     * A wildcard allowlist returns '*'.
     */
    public static function resolve(): ?string
    {
        $origins = DotEnv::get('API_ALLOW_ORIGIN');
        if ($origins === '*') {
            return '*';
        }

        $origin = Server::origin();
        if ($origin === '') {
            return null;
        }

        $allowed = array_map('trim', explode(',', $origins));

        return in_array($origin, $allowed, true) ? $origin : null;
    }
}
