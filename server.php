<?php

use React\Http\HttpServer;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

DotEnv::load('.env');
$port = DotEnv::get('API_SERVER_PORT');

$server = new HttpServer(function(ServerRequest $request) {
    (new Bootstrap())->execute($request);
});
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});

$socket = new SocketServer("0.0.0.0:{$port}");
$server->listen($socket);

echo "Server running at http://0.0.0.0:{$port}".PHP_EOL;