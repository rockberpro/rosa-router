<?php

use Rockberpro\RestRouter\RequestHandler;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

if (Server::getInstance()->isApiEndpoint()) {
    DotEnv::load(".env");
    InfoLogHandler::register("logs/info.log");
    ErrorLogHandler::register("logs/error.log");

    Server::getInstance()->dispatch();
}