<?php

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
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
    }

    /**
     * Binding LogRequestMiddleware (which resolves InfoLogHandler) while no log
     * destination is enabled — API_LOGS off and no handler supplied — is a
     * contradiction: it must fail loudly, not silently drop the log.
     */
    public function testThrowsWhenNoLogDestinationEnabled(): void
    {
        putenv('API_LOGS=false');

        InfoLogHandler::register(__DIR__ . '/../logs/info.log');

        $this->expectException(LogHandlerException::class);

        // resolving the service runs the registration closure
        Container::getInstance()->get(InfoLogHandler::class);
    }

    /**
     * A consumer-supplied Monolog handler is a valid destination on its own:
     * with API_LOGS off but a handler passed in, registration must not throw.
     */
    public function testSuppliedHandlerSatisfiesDestinationGuard(): void
    {
        putenv('API_LOGS=false');

        InfoLogHandler::register(
            __DIR__ . '/../logs/info.log',
            [new TestRecordingHandler()]
        );

        $handler = Container::getInstance()->get(InfoLogHandler::class);

        $this->assertInstanceOf(InfoLogHandler::class, $handler);
    }
}

class TestRecordingHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
    }
}
