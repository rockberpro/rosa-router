<?php

namespace Rockberpro\RestRouter\Utils;

use Rockberpro\RestRouter\Server;

/**
 * Cross-origin Resource Sharing
 * 
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter\Utils
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