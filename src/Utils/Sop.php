<?php

namespace Rockberpro\RestRouter\Utils;

use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Core\Server;

/**
 * Same Origin Policy
 */
class Sop
{
    public static function check()
    {
        $origins = DotEnv::get('API_ALLOW_ORIGIN');
        if ($origins === '*') {
            return;
        }
        $_origins = explode(',', $origins);
        $_allow = array_filter($_origins,function($origin) {
            return Server::remoteAddress() === $origin;
        });
        $allow = end($_allow);
        if (!$allow) {
            Response::json([
                'message' => 'Access denied for your host'
            ], Response::FORBIDDEN);
        }
    }
}