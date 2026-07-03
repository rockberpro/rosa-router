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
     * Run the transport-agnostic request pipeline and return a Core\Response.
     * The active transport is responsible for turning this into the concrete
     * wire response (SAPI output or React response).
     */
    public function dispatch(): Response
    {
        try {
            return (new Request())->handle(
                Server::getInstance()->getRequestData()
            );
        }
        catch (Throwable $t) {
            $this->writeLog($t);

            $message = DotEnv::get('API_DEBUG') ? $t->getMessage() : 'Internal server error';

            return new Response(['message' => $message], 500);
        }
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