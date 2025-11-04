<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Core\Server;

require_once "vendor/autoload.php";
require_once "routes/api.php";

Bootstrap::setup(".ini");
$server = Server::init();
if ($server->isApiEndpoint()) {
    Bootstrap::execute(Bootstrap::MODE_STATELESS);
}
