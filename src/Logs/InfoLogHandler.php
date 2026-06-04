<?php

namespace Rockberpro\RestRouter\Logs;

use Monolog\Logger;

class InfoLogHandler extends AbstractLogHandler
{
    protected static function channel(): string
    {
        return 'info';
    }

    protected static function level(): int
    {
        return Logger::INFO;
    }

    protected static function throwOnNoDestination(): bool
    {
        return true;
    }
}
