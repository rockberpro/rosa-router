<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";

if (Server::isRouteApi()) {
    DotEnv::load(".env");

    require_once "routes/api.php";

    (new Bootstrap())->execute();
}