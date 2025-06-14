<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InfoLogHandler
{
    private ?Logger $logger = null;

    public function __construct($file_path = null)
    {
        $this->logger = new Logger('api_log');
        if (DotEnv::get('API_LOGS')) {
            $this->logger->pushHandler(new StreamHandler($file_path, Logger::INFO));
        }
        if (DotEnv::get('API_LOGS_DB')) {
            $this->logger->pushHandler(new PDOLogHandler(
                (new PDOConnection())->getPDO(),
                'logs',
                Logger::INFO,
            ));
        }
    }

    public function write($message, $data)
    {
        $this->logger->info($message, $data);
    }

    public function getLooger(): Logger
    {
        return $this->logger;
    }
}