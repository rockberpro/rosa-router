<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Database\Handlers\PDOLogHandler;
use Rockberpro\RestRouter\Database\PDOConnection;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InfoLogHandler
{
    private ?Logger $logger = null;

    private function __construct($file_path = null)
    {
        $this->logger = new Logger('api_log');
        $log_file = $file_path ?? Server::getRootDir()."/logs/api_access.log";
        $this->logger->pushHandler(new StreamHandler($log_file, Logger::ERROR));
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
        if (DotEnv::get('API_LOGS')) {
            $this->logger->info($message, $data);
        }
    }

    public function getLooger(): Logger
    {
        return $this->logger;
    }
}