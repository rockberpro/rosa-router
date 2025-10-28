<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Logs\RequestLogger;
use Rockberpro\RestRouter\Logs\ExceptionLogger;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

$server = new Server();
if ($server->isApiEndpoint()) {
    DotEnv::load(".env");

    InfoLogHandler::register("logs/info.log");
    ErrorLogHandler::register("logs/error.log");

    (new Bootstrap())->execute();
}