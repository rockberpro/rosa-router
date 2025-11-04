<?php

use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Bootstrap;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use Rockberpro\RestRouter\Core\Server;

require_once "vendor/autoload.php";

Bootstrap::setup();
$port = DotEnv::get('API_SERVER_PORT');
$address = DotEnv::get('API_SERVER_ADDRESS');

$server = Server::init();
$server->loadRoutes('./routes/api.php');
$server = new HttpServer(
    $server->execute(Server::MODE_STATEFUL)
);
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});
$socket = new SocketServer("{$address}:{$port}");
$server->listen($socket);

print("Server running at http://{$address}:{$port}".PHP_EOL);
