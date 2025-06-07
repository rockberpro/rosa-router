<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";

if (Server::isRouteApi()) {
    DotEnv::load(Server::getAppRootDirectory() . "/.env");

    require_once Server::getAppRootDirectory() . "/routes/api.php";

    (new Bootstrap())->execute();
}