<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\IniEnv;

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

        // detect by file extension: .ini -> IniEnv, .env -> DotEnv
        $lower = strtolower(trim($envPath));
        // Use substr_compare for PHP 7.4 compatibility
        if (substr_compare($lower, '.ini', -4, 4) === 0) {
            IniEnv::load($envPath);
        } elseif (substr_compare($lower, '.env', -4, 4) === 0) {
            DotEnv::load($envPath);
        } else {
            // fallback: try ini first, then dotenv
            try {
                IniEnv::load($envPath);
            } catch (\Throwable $e) {
                DotEnv::load($envPath);
            }
        }

        InfoLogHandler::register($infoLog);
        ErrorLogHandler::register($errorLog);

        return self::$booted = true;
    }

    public static function isBooted()
    {
        return self::$booted;
    }
}
