<?php

namespace Rockberpro\RestRouter;

use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Server;
use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use Rockberpro\RestRouter\Logs\RequestLogger;
use Rockberpro\RestRouter\Logs\ExceptionLogger;
use Rockberpro\RestRouter\Service\Container;
use React\Http\Message\ServerRequest;
use Rockberpro\RestRouter\Utils\DotEnv;
use Throwable;

class Bootstrap
{
    private ?ServerRequest $request;

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

    public function handleStateless(): \Rockberpro\RestRouter\Core\Response
    {
        try {
            $response = (new Request())
                ->handle(
                    Server::getInstance()->getRequestData()
                );

            if ($response) {
                $response->response();
            }

            \Rockberpro\RestRouter\Core\Response::json([
                'message' => 'Not implemented'
            ], 501);
        }
        catch (Throwable $t) {
            $this->writeLog($t);
        }

        if (DotEnv::get('API_DEBUG')) {
            \Rockberpro\RestRouter\Core\Response::json([
                'message' => $t->getMessage()
            ], 500);
        }

        \Rockberpro\RestRouter\Core\Response::json([
            'message' => 'Internal server error'
        ], 500);
    }

    public function handleStateful(ServerRequest $request): \React\Http\Message\Response
    {
        try {
            $response = (new Request())
                ->handle(
                    Server::getInstance()->getRequestData()
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
        catch (Throwable $t) {
            $this->writeLog($t);
        }

        if (DotEnv::get('API_DEBUG')) {
            return new \React\Http\Message\Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => $t->getMessage()])
            );
        }

        return new \React\Http\Message\Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => 'Internal server error'])
        );
    }

    public function isRunningCli(): bool {
        return (php_sapi_name() === 'cli');
    }

    /**
     * @param Throwable $t
     * @return void
     */
    public function writeLog(Throwable $t): void
    {
        $logger = Container::getInstance()->get(ErrorLogHandler::class);
        $logger->write('error', [
            'message' => $t->getMessage(),
            'file' => $t->getFile(),
            'line' => $t->getLine(),
            'trace' => $t->getTraceAsString(),
        ]);
    }
}