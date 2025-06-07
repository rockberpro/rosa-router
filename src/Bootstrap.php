<?php

namespace Rockberpro\RestRouter;

use React\Http\Message\ServerRequest;
use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\RequestData;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Server;
use Rockberpro\RestRouter\Utils\UrlParser;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use stdClass;
use Throwable;

class Bootstrap
{
    private ?ServerRequest $request;
    private Logger $logger;

    public function __construct(?ServerRequest $request = null)
    {
        $this->request = $request;

        $this->logger = new Logger('api_log');
        $log_file = Server::getRootDir()."/logs/api_error.log";
        $this->logger->pushHandler(new StreamHandler($log_file, Logger::ERROR));
    }

    public function execute()
    {
        if (!$this->isEventLoop()) {
            return $this->handleStateless();
        }
        return $this->handleStateful($this->request);
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
        } catch (Throwable $th) {
            $this->logger->error('Exception in handleStateful', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            return new \React\Http\Message\Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => $th->getMessage()])
            );
        }
    }

    public function handleStateless()
    {
        $uri = Server::uri();
        $body = Request::body();
        $method = Server::method();
        $pathQuery = UrlParser::pathQuery(Server::query());

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
        } catch (Throwable $th) {
            $this->logger->error('Exception in handleStateless', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

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
        return (php_sapi_name() === 'cli');
    }
}