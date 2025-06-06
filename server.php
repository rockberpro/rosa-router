<?php

use React\Http\HttpServer;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

DotEnv::load('.env');

$server = new HttpServer(function(ServerRequest $request) {
    (new Bootstrap())->execute($request);
});

$socket = new SocketServer("0.0.0.0:".DotEnv::get('API_SERVER_PORT'));
$server->listen($socket);

echo "Server running at http://0.0.0.0:".DotEnv::get('API_SERVER_PORT').PHP_EOL;