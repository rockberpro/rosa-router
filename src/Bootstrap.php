<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\RequestData;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\UrlParser;
use React\Http\Message\ServerRequest;
use \React\Http\Message\Response as ReactResponse;
use Rockberpro\RestRouter\Core\Response as RouterResponse;
use RuntimeException;
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

            RouterResponse::json([
                'message' => 'Not implemented'
            ], RouterResponse::NOT_IMPLEMENTED);
        }
        catch (Throwable $th) {
            try {
                $this->writeErrorLog([
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString(),
                ]);
            }
            catch (Throwable $logError) {
                if (DotEnv::get('API_DEBUG')) {
                    return RouterResponse::json([
                        'message' => 'Error writing log: ' . $logError->getMessage(),
                        'file' => $logError->getFile(),
                        'line' => $logError->getLine(),
                        'trace' => $logError->getTraceAsString(),
                    ], RouterResponse::INTERNAL_SERVER_ERROR);
                }
                return RouterResponse::json([
                    'message' => 'Internal server error'
                ], RouterResponse::INTERNAL_SERVER_ERROR);
            }

            if (DotEnv::get('API_DEBUG')) {
                return RouterResponse::json([
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString(),
                ], RouterResponse::INTERNAL_SERVER_ERROR);
            }

            RouterResponse::json([
                'message' => 'Internal server error'
            ], RouterResponse::INTERNAL_SERVER_ERROR);
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
                return new ReactResponse(
                    $response->status,
                    ['Content-Type' => 'application/json'],
                    json_encode($response->data)
                );
            }

            return new ReactResponse(
                501,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Not implemented'])
            );
        }
        catch (Throwable $th) {
            try {
                $this->writeErrorLog([
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString(),
                ]);
            }
            catch (Throwable $logError) {
                if (DotEnv::get('API_DEBUG')) {
                    return new ReactResponse(
                        500,
                        ['Content-Type' => 'application/json'],
                        json_encode([
                            'message' => 'Error writing log: ' . $logError->getMessage(),
                            'file' => $logError->getFile(),
                            'line' => $logError->getLine(),
                            'trace' => $logError->getTraceAsString(),
                        ])
                    );
                }
                return new ReactResponse(
                    500,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Internal server error'])
                );
            }

            if (DotEnv::get('API_DEBUG')) {
                return new ReactResponse(
                    500,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'trace' => $th->getTraceAsString(),
                    ])
                );
            }
        }

        return new ReactResponse(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => 'Internal server error'])
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
        if (!$this->getErrorLogger()) {
            throw new RuntimeException('Error logger is not set');
        }
        $this->getErrorLogger()->write('Error', $data);
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