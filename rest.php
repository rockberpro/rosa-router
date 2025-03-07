<?php

use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\UrlParser;

require_once "autoload.php";
require_once "routes/api.php";

$uri = Server::uri(); /// if request: /api
$body = Request::body();
$method = Server::method();
$route = Server::routeArgv(); /// if request: .htaccess redirect
$query = UrlParser::pathQuery($uri); /// if request: rest.php?path=/api/route

try
{
    DotEnv::load('.env');
    (new Request())->handle($method, $uri, $query, $body);
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
        ], Response::INTERNAL_SERVER_ERROR);
    }

    Response::json([
        'message' => $th->getMessage(),
    ], Response::INTERNAL_SERVER_ERROR);
}