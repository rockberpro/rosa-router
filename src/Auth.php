<?php

namespace Rockberpro\RosaRouter;

use Rockberpro\RosaRouter\Core\Response;

class Auth
{
    /**
     * Check if API-Key is valid
     *
     * Returns a denial Response on failure, otherwise null. Never terminates
     * the process, so it is safe in the stateful server mode.
     *
     * @param string $api_key
     * @param string $client_key
     * @return Response|null
     */
    public static function check($api_key, $client_key): ?Response
    {
        if (!$api_key) {
            return new Response(['message' => 'API-Key could not be loaded'], Response::FORBIDDEN);
        }

        if (!$client_key) {
            return new Response(['message' => 'API-Key was not provided'], Response::FORBIDDEN);
        }

        if (!hash_equals($api_key, $client_key)) {
            return new Response(['message' => 'Incorrect API-Key provided'], Response::FORBIDDEN);
        }

        return null;
    }
}