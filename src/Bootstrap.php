<?php

use React\EventLoop\Loop;
use React\Http\Message\ServerRequest;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\UrlParser;

class Bootstrap
{
    public function execute(ServerRequest $request)
    {
        if ($this->isEventLoop()) {
            $this->handleStateful($request);
        }

        if (!$this->isEventLoop()) {
            $this->handleStateless();
        }
    }

    public function handleStateful(ServerRequest $request)
    {
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
                return new \React\Http\Message\Response(
                    $response->status,
                    ['Content-Type' => 'application/json'],
                    json_encode($response->data)
                );
            }

            return new \React\Http\Message\Response(
                501,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Not implemented'])
            );
        }
        catch(Throwable $th) {
            if (DotEnv::get('API_DEBUG')) {
                return new \React\Http\Message\Response(
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

            return new \React\Http\Message\Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => $th->getMessage()])
            );
        }
    }

    public function handleStateless()
    {
        $uri = Server::uri(); /// if request: /api
        $body = Request::body();
        $method = Server::method();
        $route = Server::routeArgv(); /// if request: .htaccess redirect
        $pathQuery = UrlParser::pathQuery(Server::query()); /// if request: rest.php?path=/api/route

        try {
            $response = (new Request())->handle(
                new RequestData(
                    $method, 
                    $uri, 
                    $pathQuery, 
                    (array) $body,
                    (array) $this->queryParams()
                )
            );

            if ($response) {
                $response->response();
            }

            Response::json([
                'message' => 'Not implemented'
            ], Response::NOT_IMPLEMENTED);
        }
        catch(Throwable $th) {
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
    }

    public function queryParams()
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
    
    public function isEventLoop(): bool {
        try {
            $loop = Loop::get();
            return $loop !== null;
        } catch (Throwable $e) {
            return false;
        }
    }
}
