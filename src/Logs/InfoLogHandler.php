<?php

namespace Rockberpro\RosaRouter\Logs;

use Rockberpro\RosaRouter\Core\Server;
use Rockberpro\RosaRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RosaRouter\Database\PDOConnection;
use Rockberpro\RosaRouter\Service\Container;
use Rockberpro\RosaRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InfoLogHandler
{
    private Logger $logger;

    public static function register(string $file_path)
    {
        $container = Container::getInstance();
        $container->set(InfoLogHandler::class, function() use ($file_path) {
            $instance = new self();
            $instance->logger = new Logger('info');

            if (DotEnv::get('API_LOGS')) {
                $instance->logger->pushHandler(new StreamHandler($file_path, Logger::INFO));
            }
            if (DotEnv::get('API_LOGS_DB')) {
                $instance->logger->pushHandler(new PDOLogHandler(
                    (new PDOConnection())->getPDO(),
                    'logs',
                    Logger::INFO,
                ));
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
        $this->logger->info($message, $data);
    }
}