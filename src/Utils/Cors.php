<?php

namespace Rockberpro\RosaRouter\Utils;

use Rockberpro\RosaRouter\Core\Server;

/**
 * Cross-origin Resource Sharing
 */
class Cors
{
    public static function allowOrigin()
    {
        $origins = DotEnv::get('API_ALLOW_ORIGIN');
        if ($origins === '*') {
            header("Access-Control-Allow-Origin: *");
            return;
        }
        $_origins = explode(',', $origins);
        $_allow = array_filter($_origins, function($origin) {
            return Server::remoteAddress() === $origin;
        });
        $allow = end($_allow);
        if ($allow) {
            header("Access-Control-Allow-Origin: {$allow}");
        }
    }
}