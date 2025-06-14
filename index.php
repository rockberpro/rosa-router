<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\RequestLogger;
use Rockberpro\RestRouter\Logs\RouterLogger;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";

if (Server::isApiEndpoint()) {
    DotEnv::load(".env");

    require_once "routes/api.php";

    (new Bootstrap())
        ->setRequestLogger(new RequestLogger("logs/api_access.log"))
        ->setRouterLogger(new RouterLogger("logs/api_error.log"))
        ->execute();
}