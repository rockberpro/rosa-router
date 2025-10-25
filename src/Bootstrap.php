<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\RequestLogger;
use Rockberpro\RestRouter\Logs\ExceptionLogger;
use Rockberpro\RestRouter\Utils\DotEnv;
use React\Http\Message\ServerRequest;
use React\Http\Message\Response as ReactResponse;
use Rockberpro\RestRouter\Core\Response as RouterResponse;
use stdClass;
use Throwable;

class Bootstrap
{
    private ?ServerRequest $request;
    private ?ExceptionLogger $exceptionLogger = null;
    private ?RequestLogger $requestLogger = null;

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
        try {
            $response = (new Request())
                ->setRequestLogger($this->getRequestLogger())
                ->handle(
                    Server::getInstance()->getRequestData()
                );

            if ($response) {
                $response->response();
            }

            RouterResponse::json([
                'message' => 'Not implemented'
            ], RouterResponse::NOT_IMPLEMENTED);
        }
        catch (Throwable $t) {
            if ($this->getExceptionLogger()) {
                $this->getExceptionLogger()->writeLog($t);
            }

            if (DotEnv::get('API_DEBUG')) {
                RouterResponse::json([
                    'message' => $t->getMessage(),
                    'file' => $t->getFile(),
                    'line' => $t->getLine(),
                    'trace' => $t->getTraceAsString(),
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
                ->setRequestLogger($this->getRequestLogger())
                ->handle(
                    Server::getInstance()->getRequestData()
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
        catch (Throwable $t) {
            if ($this->getExceptionLogger()) {
                $this->getExceptionLogger()->writeLog($t);
            }

            if (DotEnv::get('API_DEBUG')) {
                return new ReactResponse(
                    500,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'message' => $t->getMessage(),
                        'file' => $t->getFile(),
                        'line' => $t->getLine(),
                        'trace' => $t->getTraceAsString(),
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

    public function setExceptionLogger(?ExceptionLogger $logger)
    {
        $this->exceptionLogger = $logger;
        return $this;
    }
    public function getExceptionLogger(): ?ExceptionLogger
    {
        return $this->exceptionLogger;
    }

    public function setRequestLogger(?RequestLogger $logger)
    {
        $this->requestLogger = $logger;
        return $this;
    }
    public function getRequestLogger(): ?RequestLogger
    {
        return $this->requestLogger;
    }

    public function isRunningCli(): bool {
        return (php_sapi_name() === 'cli');
    }
}