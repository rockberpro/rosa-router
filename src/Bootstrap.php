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

    public const MODE_STATELESS = 'stateless';
    public const MODE_STATEFUL = 'stateful';

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
     * Centralized executor. Accepts an explicit mode or will detect one when null.
     * - If mode is stateless: dispatch immediately and return the dispatch result (or null when not an API endpoint).
     * - If mode is stateful: return a callable compatible with React\Http\HttpServer (same as previous stateful()).
     * This preserves backward compatibility while providing a single authoritative entry point.
     *
     * @param string|null $mode one of self::MODE_STATELESS|self::MODE_STATEFUL or null to autodetect
     * @return mixed|null|callable
     */
    public static function execute(?string $mode = null)
    {
        if (!self::$booted) {
            self::setup();
        }

        if ($mode === null) {
            $mode = self::detectMode();
        }

        switch ($mode) {
            case self::MODE_STATELESS:
                return self::doStateless();

            case self::MODE_STATEFUL:
                return self::doStateful();

            default:
                throw new \InvalidArgumentException("Unknown bootstrap mode: {$mode}");
        }
    }

    /**
     * Backwards-compatible convenience wrapper for stateless mode.
     */
    public static function stateless()
    {
        return self::execute(self::MODE_STATELESS);
    }

    /**
     * Backwards-compatible convenience wrapper for stateful mode.
     */
    public static function stateful(): callable
    {
        $callable = self::execute(self::MODE_STATEFUL);
        if (!is_callable($callable)) {
            throw new \RuntimeException('Stateful mode did not return a callable as expected.');
        }

        return $callable;
    }

    /**
     * Internal implementation for stateless handling (preserves previous behavior).
     */
    protected static function doStateless()
    {
        if (Server::getInstance()->isApiEndpoint()) {
            return Server::getInstance()->dispatch();
        }

        return null;
    }

    /**
     * Internal implementation for stateful handling (preserves previous behavior).
     * Returns the callable expected by React\Http\HttpServer.
     */
    protected static function doStateful(): callable
    {
        return function (ServerRequest $request) {
            return Server::getInstance()
                ->stateful($request)
                ->dispatch();
        };
    }

    /**
     * Simple detection heuristics for the API mode
     */
    protected static function detectMode(): string
    {
        // CLI should default to stateless (no HTTP sessions)
        if (php_sapi_name() === 'cli' || PHP_SAPI === 'cli') {
            return self::MODE_STATEFUL;
        }

        // default/fallback
        return self::MODE_STATELESS;
    }
}
