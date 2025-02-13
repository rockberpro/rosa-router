<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Interfaces\ServerInterface;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @version 1.0
 * @package Rockberpro\RestRouter
 */
class Server implements ServerInterface
{
    public static function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public static function query()
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function key()
    {
        return $_SERVER['HTTP_X_API_KEY'] ?? '';
    }

    public static function authorization()
    {
        return $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    }

    public static function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public static function routeArgv()
    {
        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            return explode('path=', $_SERVER['argv'][0])[1];
        }
    }

    public static function documentRoot()
    {
        return $_SERVER['DOCUMENT_ROOT'] ?? '';
    }

    public static function serverName()
    {
        return $_SERVER['SERVER_NAME'] ?? '';
    }

    public static function serverAddress()
    {
        return $_SERVER['SERVER_ADDR'] ?? '';
    }

    public static function remoteAddress()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public static function targetAddress()
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public static function requestMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function requestUri()
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }
}