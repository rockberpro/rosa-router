<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Utils\UrlParser;
use React\Http\Message\ServerRequest;
use stdClass;
use Throwable;

class Bootstrap
{
    private ?ServerRequest $request;
    private ?ErrorLogHandler $errorLogHander = null;
    private ?InfoLogHandler $infoLogHandler = null;

    public function __construct(?ServerRequest $request = null)
    {
        $this->request = $request;
    }

    public function execute()
    {
        if (!$this->isRunningCli()) {
            return $this->handleStateless();
        }
        return $this->handleStateful($this->request);
    }

    public function handleStateless()
    {
        $uri = Server::uri();
        $body = Request::body();
        $method = Server::method();
        $pathQuery = UrlParser::pathQuery(Server::query());

        try {
            $response = (new Request())
                ->setInfoLogger($this->getInfoLogger())
                ->setErrorLogger($this->getErrorLogger())
                ->handle(
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

            \Rockberpro\RestRouter\Core\Response::json([
                'message' => 'Not implemented'
            ], \Rockberpro\RestRouter\Core\Response::NOT_IMPLEMENTED);
        }
        catch (Throwable $th) {
            $this->writeErrorLog([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            \Rockberpro\RestRouter\Core\Response::json([
                'message' => $th->getMessage(),
            ], \Rockberpro\RestRouter\Core\Response::INTERNAL_SERVER_ERROR);
        }
    }

    public function handleStateful(ServerRequest $request)
    {
        try {
            $response = (new Request())
                ->setInfoLogger($this->getInfoLogger())
                ->setErrorLogger($this->getErrorLogger())
                ->handle(
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
        catch (Throwable $th) {
            $this->writeErrorLog([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
        }

        return new \React\Http\Message\Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => $th->getMessage()])
        );
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

    private function writeErrorLog(array $data)
    {
        try {
            $this->getErrorLogger()->write('Error', $data);
        }
        catch (Throwable $e) {
            \Rockberpro\RestRouter\Core\Response::json([
                'message' => "Error writing exception log: {$e->getMessage()}"
            ], \Rockberpro\RestRouter\Core\Response::INTERNAL_SERVER_ERROR);
        }
    }

    public function setErrorLogger(?ErrorLogHandler $logger) {
        $this->errorLogHander = $logger;

        return $this;
    }
    public function getErrorLogger(): ?ErrorLogHandler {
        return $this->errorLogHander;
    }

    public function setInfoLogger(?InfoLogHandler $logger) {
        $this->infoLogHandler = $logger;

        return $this;
    }
    public function getInfoLogger(): ?InfoLogHandler {
        return $this->infoLogHandler;
    }

    public function isRunningCli(): bool {
        return (php_sapi_name() === 'cli');
    }
}