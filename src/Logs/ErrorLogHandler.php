<?php

namespace Rockberpro\RestRouter\Logs;

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
