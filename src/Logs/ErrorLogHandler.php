<?php

namespace Rockberpro\RestRouter\Logs;

<<<<<<< HEAD
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Service\Container;
use Rockberpro\RestRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
=======
>>>>>>> da1b585 (refactor: de-duplicate log handlers, env coercion, and route context)
use Monolog\Logger;

class ErrorLogHandler extends AbstractLogHandler
{
    protected static function channel(): string
    {
        return 'error';
    }

    protected static function level(): int
    {
        return Logger::ERROR;
    }

    protected static function throwOnNoDestination(): bool
    {
        return false;
    }
}
