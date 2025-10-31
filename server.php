<?php

use Rockberpro\RestRouter\RequestHandler;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Logs\ExceptionLogger;
use Rockberpro\RestRouter\Logs\RequestLogger;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use React\Http\HttpServer;

require_once "vendor/autoload.php";
require_once "routes/api.php";

DotEnv::load(".env");
InfoLogHandler::register("logs/info.log");
ErrorLogHandler::register("logs/error.log");

$port = DotEnv::get('API_SERVER_PORT');
$server = new HttpServer(function(ServerRequest $request) {
    Server::getInstance()->stateful($request);
    return Server::getInstance()->dispatch();
});
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});
$socket = new SocketServer("0.0.0.0:{$port}");
$server->listen($socket);

print("Server running at http://0.0.0.0:{$port}".PHP_EOL);