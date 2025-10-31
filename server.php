<?php

use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Bootstrap;
use React\Socket\SocketServer;
use React\Http\HttpServer;

require_once "vendor/autoload.php";
require_once "routes/api.php";

Bootstrap::setup();
$port = DotEnv::get('API_SERVER_PORT');

$server = new HttpServer(Bootstrap::stateful());
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});
$socket = new SocketServer("0.0.0.0:{$port}");
$server->listen($socket);

print("Server running at http://0.0.0.0:{$port}".PHP_EOL);