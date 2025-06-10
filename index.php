<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;

require_once "vendor/autoload.php";

if (Server::isRouteApi()) {
    DotEnv::load(".env");

    require_once "routes/api.php";

    (new Bootstrap())
        ->setInfoLogger(new InfoLogHandler("logs/api_access.log"))
        ->setErrorLogger(new ErrorLogHandler("logs/api_error.log"))
        ->execute();
}