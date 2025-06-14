<?php

use Rockberpro\RestRouter\Bootstrap;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Logs\ExceptionLogger;
use Rockberpro\RestRouter\Logs\RequestLogger;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use React\Http\HttpServer;

require_once "vendor/autoload.php";

DotEnv::load(".env");

require_once "routes/api.php";

$port = DotEnv::get('API_SERVER_PORT');
$server = new HttpServer(function(ServerRequest $request) {
    return (new Bootstrap($request))
            ->setRequestLogger(new RequestLogger("logs/api_access.log"))
            ->setExceptionLogger(new ExceptionLogger("logs/api_error.log"))
            ->execute();
});
$server->on('error', function (Throwable $e) {
    print("Request error: " . $e->getMessage().PHP_EOL);
});
$socket = new SocketServer("0.0.0.0:{$port}");
$server->listen($socket);

print("Server running at http://0.0.0.0:{$port}".PHP_EOL);