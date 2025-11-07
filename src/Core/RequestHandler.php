<?php

namespace Rockberpro\RosaRouter\Core;

use Rockberpro\RosaRouter\Core;
use Rockberpro\RosaRouter\Logs\ErrorLogHandler;
use Rockberpro\RosaRouter\Service\Container;
use Rockberpro\RosaRouter\Utils\DotEnv;
use Throwable;

class RequestHandler
{
    /**
     * @param bool $stateful
     * @return \React\Http\Message\Response|Core\Response
     */
    public function dispatch(bool $stateful)
    {
        if ($stateful) {
            return $this->handleStateful();
        }
        return $this->handleStateless();
    }

    public function handleStateless(): \Rockberpro\RosaRouter\Core\Response
    {
        $request = new Request();
        try {
            return $request->handle(
                Server::getInstance()->getRequestData()
            );
        }
        catch (Throwable $t) {
            $this->writeLog($t);
        }

        if (DotEnv::get('API_DEBUG')) {
            return new \Rockberpro\RosaRouter\Core\Response([
                'message' => $t->getMessage()
            ], 500);
        }

        return new \Rockberpro\RosaRouter\Core\Response([
            'message' => 'Internal server error'
        ], 500);
    }

    public function handleStateful(): \React\Http\Message\Response
    {
        $request = new Request();
        try {
            $response = $request->handle(
                Server::getInstance()->getRequestData()
            );

            if ($response) {
                // for HEAD requests, return only headers (no body)
                if (Server::requestMethod() === 'HEAD') {
                    return new \React\Http\Message\Response(
                        $response->status,
                        $response->getHeadersForHead(),
                        ''
                    );
                }
                // for OPTIONS requests, return only headers (no body)
                if (Server::requestMethod() === 'OPTIONS') {
                    return new \React\Http\Message\Response(
                        Response::NO_CONTENT,
                        $response->getHeadersForOptions(),
                        ''
                    );
                }

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