<?php

namespace Rockberpro\RosaRouter\Utils;

use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\Server;

/**
 * Same Origin Policy
 */
class Sop
{
    /**
     * Returns a denial Response when the host is not allowed, otherwise null.
     * Never terminates the process, so it is safe in the stateful server mode.
     */
    public static function check(): ?Response
    {
        $origins = DotEnv::get('API_ALLOW_ORIGIN');
        if ($origins === '*') {
            return null;
        }
        $_origins = explode(',', $origins);
        $_allow = array_filter($_origins,function($origin) {
            return Server::remoteAddress() === $origin;
        });
        $allow = end($_allow);
        if (!$allow) {
            return new Response([
                'message' => 'Access denied for your host'
            ], Response::FORBIDDEN);
        }

        return null;
    }
}