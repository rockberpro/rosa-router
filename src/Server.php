<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\ServerInterface;

/**
 * @author Samuel Oberger Rockenbach
 * 
 * @package Rockberpro\RestRouter
 */
class Server implements ServerInterface
{
    public static function uri(): string
    {
        return urldecode(parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH));
    }

    public static function isRouteApi(): bool
    {
        return strpos(self::uri(), '/api/') === 0;
    }

    public static function query(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function key(): string
    {
        return $_SERVER['HTTP_X_API_KEY'] ?? '';
    }

    public static function authorization(): string
    {
        return $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    }

    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function routeArgv(): string
    {
        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            return explode('path=', $_SERVER['argv'][0])[1] ?? '';
        }
        return '';
    }

    public static function documentRoot(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] ?? '';
    }

    public static function serverName(): string
    {
        return $_SERVER['SERVER_NAME'] ?? '';
    }

    public static function serverAddress(): string
    {
        return $_SERVER['SERVER_ADDR'] ?? '';
    }

    public static function remoteAddress(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public static function targetAddress(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public static function requestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    public static function requestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }
}