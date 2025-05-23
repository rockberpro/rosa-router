<?php

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "routes/api.php";

DotEnv::load('.env');

$server = new HttpServer(function(ServerRequest $request) {
    try {
        $response = (new Request())->handle(
            new RequestData(
                $request->getMethod(),
                $request->getUri()->getPath(),
                null, 
                (array) $request->getParsedBody(),
                (array) $request->getQueryParams()
            )
        );

        if ($response) {
            return new Response(
                $response->status,
                ['Content-Type' => 'application/json'],
                json_encode($response->data)
            );
        }

        return new Response(
            501,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => 'Not implemented'])
        );
    }
    catch(Throwable $th) {
        if (DotEnv::get('API_DEBUG')) {
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTrace(),
                ])
            );
        }

        return new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => $th->getMessage()])
        );
    }
});

$socket = new SocketServer("0.0.0.0:".DotEnv::get('API_SERVER_PORT'));
$server->listen($socket);

echo "Server running at http://0.0.0.0:".DotEnv::get('API_SERVER_PORT').PHP_EOL;