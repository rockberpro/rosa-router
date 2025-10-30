<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Service\Container;
use Rockberpro\RestRouter\Utils\DotEnv;
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
        $this->logger->info($message, $data);
    }
}