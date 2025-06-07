<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";

if (Server::isRouteApi()) {
    DotEnv::load(Server::getRootDir() . "/.env");

    require_once Server::getRootDir() . "/routes/api.php";

    (new Bootstrap())->execute();
}