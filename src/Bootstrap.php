<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;
use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\UrlParser;
use React\Http\Message\ServerRequest;
use stdClass;
use Throwable;
use Exception;

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

            Response::json([
                'message' => 'Not implemented'
            ], Response::NOT_IMPLEMENTED);
        }
        catch (Throwable $th) {
            $this->writeErrorLog([
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

    private function writeErrorLog(array $data)
    {
        try {
            if (!$this->getErrorLogger() && DotEnv::get('API_LOGS')) {
                throw new Exception('Error logger is not set');
            }
            if (DotEnv::get('API_LOGS')) {
                $this->getErrorLogger()->write('Error', $data);
            }
        }
        catch (Throwable $e) {
            Response::json([
                'message' => "Error writing exception log: {$e->getMessage()}"
            ], Response::INTERNAL_SERVER_ERROR);
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