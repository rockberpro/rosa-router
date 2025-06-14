<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ErrorLogHandler
{
    private ?Logger $logger = null;

    public function __construct($file_path)
    {
        $this->logger = new Logger('api_log');
        if (DotEnv::get('API_LOGS')) {
            $this->logger->pushHandler(new StreamHandler($file_path, Logger::ERROR));
        }
        if (DotEnv::get('API_LOGS_DB')) {
            $this->logger->pushHandler(new PDOLogHandler(
                (new PDOConnection())->getPDO(),
                'logs',
                Logger::ERROR,
            ));
        }
    }

    public function write($message, $data)
    {
        $this->logger->error($message, $data);
    }

    public function getLooger(): Logger
    {
        return $this->logger;
    }
}