<?php

namespace Rockberpro\RestRouter\Core;

class ServerHelper
{
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