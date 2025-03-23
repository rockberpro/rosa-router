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
$pathQuery = UrlParser::pathQuery($uri); /// if request: rest.php?path=/api/route

try
{
    DotEnv::load('.env');

    $response = (new Request())->handle(
        $method, 
        $uri, 
        $pathQuery, 
        (array) $body,
        (array) queryParams()
    );

    if (get_class($response) === 'Rockberpro\RestRouter\Response') {
        $response->response();
    }

    Response::json([
        'message' => 'Not implemented'
    ], Response::INTERNAL_SERVER_ERROR);
}
catch(Throwable $th)
{
    if (DotEnv::get('API_DEBUG')) {
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

function queryParams()
{
    $queryParams = new stdClass();
    if (empty(Server::query())) {
        return $queryParams;
    }

    if (stripos(Server::query(), 'path=') !== false) {
        $parts = [];
        $query = Server::query();
        parse_str($query, $parts);
        if (!empty($parts)) {
            foreach($parts as $key => $value) {
                if ($key !== 'path') {
                    $queryParams->$key = $value;
                }
            }
        }
    }
    else if (stripos(Server::query(), '=') !== false) {
        $parts = [];
        $query = Server::query();
        parse_str($query, $parts);
        if (!empty($query)) {
            foreach($parts as $key => $value) {
                $queryParams->$key = $value;
            }
        }
    }

    return $queryParams;
}