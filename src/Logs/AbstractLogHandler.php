<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Service\Container;
use Rockberpro\RestRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Shared base for the request/info and error log handlers.
 *
 * Subclasses differ only in their Monolog channel name, log level, and whether
 * an absent log destination is a hard error. The deliberate asymmetry from the
 * logging work is preserved: the info/request handler throws when no
 * destination is enabled, the error handler does not (it runs inside the error
 * path and must not throw there).
 */
abstract class AbstractLogHandler
{
    private Logger $logger;

    /**
     * Monolog channel name for this handler.
     */
    abstract protected static function channel(): string;

    /**
     * Monolog level this handler writes at.
     */
    abstract protected static function level(): int;

    /**
     * Whether resolving the handler with no enabled destination is fatal.
     */
    abstract protected static function throwOnNoDestination(): bool;

    public static function register(string $file_path)
    {
        $container = Container::getInstance();
        $container->set(static::class, function() use ($file_path) {
            $instance = new static();
            $instance->logger = new Logger(static::channel());

            if (DotEnv::get('API_LOGS')) {
                $instance->logger->pushHandler(new StreamHandler($file_path, static::level()));
            }
            if (DotEnv::get('API_LOGS_DB')) {
                $instance->logger->pushHandler(new PDOLogHandler(
                    (new PDOConnection())->getPDO(),
                    'logs',
                    static::level(),
                ));
            }

            if (static::throwOnNoDestination() && count($instance->logger->getHandlers()) === 0) {
                throw new LogHandlerException(
                    'LogRequestMiddleware is active but no log destination is enabled. '
                    . 'Set API_LOGS=true and/or API_LOGS_DB=true, or remove the middleware from the route.'
                );
            }

            return $instance;
        });
    }

    public function write($message, $data)
    {
        $log_data = [
            'subject' => DotEnv::get('API_NAME'),
            'remote_address' => Server::remoteAddress(),
            'target_address' => Server::targetAddress(),
            'user_agent' => Server::userAgent(),
            'request_method' => Server::requestMethod(),
            'request_uri' => Server::requestUri(),
        ];
        $data = array_merge($data, $log_data);
        $this->logger->log(static::level(), $message, $data);
    }
}
