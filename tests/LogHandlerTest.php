<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RosaRouter\Logs\InfoLogHandler;
use Rockberpro\RosaRouter\Logs\LogHandlerException;
use Rockberpro\RosaRouter\Service\Container;

class LogHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    protected function tearDown(): void
    {
        putenv('API_LOGS');
        putenv('API_LOGS_DB');
    }

    /**
     * Binding LogRequestMiddleware (which resolves InfoLogHandler) while both
     * log destinations are disabled is a contradiction: it must fail loudly,
     * not silently drop the log.
     */
    public function testThrowsWhenNoLogDestinationEnabled(): void
    {
        putenv('API_LOGS=false');
        putenv('API_LOGS_DB=false');

        InfoLogHandler::register(__DIR__ . '/../logs/info.log');

        $this->expectException(LogHandlerException::class);

        // resolving the service runs the registration closure
        Container::getInstance()->get(InfoLogHandler::class);
    }
}
