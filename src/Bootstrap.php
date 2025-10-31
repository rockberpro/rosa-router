<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Utils\DotEnv;
use React\Http\Message\ServerRequest;

/**
 * Small bootstrap helper to initialize environment and logging
 * and provide convenient entry points for stateful (react) and
 * stateless (regular PHP) dispatching.
 */
class Bootstrap
{
    private static bool $booted = false;

    /**
     * Initialize environment and register log handlers. Idempotent.
     */
    public static function setup(string $envPath = ".env", string $infoLog = "logs/info.log", string $errorLog = "logs/error.log"): bool
    {
        if (self::$booted) {
            return true;
        }

        DotEnv::load($envPath);
        InfoLogHandler::register($infoLog);
        ErrorLogHandler::register($errorLog);

        return self::$booted = true;
    }

    /**
     * Stateless entrypoint: boot and dispatch if the current request is an API endpoint.
     * Returns whatever Server::dispatch() returns or null when not an API endpoint.
     */
    public static function stateless()
    {
        if (!self::$booted) {
            throw new \RuntimeException('Bootstrap not booted. Call Bootstrap::setup() before using stateless entrypoint.');
        }

        if (Server::getInstance()->isApiEndpoint()) {
            return Server::getInstance()->dispatch();
        }

        return null;
    }

    /**
     * Stateful entrypoint for React HTTP server: returns a callable compatible with
     * React\Http\HttpServer which will ensure the Server instance handles the
     * incoming ServerRequest.
     */
    public static function stateful(): callable
    {
        if (!self::$booted) {
            throw new \RuntimeException('Bootstrap not booted. Call Bootstrap::setup() before using stateless entrypoint.');
        }

        return function (ServerRequest $request) {
            return Server::getInstance()
                ->stateful($request)
                ->dispatch();
        };
    }
}

