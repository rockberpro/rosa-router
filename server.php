<?php

use React\Http\HttpServer;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";

DotEnv::load(Server::getAppRootDirectory()."/.env");

require_once Server::getAppRootDirectory()."/routes/api.php";

$port = DotEnv::get('API_SERVER_PORT');
$server = new HttpServer(function(ServerRequest $request) {
    return (new Bootstrap($request))->execute();
});
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});
$socket = new SocketServer("0.0.0.0:{$port}");
$server->listen($socket);

print("Server running at http://0.0.0.0:{$port}".PHP_EOL);