<?php

use Rockberpro\RosaRouter\Bootstrap;
use Rockberpro\RosaRouter\Core\Server;

require_once "vendor/autoload.php";

Bootstrap::setup();
$server = Server::init();
if ($server->isApiEndpoint()) {
    $server->loadRoutes('./routes/api.php');
    $server->execute(Server::MODE_STATELESS);
}
