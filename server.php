<?php

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Socket\SocketServer;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\Utils\DotEnv;

require_once "vendor/autoload.php";
require_once "autoload.php";
require_once "routes/api.php";

$server = new HttpServer(function(ServerRequest $request) {

    try
    {
        DotEnv::load('.env');

        $response = (new Request())->handle(
            $request->getMethod(), 
            $request->getUri()->getPath(), 
            $query = null, 
            $body = null
        );

        if (get_class($response) === 'Rockberpro\RestRouter\Response') {
            return (new Response($response->status))->json($response->data);
        }
        if (get_class(object: $response) === 'React\Http\Message\Response') {
            return $response;
        }
    }
    catch(Throwable $th)
    {
        if (DotEnv::get('API_DEBUG'))
        {
            Response::json([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTrace(),
            ]);
        }
    
        Response::json([
            'message' => $th->getMessage(),
        ]);
    }
});

$socket = new SocketServer('0.0.0.0:8081');
$server->listen($socket);

echo "Server running at http://0.0.0.0:8081".PHP_EOL;