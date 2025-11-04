<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Core\Server;

require_once "vendor/autoload.php";
require_once "routes/api.php";

Bootstrap::setup();
$server = Server::init();
if ($server->isApiEndpoint()) {
    $server->execute(Server::MODE_STATELESS);
}
